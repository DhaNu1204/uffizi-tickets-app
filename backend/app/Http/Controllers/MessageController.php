<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MessageTemplate;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Send ticket to customer
     * POST /api/bookings/{id}/send-ticket
     */
    public function sendTicket(Request $request, int $id): JsonResponse
    {
        Log::info('sendTicket called', [
            'booking_id' => $id,
            'request_data' => $request->all(),
        ]);

        $booking = Booking::findOrFail($id);

        // Validate booking is for Timed Entry only
        if (!$booking->isTimedEntry()) {
            Log::warning('sendTicket 422: Not timed entry', ['booking_id' => $id, 'product_id' => $booking->bokun_product_id]);
            return response()->json([
                'success' => false,
                'error' => 'Ticket sending is only available for Timed Entry tickets',
            ], 422);
        }

        // Validate booking has reference number
        if (!$booking->reference_number) {
            Log::warning('sendTicket 422: No reference number', ['booking_id' => $id]);
            return response()->json([
                'success' => false,
                'error' => 'Booking must have a ticket reference number before sending',
            ], 422);
        }

        // Validate audio guide dynamic link if booking has audio guide
        if ($booking->has_audio_guide) {
            if (!$booking->vox_dynamic_link) {
                Log::warning('sendTicket 422: Missing audio guide link', ['booking_id' => $id]);
                return response()->json([
                    'success' => false,
                    'error' => 'Audio guide link is required. Please generate the PopGuide link first.',
                ], 422);
            }
        }

        $validated = $request->validate([
            'language' => 'sometimes|string',
            'attachment_ids' => 'sometimes|array',
            'attachment_ids.*' => 'integer|exists:message_attachments,id',
            'custom_subject' => 'sometimes|string|max:255',
            'custom_content' => 'sometimes|string|min:50',
        ]);

        $language = $validated['language'] ?? 'en';
        $attachmentIds = $validated['attachment_ids'] ?? [];
        $customMessage = null;

        Log::info('=== SEND TICKET: VALIDATED DATA ===', [
            'booking_id' => $id,
            'language' => $language,
            'attachment_ids' => $attachmentIds,
            'attachment_count' => count($attachmentIds),
        ]);

        // Handle custom message
        if ($language === 'custom') {
            if (empty($validated['custom_subject']) || empty($validated['custom_content'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Custom message requires both subject and content',
                ], 422);
            }
            $customMessage = [
                'subject' => $validated['custom_subject'],
                'content' => $validated['custom_content'],
            ];
        }

        // Validate at least one attachment
        if (empty($attachmentIds)) {
            Log::warning('sendTicket 422: No attachments', ['booking_id' => $id, 'attachment_ids' => $attachmentIds]);
            return response()->json([
                'success' => false,
                'error' => 'At least one PDF attachment is required',
            ], 422);
        }

        // CRITICAL: Verify all attachments belong to THIS booking
        $validAttachments = \App\Models\MessageAttachment::whereIn('id', $attachmentIds)
            ->where('booking_id', $booking->id)
            ->get();

        if ($validAttachments->count() !== count($attachmentIds)) {
            $invalidIds = array_diff($attachmentIds, $validAttachments->pluck('id')->toArray());
            Log::error('SECURITY: Attachment mismatch - possible wrong PDF!', [
                'booking_id' => $id,
                'requested_ids' => $attachmentIds,
                'valid_ids' => $validAttachments->pluck('id')->toArray(),
                'invalid_ids' => $invalidIds,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'One or more attachments do not belong to this booking. Please re-upload the correct PDF.',
            ], 422);
        }

        Log::info('Attachment validation passed', [
            'booking_id' => $id,
            'valid_attachment_ids' => $validAttachments->pluck('id')->toArray(),
            'filenames' => $validAttachments->pluck('original_name')->toArray(),
        ]);

        try {
            $result = $this->messagingService->sendTicket($booking, $language, $attachmentIds, $customMessage);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket sent successfully',
                    'channel_used' => $result['channel_used'],
                    'messages' => collect($result['messages'])->map(function ($msg) {
                        return [
                            'id' => $msg->id,
                            'channel' => $msg->channel,
                            'status' => $msg->status,
                            'recipient' => $msg->recipient,
                        ];
                    }),
                ]);
            }

            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to send ticket', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send ticket: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect which channel will be used
     * GET /api/bookings/{id}/detect-channel
     */
    public function detectChannel(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $channel = $this->messagingService->detectChannel($booking);

        return response()->json($channel);
    }

    /**
     * Preview message content with actual template text
     * POST /api/messages/preview
     *
     * Returns the actual WhatsApp and Email content that will be sent
     * in the selected language.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'language' => 'sometimes|string',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $language = $validated['language'] ?? 'en';
        $hasAudioGuide = $booking->has_audio_guide;

        // Channel detection
        $channel = $this->messagingService->detectChannel($booking);

        // Build template variables
        $name = $booking->customer_name ?? 'Guest';
        $dateTime = $booking->tour_date
            ? $booking->tour_date->format('F j, Y') . ' at ' . ($booking->tour_time ?? '10:00 AM')
            : 'Your scheduled time';
        $audioLink = $booking->vox_dynamic_link ?? 'https://popguide.me/xxx';
        $guideUrl = 'https://uffizi.florencewithlocals.com';
        $tipsUrl = 'https://uffizi.florencewithlocals.com/know-before-you-go';

        // WhatsApp template content (matches actual Twilio approved templates)
        $whatsappPreview = $this->getWhatsAppTemplateContent($language, $hasAudioGuide, $name, $dateTime, $audioLink, $guideUrl, $tipsUrl);

        // Email subject and type
        $emailSubjects = [
            'en' => ['audio' => 'Your Uffizi Gallery Tickets + Audio Guide', 'non_audio' => 'Your Uffizi Gallery Tickets'],
            'it' => ['audio' => 'I tuoi biglietti + Audioguida', 'non_audio' => 'I tuoi biglietti per la Galleria degli Uffizi'],
            'es' => ['audio' => 'Tus entradas + Audioguía', 'non_audio' => 'Tus entradas para la Galería Uffizi'],
            'de' => ['audio' => 'Ihre Eintrittskarten + Audioguide', 'non_audio' => 'Ihre Eintrittskarten für die Uffizien'],
            'fr' => ['audio' => 'Vos billets + Audioguide', 'non_audio' => 'Vos billets pour la Galerie des Offices'],
            'pt' => ['audio' => 'Seus ingressos + Audioguia', 'non_audio' => 'Seus ingressos para a Galeria Uffizi'],
            'ja' => ['audio' => 'チケット + オーディオガイド', 'non_audio' => 'ウフィツィ美術館のチケット'],
            'ko' => ['audio' => '입장권 + 오디오 가이드', 'non_audio' => '우피치 미술관 입장권'],
            'el' => ['audio' => 'Τα εισιτήριά σας + Ξενάγηση', 'non_audio' => 'Τα εισιτήριά σας για την Πινακοθήκη Ουφίτσι'],
            'tr' => ['audio' => 'Biletleriniz + Sesli Rehber', 'non_audio' => 'Uffizi Galerisi Biletleriniz'],
        ];

        $type = $hasAudioGuide ? 'audio' : 'non_audio';
        $emailSubject = $emailSubjects[$language][$type] ?? $emailSubjects['en'][$type];

        // SMS notification text
        $smsPreview = $this->getSmsNotificationText($language);

        return response()->json([
            'channel_detection' => $channel,
            'language' => $language,
            'has_audio_guide' => $hasAudioGuide,
            'whatsapp_preview' => $whatsappPreview,
            'email_subject' => $emailSubject,
            'email_type' => $hasAudioGuide ? 'Audio Guide Template' : 'Standard Ticket Template',
            'sms_preview' => $smsPreview,
        ]);
    }

    /**
     * Get WhatsApp template content for a specific language
     */
    private function getWhatsAppTemplateContent(
        string $language,
        bool $hasAudioGuide,
        string $name,
        string $dateTime,
        string $audioLink,
        string $guideUrl,
        string $tipsUrl
    ): string {
        // These match the actual Twilio approved templates
        $templates = [
            'en' => [
                'non_audio' => "🎫 *Your Uffizi Gallery Tickets*\n\nDear {$name},\n\nThank you for booking with Florence with Locals! Your tickets are attached.\n\n📅 *Entry:* {$dateTime}\n\n📍 Go to Door 01 at the Uffizi Gallery. Show your PDF ticket and proceed through security.\n\n⏰ Arrive 15 minutes early. Bring valid ID.\n\n🖼️ *Online Guide:* {$guideUrl}\n\n📖 *Tips:* {$tipsUrl}\n\nEnjoy your visit!\n— Florence with Locals",
                'audio' => "🎫🎧 *Your Uffizi Gallery Tickets + Audio Guide*\n\nDear {$name},\n\nThank you for booking with Florence with Locals! Your tickets are attached.\n\n📅 *Entry:* {$dateTime}\n\n📍 Go to Door 01 at the Uffizi Gallery.\n\n🎧 *Activate Audio Guide:* {$audioLink}\n\n📖 *Tips:* {$tipsUrl}\n\nEnjoy your visit!\n— Florence with Locals",
            ],
            'it' => [
                'non_audio' => "🎫 *I Tuoi Biglietti per gli Uffizi*\n\nGentile {$name},\n\nGrazie per aver prenotato con Florence with Locals! I tuoi biglietti sono in allegato.\n\n📅 *Ingresso:* {$dateTime}\n\n📍 Recati alla Porta 01 della Galleria degli Uffizi. Mostra il PDF e procedi ai controlli.\n\n⏰ Arriva 15 minuti prima. Porta un documento d'identità.\n\n🖼️ *Guida Online:* {$guideUrl}\n\n📖 *Consigli:* {$tipsUrl}\n\nBuona visita!\n— Florence with Locals",
                'audio' => "🎫🎧 *I Tuoi Biglietti + Audioguida*\n\nGentile {$name},\n\nGrazie per aver prenotato! I tuoi biglietti sono in allegato.\n\n📅 *Ingresso:* {$dateTime}\n\n📍 Recati alla Porta 01 degli Uffizi.\n\n🎧 *Attiva l'Audioguida:* {$audioLink}\n\n📖 *Consigli:* {$tipsUrl}\n\nBuona visita!\n— Florence with Locals",
            ],
            'es' => [
                'non_audio' => "🎫 *Tus Entradas para la Galería Uffizi*\n\nEstimado/a {$name},\n\nGracias por reservar con Florence with Locals. Tus entradas están adjuntas.\n\n📅 *Entrada:* {$dateTime}\n\n📍 Ve a la Puerta 01 de la Galería Uffizi.\n\n⏰ Llega 15 minutos antes.\n\n🖼️ *Guía Online:* {$guideUrl}\n\n📖 *Consejos:* {$tipsUrl}\n\n¡Disfruta tu visita!\n— Florence with Locals",
                'audio' => "🎫🎧 *Tus Entradas + Audioguía*\n\nEstimado/a {$name},\n\nTus entradas están adjuntas.\n\n📅 *Entrada:* {$dateTime}\n\n🎧 *Activa tu Audioguía:* {$audioLink}\n\n📖 *Consejos:* {$tipsUrl}\n\n¡Disfruta tu visita!\n— Florence with Locals",
            ],
            'de' => [
                'non_audio' => "🎫 *Ihre Uffizien-Tickets*\n\nLiebe/r {$name},\n\nVielen Dank für Ihre Buchung bei Florence with Locals! Ihre Tickets sind angehängt.\n\n📅 *Einlass:* {$dateTime}\n\n📍 Gehen Sie zu Eingang 01 der Uffizien-Galerie.\n\n⏰ Erscheinen Sie 15 Minuten früher.\n\n🖼️ *Online-Guide:* {$guideUrl}\n\n📖 *Tipps:* {$tipsUrl}\n\nGenießen Sie Ihren Besuch!\n— Florence with Locals",
                'audio' => "🎫🎧 *Ihre Tickets + Audioguide*\n\nLiebe/r {$name},\n\nIhre Tickets sind angehängt.\n\n📅 *Einlass:* {$dateTime}\n\n🎧 *Audioguide aktivieren:* {$audioLink}\n\n📖 *Tipps:* {$tipsUrl}\n\nGenießen Sie Ihren Besuch!\n— Florence with Locals",
            ],
            'fr' => [
                'non_audio' => "🎫 *Vos Billets pour les Offices*\n\nCher/Chère {$name},\n\nMerci d'avoir réservé avec Florence with Locals! Vos billets sont en pièce jointe.\n\n📅 *Entrée:* {$dateTime}\n\n📍 Rendez-vous à la Porte 01 de la Galerie des Offices.\n\n⏰ Arrivez 15 minutes à l'avance.\n\n🖼️ *Guide en ligne:* {$guideUrl}\n\n📖 *Conseils:* {$tipsUrl}\n\nBonne visite!\n— Florence with Locals",
                'audio' => "🎫🎧 *Vos Billets + Audioguide*\n\nCher/Chère {$name},\n\nVos billets sont en pièce jointe.\n\n📅 *Entrée:* {$dateTime}\n\n🎧 *Activez l'audioguide:* {$audioLink}\n\n📖 *Conseils:* {$tipsUrl}\n\nBonne visite!\n— Florence with Locals",
            ],
            'pt' => [
                'non_audio' => "🎫 *Seus Ingressos para a Galeria Uffizi*\n\nPrezado/a {$name},\n\nObrigado por reservar com Florence with Locals! Seus ingressos estão anexados.\n\n📅 *Entrada:* {$dateTime}\n\n📍 Vá até a Porta 01 da Galeria Uffizi.\n\n⏰ Chegue 15 minutos antes.\n\n🖼️ *Guia Online:* {$guideUrl}\n\n📖 *Dicas:* {$tipsUrl}\n\nAproveite sua visita!\n— Florence with Locals",
                'audio' => "🎫🎧 *Seus Ingressos + Audioguia*\n\nPrezado/a {$name},\n\nSeus ingressos estão anexados.\n\n📅 *Entrada:* {$dateTime}\n\n🎧 *Ative o Audioguia:* {$audioLink}\n\n📖 *Dicas:* {$tipsUrl}\n\nAproveite sua visita!\n— Florence with Locals",
            ],
            'ja' => [
                'non_audio' => "🎫 *ウフィツィ美術館のチケット*\n\n{$name} 様\n\nFlorence with Localsをご予約いただきありがとうございます。チケットを添付しました。\n\n📅 *入場:* {$dateTime}\n\n📍 ウフィツィ美術館の入口01へお越しください。\n\n⏰ 15分前にお越しください。\n\n🖼️ *オンラインガイド:* {$guideUrl}\n\n📖 *ヒント:* {$tipsUrl}\n\n素敵な訪問をお楽しみください！\n— Florence with Locals",
                'audio' => "🎫🎧 *チケット + オーディオガイド*\n\n{$name} 様\n\nチケットを添付しました。\n\n📅 *入場:* {$dateTime}\n\n🎧 *オーディオガイドを有効化:* {$audioLink}\n\n📖 *ヒント:* {$tipsUrl}\n\n素敵な訪問をお楽しみください！\n— Florence with Locals",
            ],
            'ko' => [
                'non_audio' => "🎫 *우피치 미술관 입장권*\n\n{$name} 님께\n\nFlorence with Locals를 예약해 주셔서 감사합니다! 티켓이 첨부되어 있습니다.\n\n📅 *입장:* {$dateTime}\n\n📍 우피치 미술관 1번 입구로 가세요.\n\n⏰ 15분 전에 도착하세요.\n\n🖼️ *온라인 가이드:* {$guideUrl}\n\n📖 *팁:* {$tipsUrl}\n\n즐거운 방문 되세요!\n— Florence with Locals",
                'audio' => "🎫🎧 *입장권 + 오디오 가이드*\n\n{$name} 님께\n\n티켓이 첨부되어 있습니다.\n\n📅 *입장:* {$dateTime}\n\n🎧 *오디오 가이드 활성화:* {$audioLink}\n\n📖 *팁:* {$tipsUrl}\n\n즐거운 방문 되세요!\n— Florence with Locals",
            ],
            'el' => [
                'non_audio' => "🎫 *Τα Εισιτήριά σας για την Πινακοθήκη Ουφίτσι*\n\nΑγαπητέ/ή {$name},\n\nΣας ευχαριστούμε για την κράτηση με Florence with Locals! Τα εισιτήριά σας επισυνάπτονται.\n\n📅 *Είσοδος:* {$dateTime}\n\n📍 Πηγαίνετε στην Πόρτα 01 της Πινακοθήκης Ουφίτσι.\n\n⏰ Φτάστε 15 λεπτά νωρίτερα.\n\n🖼️ *Online Ξενάγηση:* {$guideUrl}\n\n📖 *Συμβουλές:* {$tipsUrl}\n\nΚαλή επίσκεψη!\n— Florence with Locals",
                'audio' => "🎫🎧 *Τα Εισιτήριά σας + Ξενάγηση*\n\nΑγαπητέ/ή {$name},\n\nΤα εισιτήριά σας επισυνάπτονται.\n\n📅 *Είσοδος:* {$dateTime}\n\n🎧 *Ενεργοποιήστε την Ξενάγηση:* {$audioLink}\n\n📖 *Συμβουλές:* {$tipsUrl}\n\nΚαλή επίσκεψη!\n— Florence with Locals",
            ],
            'tr' => [
                'non_audio' => "🎫 *Uffizi Galerisi Biletleriniz*\n\nSayın {$name},\n\nFlorence with Locals ile rezervasyon yaptığınız için teşekkürler! Biletleriniz ektedir.\n\n📅 *Giriş:* {$dateTime}\n\n📍 Uffizi Galerisi'nin 01 numaralı kapısına gidin.\n\n⏰ 15 dakika erken gelin.\n\n🖼️ *Online Rehber:* {$guideUrl}\n\n📖 *İpuçları:* {$tipsUrl}\n\nİyi ziyaretler!\n— Florence with Locals",
                'audio' => "🎫🎧 *Biletleriniz + Sesli Rehber*\n\nSayın {$name},\n\nBiletleriniz ektedir.\n\n📅 *Giriş:* {$dateTime}\n\n🎧 *Sesli Rehberi Etkinleştirin:* {$audioLink}\n\n📖 *İpuçları:* {$tipsUrl}\n\nİyi ziyaretler!\n— Florence with Locals",
            ],
        ];

        $type = $hasAudioGuide ? 'audio' : 'non_audio';
        return $templates[$language][$type] ?? $templates['en'][$type];
    }

    /**
     * Get SMS notification text for a specific language
     */
    private function getSmsNotificationText(string $language): string
    {
        $smsTemplates = [
            'en' => "Your Uffizi Gallery tickets have been sent to your email. Please check your inbox and spam folder. - Florence with Locals",
            'it' => "I tuoi biglietti per la Galleria degli Uffizi sono stati inviati alla tua email. Controlla la posta in arrivo e lo spam. - Florence with Locals",
            'es' => "Tus entradas para la Galería Uffizi han sido enviadas a tu email. Revisa tu bandeja de entrada y spam. - Florence with Locals",
            'de' => "Ihre Uffizi-Galerie-Tickets wurden an Ihre E-Mail gesendet. Überprüfen Sie Ihren Posteingang und Spam-Ordner. - Florence with Locals",
            'fr' => "Vos billets pour la Galerie des Offices ont été envoyés à votre email. Vérifiez votre boîte de réception et spam. - Florence with Locals",
            'pt' => "Seus ingressos para a Galeria Uffizi foram enviados para seu email. Verifique sua caixa de entrada e spam. - Florence with Locals",
            'ja' => "ウフィツィ美術館のチケットをメールで送信しました。受信トレイと迷惑メールフォルダをご確認ください。- Florence with Locals",
            'ko' => "우피치 미술관 티켓이 이메일로 전송되었습니다. 받은편지함과 스팸 폴더를 확인해주세요. - Florence with Locals",
            'el' => "Τα εισιτήριά σας στάλθηκαν στο email σας. Ελέγξτε τα εισερχόμενα και τα spam. - Florence with Locals",
            'tr' => "Uffizi Galerisi biletleriniz e-postanıza gönderildi. Gelen kutunuzu ve spam klasörünü kontrol edin. - Florence with Locals",
        ];

        return $smsTemplates[$language] ?? $smsTemplates['en'];
    }

    /**
     * Get message history for a booking
     * GET /api/bookings/{id}/messages
     */
    public function history(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $messages = $this->messagingService->getHistory($booking);

        return response()->json([
            'messages' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'channel' => $msg->channel,
                    'recipient' => $msg->recipient,
                    'status' => $msg->status,
                    'content' => $msg->content,
                    'subject' => $msg->subject,
                    'error_message' => $msg->error_message,
                    'sent_at' => $msg->sent_at?->toIso8601String(),
                    'delivered_at' => $msg->delivered_at?->toIso8601String(),
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Get available templates
     * GET /api/messages/templates
     */
    public function templates(Request $request): JsonResponse
    {
        $channel = $request->query('channel');
        $language = $request->query('language');

        $query = MessageTemplate::where('is_active', true);

        if ($channel) {
            $query->where('channel', $channel);
        }

        if ($language) {
            $query->where('language', $language);
        }

        $templates = $query->orderBy('channel')
            ->orderBy('language')
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json([
            'templates' => $templates->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                    'channel' => $t->channel,
                    'language' => $t->language,
                    'subject' => $t->subject,
                    'content' => $t->content,
                    'is_default' => $t->is_default,
                ];
            }),
            'languages' => MessageTemplate::LANGUAGES,
            'channels' => MessageTemplate::CHANNELS,
        ]);
    }
}
