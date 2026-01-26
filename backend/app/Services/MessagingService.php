<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class MessagingService
{
    protected EmailService $emailService;
    protected TwilioService $twilioService;

    public function __construct(
        EmailService $emailService,
        TwilioService $twilioService
    ) {
        $this->emailService = $emailService;
        $this->twilioService = $twilioService;
    }

    /**
     * Send ticket to customer using best available channel
     *
     * Logic:
     * 1. If customer has phone, check WhatsApp availability
     * 2. If WhatsApp available: Send via WhatsApp only
     * 3. If no WhatsApp: Send email + SMS notification
     * 4. If no phone: Send email only
     *
     * @param Booking $booking
     * @param string $language Language code or 'custom' for custom message
     * @param array $attachmentIds IDs of attachments to include
     * @param array|null $customMessage Optional custom message ['subject' => '...', 'content' => '...']
     */
    public function sendTicket(
        Booking $booking,
        string $language = 'en',
        array $attachmentIds = [],
        ?array $customMessage = null
    ): array {
        $results = [
            'success' => false,
            'messages' => [],
            'errors' => [],
            'channel_used' => null,
        ];

        // Validate booking
        if (!$booking->reference_number) {
            $results['errors'][] = 'Booking has no ticket reference number';
            return $results;
        }

        // Load attachments
        $attachments = MessageAttachment::whereIn('id', $attachmentIds)
            ->where('booking_id', $booking->id)
            ->get();

        // Determine which channel to use
        $hasPhone = !empty($booking->customer_phone);
        $hasEmail = !empty($booking->customer_email);
        $hasWhatsApp = false;

        if ($hasPhone) {
            try {
                $hasWhatsApp = $this->twilioService->hasWhatsApp($booking->customer_phone);
            } catch (\Exception $e) {
                Log::warning('WhatsApp check failed, assuming available', [
                    'error' => $e->getMessage(),
                ]);
                $hasWhatsApp = true; // Default to trying WhatsApp
            }
        }

        // Check if using custom message
        $isCustomMessage = $language === 'custom' && $customMessage !== null;

        // Send based on channel availability
        if ($hasPhone && $hasWhatsApp) {
            // WhatsApp available - use WhatsApp only
            $results['channel_used'] = 'whatsapp';

            try {
                if ($isCustomMessage) {
                    // Create a fake template with custom content
                    $template = new MessageTemplate([
                        'channel' => 'whatsapp',
                        'subject' => $customMessage['subject'],
                        'content' => $customMessage['content'],
                        'language' => 'custom',
                    ]);
                } else {
                    $template = $this->getTemplate('whatsapp', $language, $booking->has_audio_guide);
                }
                $message = $this->twilioService->sendWhatsApp($booking, $template, $attachments->all());
                $results['messages'][] = $message;
                $results['success'] = true;
            } catch (\Exception $e) {
                $results['errors'][] = "WhatsApp failed: {$e->getMessage()}";
            }

        } elseif ($hasEmail) {
            // No WhatsApp - use Email + SMS
            $results['channel_used'] = 'email_sms';

            // Send email with PDF
            try {
                if ($isCustomMessage) {
                    // Create a fake template with custom content
                    $emailTemplate = new MessageTemplate([
                        'channel' => 'email',
                        'subject' => $customMessage['subject'],
                        'content' => $customMessage['content'],
                        'language' => 'custom',
                    ]);
                } else {
                    $emailTemplate = $this->getTemplate('email', $language, $booking->has_audio_guide);
                }
                $message = $this->emailService->sendTicketEmail($booking, $emailTemplate, $attachments->all());
                $results['messages'][] = $message;
                $results['success'] = true;
            } catch (\Exception $e) {
                $results['errors'][] = "Email failed: {$e->getMessage()}";
            }

            // Send SMS notification if phone available (always uses standard template)
            if ($hasPhone) {
                try {
                    $smsTemplate = $this->getTemplate('sms', $isCustomMessage ? 'en' : $language, false); // SMS never includes audio guide details
                    $message = $this->twilioService->sendSms($booking, $smsTemplate);
                    $results['messages'][] = $message;
                } catch (\Exception $e) {
                    $results['errors'][] = "SMS failed: {$e->getMessage()}";
                    // Don't fail the whole operation if SMS fails
                }
            }

        } else {
            $results['errors'][] = 'No contact information available (no phone or email)';
        }

        // Update booking if successful
        if ($results['success']) {
            $booking->update(['tickets_sent_at' => now()]);

            if ($booking->has_audio_guide) {
                $booking->update(['audio_guide_sent_at' => now()]);
            }
        }

        return $results;
    }

    /**
     * Get appropriate template for channel and language
     */
    protected function getTemplate(string $channel, string $language, bool $hasAudioGuide): MessageTemplate
    {
        // Determine template type based on whether audio guide is included
        $templateType = $hasAudioGuide
            ? MessageTemplate::TYPE_TICKET_WITH_AUDIO
            : MessageTemplate::TYPE_TICKET_ONLY;

        // Try to find template by language and type
        $template = MessageTemplate::getByLanguageAndType($language, $templateType, $channel);

        // Fall back to English template
        if (!$template) {
            $template = MessageTemplate::getByLanguageAndType('en', $templateType, $channel);
        }

        // Fall back to default for channel
        if (!$template) {
            $template = MessageTemplate::getDefault($channel, $language);
        }

        // Ultimate fallback to English default
        if (!$template) {
            $template = MessageTemplate::getDefault($channel, 'en');
        }

        if (!$template) {
            throw new \RuntimeException("No template found for channel: {$channel}, language: {$language}");
        }

        return $template;
    }

    /**
     * Detect which channel will be used for a booking
     */
    public function detectChannel(Booking $booking): array
    {
        $hasPhone = !empty($booking->customer_phone);
        $hasEmail = !empty($booking->customer_email);
        $hasWhatsApp = false;

        if ($hasPhone) {
            try {
                $hasWhatsApp = $this->twilioService->hasWhatsApp($booking->customer_phone);
            } catch (\Exception $e) {
                Log::warning('WhatsApp check failed', ['error' => $e->getMessage()]);
            }
        }

        if ($hasPhone && $hasWhatsApp) {
            return [
                'primary' => 'whatsapp',
                'fallback' => null,
                'description' => 'Will send via WhatsApp',
            ];
        }

        if ($hasEmail && $hasPhone) {
            return [
                'primary' => 'email',
                'fallback' => 'sms',
                'description' => 'Will send via Email + SMS notification',
            ];
        }

        if ($hasEmail) {
            return [
                'primary' => 'email',
                'fallback' => null,
                'description' => 'Will send via Email only',
            ];
        }

        return [
            'primary' => null,
            'fallback' => null,
            'description' => 'No contact information available',
        ];
    }

    /**
     * Preview message content for all channels
     */
    public function preview(
        Booking $booking,
        string $language = 'en'
    ): array {
        $previews = [];
        $hasAudioGuide = $booking->has_audio_guide;

        // WhatsApp preview
        try {
            $template = $this->getTemplate('whatsapp', $language, $hasAudioGuide);
            $previews['whatsapp'] = $this->twilioService->previewWhatsApp($booking, $template);
        } catch (\Exception $e) {
            $previews['whatsapp'] = ['error' => $e->getMessage()];
        }

        // Email preview
        try {
            $template = $this->getTemplate('email', $language, $hasAudioGuide);
            $previews['email'] = $this->emailService->preview($booking, $template);
        } catch (\Exception $e) {
            $previews['email'] = ['error' => $e->getMessage()];
        }

        // SMS preview
        try {
            $template = $this->getTemplate('sms', $language, false);
            $previews['sms'] = $this->twilioService->previewSms($booking, $template);
        } catch (\Exception $e) {
            $previews['sms'] = ['error' => $e->getMessage()];
        }

        return $previews;
    }

    /**
     * Get message history for a booking
     */
    public function getHistory(Booking $booking): Collection
    {
        return $booking->messages()
            ->with('template')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get available templates grouped by channel and language
     */
    public function getAvailableTemplates(): array
    {
        $templates = MessageTemplate::where('is_active', true)
            ->orderBy('channel')
            ->orderBy('language')
            ->orderBy('is_default', 'desc')
            ->get();

        $grouped = [];
        foreach ($templates as $template) {
            $grouped[$template->channel][$template->language][] = $template;
        }

        return $grouped;
    }
}
