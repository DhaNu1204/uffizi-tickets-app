<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketEmail;
use App\Mail\TicketMail;

class EmailService
{
    /**
     * Send ticket email to customer
     */
    public function sendTicketEmail(
        Booking $booking,
        MessageTemplate $template,
        array $attachments = []
    ): Message {
        // Validate booking has email
        if (empty($booking->customer_email)) {
            throw new \InvalidArgumentException('Booking has no customer email');
        }

        $variables = $booking->getTemplateVariables();

        // Create message record
        $message = Message::create([
            'booking_id' => $booking->id,
            'channel' => Message::CHANNEL_EMAIL,
            'recipient' => $booking->customer_email,
            'subject' => $template->renderSubject($variables),
            'content' => $template->render($variables),
            'template_id' => $template->id,
            'template_variables' => $variables,
            'status' => Message::STATUS_PENDING,
        ]);

        // Associate attachments
        foreach ($attachments as $attachment) {
            if ($attachment instanceof MessageAttachment) {
                $attachment->update(['message_id' => $message->id]);
            }
        }

        try {
            // Queue the email
            $message->markQueued();

            // Prepare attachment paths
            $attachmentPaths = [];
            foreach ($message->attachments as $attachment) {
                if ($attachment->exists()) {
                    $attachmentPaths[] = [
                        'path' => $attachment->getFullPath(),
                        'disk' => $attachment->disk,
                        'name' => $attachment->original_name,
                        'mime' => $attachment->mime_type,
                    ];
                }
            }

            // Send email
            Mail::to($booking->customer_email)
                ->send(new TicketEmail(
                    subject: $message->subject,
                    content: $message->content,
                    attachments: $attachmentPaths,
                    booking: $booking
                ));

            $message->markSent();

            Log::info('Email sent successfully', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
                'message_id' => $message->id,
            ]);

            return $message;

        } catch (\Exception $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send email', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Preview email without sending
     */
    public function preview(
        Booking $booking,
        MessageTemplate $template
    ): array {
        $variables = $booking->getTemplateVariables();

        return [
            'subject' => $template->renderSubject($variables),
            'content' => $template->render($variables),
            'recipient' => $booking->customer_email,
            'channel' => 'email',
        ];
    }

    /**
     * Send beautiful HTML ticket email using Blade templates
     *
     * This uses the new TicketMail Mailable with Tuscan-inspired design
     *
     * @param Booking $booking The booking to send tickets for
     * @param string $language Language code (en, it, es, de, fr, pt, ja, ko, el, tr)
     * @param array $attachments MessageAttachment models to include
     * @return Message The created message record
     */
    public function sendBeautifulTicketEmail(
        Booking $booking,
        string $language = 'en',
        array $attachments = []
    ): Message {
        // Validate booking has email
        if (empty($booking->customer_email)) {
            throw new \InvalidArgumentException('Booking has no customer email');
        }

        // Determine subject based on language and audio guide
        $type = $booking->has_audio_guide ? 'audio' : 'non_audio';
        $subjects = [
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
        $subject = $subjects[$language][$type] ?? $subjects['en'][$type];

        // Create message record
        $message = Message::create([
            'booking_id' => $booking->id,
            'channel' => Message::CHANNEL_EMAIL,
            'recipient' => $booking->customer_email,
            'subject' => $subject,
            'content' => "Beautiful HTML email ({$language}, " . ($booking->has_audio_guide ? 'audio' : 'non-audio') . ")",
            'template_id' => null,
            'template_variables' => [
                'language' => $language,
                'has_audio_guide' => $booking->has_audio_guide,
            ],
            'status' => Message::STATUS_PENDING,
        ]);

        // Associate attachments
        foreach ($attachments as $attachment) {
            if ($attachment instanceof MessageAttachment) {
                $attachment->update(['message_id' => $message->id]);
            }
        }

        try {
            // Queue the email
            $message->markQueued();

            // Prepare attachment paths
            $attachmentPaths = [];
            foreach ($message->attachments as $attachment) {
                if ($attachment->exists()) {
                    $attachmentPaths[] = [
                        'path' => $attachment->getFullPath(),
                        'disk' => $attachment->disk,
                        'name' => $attachment->original_name,
                        'mime' => $attachment->mime_type,
                    ];
                }
            }

            // Send using beautiful template
            Mail::to($booking->customer_email)
                ->send(new TicketMail(
                    booking: $booking,
                    language: $language,
                    attachmentFiles: $attachmentPaths
                ));

            $message->markSent();

            Log::info('Beautiful email sent successfully', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
                'message_id' => $message->id,
                'language' => $language,
                'has_audio_guide' => $booking->has_audio_guide,
            ]);

            return $message;

        } catch (\Exception $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send beautiful email', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
                'language' => $language,
                'error' => $e->getMessage(),
            ]);

            // Return the failed message instead of throwing
            // This allows MessagingService to handle partial failures gracefully
            return $message;
        }
    }
}
