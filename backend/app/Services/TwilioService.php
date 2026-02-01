<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;

class TwilioService
{
    protected ?TwilioClient $client = null;
    protected string $accountSid;
    protected string $authToken;
    protected string $whatsappFrom;
    protected string $smsFrom;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->whatsappFrom = config('services.twilio.whatsapp_from');
        $this->smsFrom = config('services.twilio.sms_from');
    }

    /**
     * Get Twilio client instance
     */
    protected function getClient(): TwilioClient
    {
        if ($this->client === null) {
            if (empty($this->accountSid) || empty($this->authToken)) {
                throw new \RuntimeException('Twilio credentials not configured');
            }
            $this->client = new TwilioClient($this->accountSid, $this->authToken);
        }

        return $this->client;
    }

    /**
     * Check if phone number likely has WhatsApp
     *
     * NOTE: We cannot actually verify if a specific number has WhatsApp installed.
     * Twilio Lookup only tells us if it's a mobile number, not if WhatsApp is active.
     *
     * Strategy: Default to false (use Email+SMS) for safety.
     * WhatsApp is only assumed for countries where it's extremely common (EU, Americas).
     * For countries where other apps dominate (China=WeChat, Japan=Line), default to Email.
     */
    public function hasWhatsApp(string $phoneNumber): bool
    {
        $phone = $this->formatPhoneNumber($phoneNumber);

        // Countries where WhatsApp is NOT common (use Email+SMS instead)
        $nonWhatsAppCountries = [
            '+86',   // China (WeChat dominant)
            '+81',   // Japan (Line dominant)
            '+82',   // South Korea (KakaoTalk dominant)
            '+7',    // Russia (Telegram dominant)
            '+1',    // USA/Canada (SMS still common, iMessage)
        ];

        foreach ($nonWhatsAppCountries as $prefix) {
            if (str_starts_with($phone, $prefix)) {
                Log::info('WhatsApp skipped for country', ['phone' => $phone, 'prefix' => $prefix]);
                return false;
            }
        }

        // For European and other countries, try Twilio Lookup
        try {
            $client = $this->getClient();

            // Use Lookup API with line type intelligence
            $lookup = $client->lookups->v2->phoneNumbers($phone)
                ->fetch(['fields' => 'line_type_intelligence']);

            // Check if mobile (WhatsApp typically works on mobile in WhatsApp-common regions)
            $lineType = $lookup->lineTypeIntelligence['type'] ?? null;
            $mobileTypes = ['mobile', 'voip'];

            return in_array($lineType, $mobileTypes, true);

        } catch (TwilioException $e) {
            Log::warning('WhatsApp lookup failed, defaulting to Email+SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            // Default to false (Email+SMS) - safer than assuming WhatsApp works
            return false;
        }
    }

    /**
     * Get WhatsApp Content Template SID for a booking
     *
     * @param string $language Language code (en, it, es, etc.)
     * @param bool $hasAudioGuide Whether booking includes audio guide
     * @param bool $hasPdfAttachment Whether to use PDF-enabled template
     * @return string|null Content Template SID or null if not found
     */
    public function getWhatsAppTemplateSid(string $language, bool $hasAudioGuide, bool $hasPdfAttachment = true): ?string
    {
        // Use new PDF-enabled templates by default
        if ($hasPdfAttachment) {
            $templateType = $hasAudioGuide ? 'ticket_audio_pdf' : 'ticket_pdf';
        } else {
            // Legacy text-only templates
            $templateType = $hasAudioGuide ? 'ticket_with_audio' : 'ticket_only';
        }

        $templates = config("whatsapp_templates.{$templateType}", []);

        // Try requested language
        if (isset($templates[$language])) {
            return $templates[$language];
        }

        // Fall back to English
        $fallbackLanguage = config('whatsapp_templates.fallback_language', 'en');
        return $templates[$fallbackLanguage] ?? null;
    }

    /**
     * Build Content Template variables for WhatsApp
     *
     * @param Booking $booking The booking to build variables for
     * @param bool $hasAudioGuide Whether to include audio guide link
     * @param string|null $pdfUrl Pre-signed S3 URL for PDF attachment
     * @return array Variables in Twilio format ['1' => 'value', '2' => 'value', ...]
     */
    public function buildTemplateVariables(Booking $booking, bool $hasAudioGuide, ?string $pdfUrl = null): array
    {
        // Format entry datetime (e.g., "January 30, 2026 at 10:00 AM")
        $entryDatetime = $booking->tour_date
            ? $booking->tour_date->format('F j, Y') . ' at ' . ($booking->tour_time ?? '10:00 AM')
            : 'Your scheduled time';

        // Get URLs from config
        $onlineGuideUrl = config('whatsapp_templates.urls.online_guide', 'https://uffizi.florencewithlocals.com');
        $knowBeforeYouGoUrl = config('whatsapp_templates.urls.know_before_you_go', 'https://florencewithlocals.com/uffizi-know-before-you-go');

        // Base variables (1-4)
        $variables = [
            '1' => $booking->customer_name ?? 'Guest',
            '2' => $entryDatetime,
            '3' => $hasAudioGuide
                ? ($booking->vox_dynamic_link ?? $booking->audio_guide_url ?? $onlineGuideUrl)
                : $onlineGuideUrl,
            '4' => $knowBeforeYouGoUrl,
        ];

        // Add PDF URL as variable 5 if provided (for new media templates)
        if ($pdfUrl) {
            $variables['5'] = $pdfUrl;
        }

        return $variables;
    }

    /**
     * Send WhatsApp message using Content Templates with PDF attachment
     *
     * Uses new media templates that support PDF attachments via variable {{5}}
     */
    public function sendWhatsApp(
        Booking $booking,
        MessageTemplate $template,
        array $attachments = []
    ): Message {
        if (empty($booking->customer_phone)) {
            throw new \InvalidArgumentException('Booking has no customer phone');
        }

        $phone = $this->formatPhoneNumber($booking->customer_phone);
        $language = $template->language ?? 'en';
        $hasAudioGuide = $booking->has_audio_guide;

        // Generate PDF URL from first attachment (14-day expiry from config)
        $pdfUrl = null;
        $hasPdfAttachment = !empty($attachments);

        if ($hasPdfAttachment) {
            $firstAttachment = reset($attachments);
            if ($firstAttachment instanceof MessageAttachment) {
                // Uses 14-day default from config (whatsapp_templates.pdf_url_expiry_days)
                $pdfUrl = $firstAttachment->getTemporaryUrl();

                if (!$pdfUrl) {
                    Log::warning('Failed to generate PDF URL for WhatsApp', [
                        'attachment_id' => $firstAttachment->id,
                        'disk' => $firstAttachment->disk,
                        'path' => $firstAttachment->path,
                    ]);
                    // Fall back to non-PDF template
                    $hasPdfAttachment = false;
                }
            }
        }

        // Get Content Template SID (PDF-enabled or legacy based on attachment)
        $contentSid = $this->getWhatsAppTemplateSid($language, $hasAudioGuide, $hasPdfAttachment);

        if (!$contentSid) {
            throw new \RuntimeException("No WhatsApp Content Template found for language: {$language}");
        }

        // Build template variables (includes PDF URL as {{5}} if available)
        $contentVariables = $this->buildTemplateVariables($booking, $hasAudioGuide, $pdfUrl);

        // Get rendered content for logging/display
        $variables = $booking->getTemplateVariables();
        $renderedContent = $template->render($variables);

        // Create message record
        $message = Message::create([
            'booking_id' => $booking->id,
            'channel' => Message::CHANNEL_WHATSAPP,
            'recipient' => $phone,
            'content' => $renderedContent,
            'template_id' => $template->id,
            'template_variables' => $variables,
            'status' => Message::STATUS_PENDING,
        ]);

        // Associate attachments with message
        foreach ($attachments as $attachment) {
            if ($attachment instanceof MessageAttachment) {
                $attachment->update(['message_id' => $message->id]);
            }
        }

        Log::info('WhatsApp message prepared', [
            'booking_id' => $booking->id,
            'attachments_count' => count($attachments),
            'has_pdf_url' => !empty($pdfUrl),
            'template_type' => $hasPdfAttachment ? 'media_with_pdf' : 'text_only',
        ]);

        try {
            $message->markQueued();

            $client = $this->getClient();

            $options = [
                'from' => "whatsapp:{$this->whatsappFrom}",
                'contentSid' => $contentSid,
                'contentVariables' => json_encode($contentVariables),
            ];

            // Add status callback
            if (config('services.twilio.status_callback_url')) {
                $options['statusCallback'] = config('services.twilio.status_callback_url');
            }

            Log::info('Sending WhatsApp with Media Template', [
                'booking_id' => $booking->id,
                'phone' => $phone,
                'content_sid' => $contentSid,
                'language' => $language,
                'has_audio_guide' => $hasAudioGuide,
                'has_pdf_attachment' => $hasPdfAttachment,
                'pdf_url_preview' => $pdfUrl ? substr($pdfUrl, 0, 80) . '...' : null,
            ]);

            // Send via Twilio
            $twilioMessage = $client->messages->create(
                "whatsapp:{$phone}",
                $options
            );

            $message->markSent($twilioMessage->sid);

            Log::info('WhatsApp sent successfully with PDF', [
                'booking_id' => $booking->id,
                'phone' => $phone,
                'message_id' => $message->id,
                'twilio_sid' => $twilioMessage->sid,
                'pdf_included' => $hasPdfAttachment,
            ]);

            return $message;

        } catch (TwilioException $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send WhatsApp', [
                'booking_id' => $booking->id,
                'phone' => $phone,
                'content_sid' => $contentSid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send SMS message
     */
    public function sendSms(
        Booking $booking,
        MessageTemplate $template
    ): Message {
        if (empty($booking->customer_phone)) {
            throw new \InvalidArgumentException('Booking has no customer phone');
        }

        $phone = $this->formatPhoneNumber($booking->customer_phone);
        $variables = $booking->getTemplateVariables();

        // Create message record
        $message = Message::create([
            'booking_id' => $booking->id,
            'channel' => Message::CHANNEL_SMS,
            'recipient' => $phone,
            'content' => $template->render($variables),
            'template_id' => $template->id,
            'template_variables' => $variables,
            'status' => Message::STATUS_PENDING,
        ]);

        try {
            $message->markQueued();

            $client = $this->getClient();

            // Build message options
            $options = [
                'from' => $this->smsFrom,
                'body' => $message->content,
            ];

            // Add status callback
            if (config('services.twilio.status_callback_url')) {
                $options['statusCallback'] = config('services.twilio.status_callback_url');
            }

            // Send via Twilio
            $twilioMessage = $client->messages->create($phone, $options);

            $message->markSent($twilioMessage->sid);

            Log::info('SMS sent successfully', [
                'booking_id' => $booking->id,
                'phone' => $phone,
                'message_id' => $message->id,
                'twilio_sid' => $twilioMessage->sid,
            ]);

            return $message;

        } catch (TwilioException $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send SMS', [
                'booking_id' => $booking->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle Twilio status callback
     */
    public function handleStatusCallback(array $data): void
    {
        $sid = $data['MessageSid'] ?? null;
        $status = $data['MessageStatus'] ?? null;

        if (!$sid || !$status) {
            return;
        }

        $message = Message::where('external_id', $sid)->first();

        if (!$message) {
            Log::warning('Twilio callback for unknown message', ['sid' => $sid]);
            return;
        }

        switch (strtolower($status)) {
            case 'sent':
                // Already marked as sent when we got the SID
                break;

            case 'delivered':
                $message->markDelivered();
                break;

            case 'read':
                $message->markRead();
                break;

            case 'failed':
            case 'undelivered':
                $errorCode = $data['ErrorCode'] ?? 'Unknown';
                $errorMessage = $data['ErrorMessage'] ?? "Status: {$status}";
                $message->markFailed("Error {$errorCode}: {$errorMessage}");
                break;
        }

        Log::info('Twilio status callback processed', [
            'sid' => $sid,
            'status' => $status,
            'message_id' => $message->id,
        ]);
    }

    /**
     * Format phone number to E.164 format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with +
        if (!str_starts_with($phone, '+')) {
            // Assume it's missing the country code
            // This is a simple heuristic - might need adjustment
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Simple heuristic to check if number looks like mobile
     */
    protected function looksLikeMobile(string $phone): bool
    {
        // This is a very basic check
        // Most international mobile numbers have 10+ digits
        $digits = preg_replace('/\D/', '', $phone);
        return strlen($digits) >= 10;
    }

    /**
     * Preview WhatsApp message without sending
     * Returns preview with Content Template variables
     */
    public function previewWhatsApp(
        Booking $booking,
        MessageTemplate $template
    ): array {
        $variables = $booking->getTemplateVariables();
        $hasAudioGuide = $booking->has_audio_guide;
        $language = $template->language ?? 'en';

        // Get the content template SID and variables for reference
        $contentSid = $this->getWhatsAppTemplateSid($language, $hasAudioGuide);
        $contentVariables = $this->buildTemplateVariables($booking, $hasAudioGuide);

        return [
            'content' => $template->render($variables),
            'recipient' => $this->formatPhoneNumber($booking->customer_phone ?? ''),
            'channel' => 'whatsapp',
            'content_template_sid' => $contentSid,
            'content_variables' => $contentVariables,
        ];
    }

    /**
     * Preview SMS message without sending
     */
    public function previewSms(
        Booking $booking,
        MessageTemplate $template
    ): array {
        $variables = $booking->getTemplateVariables();

        return [
            'content' => $template->render($variables),
            'recipient' => $this->formatPhoneNumber($booking->customer_phone ?? ''),
            'channel' => 'sms',
        ];
    }

    /**
     * Send a reply message in a conversation
     */
    public function sendReply(Conversation $conversation, string $content): Message
    {
        $phone = $conversation->phone_number;

        // Create message record
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'booking_id' => $conversation->booking_id,
            'channel' => $conversation->channel,
            'direction' => Message::DIRECTION_OUTBOUND,
            'recipient' => $phone,
            'content' => $content,
            'status' => Message::STATUS_PENDING,
        ]);

        try {
            $message->markQueued();

            $client = $this->getClient();

            // Build message options based on channel
            // Note: Replies within 24-hour window can use free-form body (no template required)
            if ($conversation->channel === Conversation::CHANNEL_WHATSAPP) {
                $options = [
                    'from' => "whatsapp:{$this->whatsappFrom}",
                    'body' => $content,
                ];

                $to = "whatsapp:{$phone}";
            } else {
                // SMS
                $options = [
                    'from' => $this->smsFrom,
                    'body' => $content,
                ];

                $to = $phone;
            }

            // Add status callback
            if (config('services.twilio.status_callback_url')) {
                $options['statusCallback'] = config('services.twilio.status_callback_url');
            }

            // Send via Twilio
            $twilioMessage = $client->messages->create($to, $options);

            $message->markSent($twilioMessage->sid);

            // Update conversation last message time
            $conversation->update(['last_message_at' => now()]);

            Log::info('Reply sent successfully', [
                'conversation_id' => $conversation->id,
                'phone' => $phone,
                'channel' => $conversation->channel,
                'message_id' => $message->id,
                'twilio_sid' => $twilioMessage->sid,
            ]);

            return $message;

        } catch (TwilioException $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send reply', [
                'conversation_id' => $conversation->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
