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
     * Send ticket to customer using dual delivery (WhatsApp + Email)
     *
     * NEW Logic (Dual Delivery):
     * 1. If customer has WhatsApp AND email: Send BOTH WhatsApp and Email
     * 2. If WhatsApp only (no email): Send WhatsApp only
     * 3. If Email only (no WhatsApp): Send Email + SMS notification
     * 4. If phone only (no WhatsApp, no email): Cannot send (SMS can't carry PDF)
     * 5. If no contact info: Return error
     *
     * @param Booking $booking
     * @param string $language Language code or 'custom' for custom message
     * @param array $attachmentIds IDs of attachments to include
     * @param array|null $customMessage Optional custom message ['subject' => '...', 'content' => '...']
     * @param string|null $forceChannel Force a specific channel for testing ('whatsapp', 'email', 'email_sms', 'whatsapp_email')
     */
    public function sendTicket(
        Booking $booking,
        string $language = 'en',
        array $attachmentIds = [],
        ?array $customMessage = null,
        ?string $forceChannel = null
    ): array {
        $results = [
            'success' => false,
            'messages' => [],
            'errors' => [],
            'channel_used' => null,
            'channels' => [], // Track multiple channels for dual delivery
            'channel_status' => [], // Per-channel success/failure status
        ];

        // Validate booking
        if (!$booking->reference_number) {
            $results['errors'][] = 'Booking has no ticket reference number';
            return $results;
        }

        // Load attachments - CRITICAL: Filter by booking_id to prevent wrong PDF bug
        $attachments = MessageAttachment::whereIn('id', $attachmentIds)
            ->where('booking_id', $booking->id)
            ->get();

        Log::info('=== MESSAGING SERVICE: ATTACHMENTS LOADED ===', [
            'booking_id' => $booking->id,
            'requested_ids' => $attachmentIds,
            'loaded_count' => $attachments->count(),
            'loaded_ids' => $attachments->pluck('id')->toArray(),
            'filenames' => $attachments->pluck('original_name')->toArray(),
        ]);

        // CRITICAL: Validate all requested attachments were found for this booking
        if ($attachments->count() !== count($attachmentIds)) {
            $invalidIds = array_diff($attachmentIds, $attachments->pluck('id')->toArray());
            Log::error('CRITICAL: Attachment mismatch - some attachments do not belong to this booking!', [
                'booking_id' => $booking->id,
                'requested_ids' => $attachmentIds,
                'found_ids' => $attachments->pluck('id')->toArray(),
                'invalid_ids' => $invalidIds,
            ]);
            $results['errors'][] = 'Attachment does not belong to this booking. Please re-upload the correct PDF.';
            return $results;
        }

        if ($attachments->isEmpty()) {
            Log::error('No valid attachments found for booking', [
                'booking_id' => $booking->id,
                'requested_ids' => $attachmentIds,
            ]);
            $results['errors'][] = 'No valid attachments found for this booking.';
            return $results;
        }

        // Determine channel availability
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

        Log::info('=== DUAL DELIVERY: Channel availability ===', [
            'booking_id' => $booking->id,
            'has_phone' => $hasPhone,
            'has_email' => $hasEmail,
            'has_whatsapp' => $hasWhatsApp,
            'language' => $language,
            'force_channel' => $forceChannel,
        ]);

        // ============================================================
        // Force channel override for testing
        // ============================================================
        $useWhatsAppEmail = $hasPhone && $hasWhatsApp && $hasEmail;
        $useWhatsAppOnly = $hasPhone && $hasWhatsApp && !$hasEmail;
        $useEmailSms = $hasEmail && $hasPhone && !$hasWhatsApp;
        $useEmailOnly = $hasEmail && !$hasPhone;
        $usePhoneOnly = $hasPhone && !$hasWhatsApp && !$hasEmail;

        if ($forceChannel) {
            Log::info('=== FORCE CHANNEL OVERRIDE ===', [
                'booking_id' => $booking->id,
                'force_channel' => $forceChannel,
            ]);

            switch ($forceChannel) {
                case 'whatsapp_email':
                    $useWhatsAppEmail = $hasPhone && $hasEmail;
                    $useWhatsAppOnly = false;
                    $useEmailSms = false;
                    $useEmailOnly = false;
                    $usePhoneOnly = false;
                    break;
                case 'whatsapp':
                    $useWhatsAppEmail = false;
                    $useWhatsAppOnly = $hasPhone;
                    $useEmailSms = false;
                    $useEmailOnly = false;
                    $usePhoneOnly = false;
                    break;
                case 'email_sms':
                    $useWhatsAppEmail = false;
                    $useWhatsAppOnly = false;
                    $useEmailSms = $hasEmail && $hasPhone;
                    $useEmailOnly = false;
                    $usePhoneOnly = false;
                    break;
                case 'email':
                    $useWhatsAppEmail = false;
                    $useWhatsAppOnly = false;
                    $useEmailSms = false;
                    $useEmailOnly = $hasEmail;
                    $usePhoneOnly = false;
                    break;
            }
        }

        // ============================================================
        // DUAL DELIVERY: WhatsApp + Email when both are available
        // ============================================================
        if ($useWhatsAppEmail) {
            $results['channel_used'] = 'whatsapp_email'; // Dual delivery
            $results['channels'] = ['whatsapp', 'email'];

            Log::info('=== DUAL DELIVERY: Sending WhatsApp + Email ===', [
                'booking_id' => $booking->id,
            ]);

            // 1. Send WhatsApp
            try {
                if ($isCustomMessage) {
                    $template = new MessageTemplate([
                        'channel' => 'whatsapp',
                        'subject' => $customMessage['subject'],
                        'content' => $customMessage['content'],
                        'language' => 'custom',
                    ]);
                } else {
                    $template = $this->getTemplate('whatsapp', $language, $booking->has_audio_guide);
                }

                Log::info('=== SENDING WHATSAPP WITH PDF ===', [
                    'booking_id' => $booking->id,
                    'language' => $language,
                    'has_audio_guide' => $booking->has_audio_guide,
                ]);

                $whatsappMessage = $this->twilioService->sendWhatsApp($booking, $template, $attachments->all());
                $results['messages'][] = $whatsappMessage;
                $results['success'] = true;
                $results['channel_status']['whatsapp'] = [
                    'success' => true,
                    'status' => $whatsappMessage->status,
                    'recipient' => $whatsappMessage->recipient,
                    'error' => null,
                ];

                Log::info('WhatsApp sent successfully', [
                    'booking_id' => $booking->id,
                    'message_id' => $whatsappMessage->id,
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = "WhatsApp failed: {$e->getMessage()}";
                $results['channel_status']['whatsapp'] = [
                    'success' => false,
                    'status' => 'failed',
                    'recipient' => $booking->customer_phone,
                    'error' => $e->getMessage(),
                ];
                Log::error('WhatsApp send failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 2. Send Email (beautiful HTML template)
            Log::info('=== SENDING BEAUTIFUL EMAIL ===', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
                'language' => $isCustomMessage ? 'en' : $language,
            ]);

            $emailMessage = $this->emailService->sendBeautifulTicketEmail(
                $booking,
                $isCustomMessage ? 'en' : $language,
                $attachments->all()
            );
            $results['messages'][] = $emailMessage;

            // Check if email succeeded or failed (EmailService now returns message instead of throwing)
            if ($emailMessage->status === Message::STATUS_FAILED) {
                $results['errors'][] = "Email failed: {$emailMessage->error_message}";
                $results['channel_status']['email'] = [
                    'success' => false,
                    'status' => 'failed',
                    'recipient' => $booking->customer_email,
                    'error' => $emailMessage->error_message,
                ];
                Log::error('Email send failed', [
                    'booking_id' => $booking->id,
                    'error' => $emailMessage->error_message,
                ]);
            } else {
                $results['success'] = true;
                $results['channel_status']['email'] = [
                    'success' => true,
                    'status' => $emailMessage->status,
                    'recipient' => $booking->customer_email,
                    'error' => null,
                ];
                Log::info('Email sent successfully', [
                    'booking_id' => $booking->id,
                    'message_id' => $emailMessage->id,
                ]);
            }

        }
        // ============================================================
        // WhatsApp only (no email available)
        // ============================================================
        elseif ($useWhatsAppOnly) {
            $results['channel_used'] = 'whatsapp';
            $results['channels'] = ['whatsapp'];

            try {
                if ($isCustomMessage) {
                    $template = new MessageTemplate([
                        'channel' => 'whatsapp',
                        'subject' => $customMessage['subject'],
                        'content' => $customMessage['content'],
                        'language' => 'custom',
                    ]);
                } else {
                    $template = $this->getTemplate('whatsapp', $language, $booking->has_audio_guide);
                }

                Log::info('=== SENDING WHATSAPP WITH PDF ===', [
                    'booking_id' => $booking->id,
                    'template_id' => $template->id ?? 'dynamic',
                    'language' => $language,
                    'has_audio_guide' => $booking->has_audio_guide,
                    'attachments_count' => $attachments->count(),
                    'attachment_ids' => $attachments->pluck('id')->toArray(),
                ]);

                $message = $this->twilioService->sendWhatsApp($booking, $template, $attachments->all());
                $results['messages'][] = $message;
                $results['success'] = true;
                $results['channel_status']['whatsapp'] = [
                    'success' => true,
                    'status' => $message->status,
                    'recipient' => $message->recipient,
                    'error' => null,
                ];

                Log::info('WhatsApp with PDF sent successfully', [
                    'booking_id' => $booking->id,
                    'message_id' => $message->id,
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = "WhatsApp failed: {$e->getMessage()}";
                $results['channel_status']['whatsapp'] = [
                    'success' => false,
                    'status' => 'failed',
                    'recipient' => $booking->customer_phone,
                    'error' => $e->getMessage(),
                ];
                Log::error('WhatsApp send failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

        }
        // ============================================================
        // Email + SMS (no WhatsApp but has phone and email)
        // ============================================================
        elseif ($useEmailSms) {
            // No WhatsApp - use Email + SMS notification
            $results['channel_used'] = 'email_sms';
            $results['channels'] = ['email', 'sms'];

            Log::info('=== SENDING EMAIL + SMS NOTIFICATION ===', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
                'phone' => $booking->customer_phone,
                'language' => $isCustomMessage ? 'en' : $language,
            ]);

            // 1. Send beautiful email with PDF tickets
            $emailMessage = $this->emailService->sendBeautifulTicketEmail(
                $booking,
                $isCustomMessage ? 'en' : $language,
                $attachments->all()
            );
            $results['messages'][] = $emailMessage;

            // Check if email succeeded or failed
            if ($emailMessage->status === Message::STATUS_FAILED) {
                $results['errors'][] = "Email failed: {$emailMessage->error_message}";
                $results['channel_status']['email'] = [
                    'success' => false,
                    'status' => 'failed',
                    'recipient' => $booking->customer_email,
                    'error' => $emailMessage->error_message,
                ];
                Log::error('Email send failed', [
                    'booking_id' => $booking->id,
                    'error' => $emailMessage->error_message,
                ]);
            } else {
                $results['success'] = true;
                $results['channel_status']['email'] = [
                    'success' => true,
                    'status' => $emailMessage->status,
                    'recipient' => $booking->customer_email,
                    'error' => null,
                ];
                Log::info('Email sent successfully', [
                    'booking_id' => $booking->id,
                    'message_id' => $emailMessage->id,
                ]);
            }

            // 2. Send SMS notification (short text, no PDF)
            $smsLanguage = $isCustomMessage ? 'en' : $language;
            $smsMessages = config('sms_notifications.ticket_email_notification');
            $smsBody = $smsMessages[$smsLanguage]
                ?? $smsMessages[config('sms_notifications.fallback_language', 'en')]
                ?? $smsMessages['en'];

            $smsMessage = $this->twilioService->sendSmsNotification($booking, $smsBody, $smsLanguage);
            $results['messages'][] = $smsMessage;

            // Check if SMS succeeded or failed (sendSmsNotification returns message, doesn't throw)
            if ($smsMessage->status === Message::STATUS_FAILED) {
                $results['errors'][] = "SMS notification failed: {$smsMessage->error_message}";
                $results['channel_status']['sms'] = [
                    'success' => false,
                    'status' => 'failed',
                    'recipient' => $booking->customer_phone,
                    'error' => $smsMessage->error_message,
                ];
                Log::warning('SMS notification failed (non-critical)', [
                    'booking_id' => $booking->id,
                    'error' => $smsMessage->error_message,
                ]);
                // Don't fail the whole operation if SMS fails - email was primary
            } else {
                $results['channel_status']['sms'] = [
                    'success' => true,
                    'status' => $smsMessage->status,
                    'recipient' => $smsMessage->recipient,
                    'error' => null,
                ];
                Log::info('SMS notification sent successfully', [
                    'booking_id' => $booking->id,
                    'message_id' => $smsMessage->id,
                ]);
            }

        }
        // ============================================================
        // Email only (no phone available)
        // ============================================================
        elseif ($useEmailOnly) {
            $results['channel_used'] = 'email';
            $results['channels'] = ['email'];

            Log::info('=== SENDING EMAIL ONLY ===', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
                'language' => $isCustomMessage ? 'en' : $language,
            ]);

            // Send beautiful email with PDF tickets
            $emailMessage = $this->emailService->sendBeautifulTicketEmail(
                $booking,
                $isCustomMessage ? 'en' : $language,
                $attachments->all()
            );
            $results['messages'][] = $emailMessage;

            // Check if email succeeded or failed
            if ($emailMessage->status === Message::STATUS_FAILED) {
                $results['errors'][] = "Email failed: {$emailMessage->error_message}";
                $results['channel_status']['email'] = [
                    'success' => false,
                    'status' => 'failed',
                    'recipient' => $booking->customer_email,
                    'error' => $emailMessage->error_message,
                ];
            } else {
                $results['success'] = true;
                $results['channel_status']['email'] = [
                    'success' => true,
                    'status' => $emailMessage->status,
                    'recipient' => $booking->customer_email,
                    'error' => null,
                ];
            }

        }
        // ============================================================
        // Phone only (no WhatsApp, no email) - Cannot send tickets
        // ============================================================
        elseif ($usePhoneOnly) {
            $results['errors'][] = 'Cannot send tickets via SMS only - SMS cannot carry PDF attachments. Customer needs WhatsApp or Email.';
            Log::warning('Cannot send ticket - phone only, no WhatsApp or email', [
                'booking_id' => $booking->id,
                'phone' => $booking->customer_phone,
            ]);
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

        // WhatsApp + Email (dual delivery)
        if ($hasPhone && $hasWhatsApp && $hasEmail) {
            return [
                'primary' => 'whatsapp_email',
                'channels' => ['whatsapp', 'email'],
                'description' => 'Will send via WhatsApp + Email (dual delivery)',
                'pdf_supported' => true,
            ];
        }

        // WhatsApp only
        if ($hasPhone && $hasWhatsApp) {
            return [
                'primary' => 'whatsapp',
                'channels' => ['whatsapp'],
                'description' => 'Will send via WhatsApp with PDF attachment',
                'pdf_supported' => true,
            ];
        }

        // Email + SMS notification (no WhatsApp)
        if ($hasEmail && $hasPhone) {
            return [
                'primary' => 'email_sms',
                'channels' => ['email', 'sms'],
                'description' => 'Will send via Email + SMS notification',
                'pdf_supported' => true,
            ];
        }

        // Email only (no phone)
        if ($hasEmail) {
            return [
                'primary' => 'email',
                'channels' => ['email'],
                'description' => 'Will send via Email only',
                'pdf_supported' => true,
            ];
        }

        // Phone only, no WhatsApp, no email - cannot send tickets
        if ($hasPhone) {
            return [
                'primary' => null,
                'channels' => [],
                'description' => 'Cannot send tickets - SMS only (no WhatsApp or Email). SMS cannot carry PDF attachments.',
                'pdf_supported' => false,
                'error' => 'Customer needs WhatsApp or Email to receive ticket PDFs',
            ];
        }

        return [
            'primary' => null,
            'channels' => [],
            'description' => 'No contact information available',
            'pdf_supported' => false,
            'error' => 'No phone or email available',
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

    /**
     * Retry a single failed message
     *
     * Re-sends the message using the same channel and booking context.
     * Updates retry_count and last_retry_at on success or failure.
     *
     * @param Message $message The failed message to retry
     * @return array ['success' => bool, 'message' => Message|null, 'error' => string|null]
     */
    public function retrySingleMessage(Message $message): array
    {
        $result = [
            'success' => false,
            'message' => null,
            'error' => null,
        ];

        // Validate message can be retried
        if (!$message->canRetry()) {
            $result['error'] = $message->status !== Message::STATUS_FAILED
                ? 'Only failed messages can be retried'
                : 'Maximum retry attempts reached (3)';
            return $result;
        }

        // Load booking with relationship
        $booking = $message->booking;
        if (!$booking) {
            $result['error'] = 'Booking not found for this message';
            return $result;
        }

        // Get attachments - first try from message, then from booking
        $attachments = $message->attachments->all();

        // If no attachments on message, get from booking
        if (empty($attachments)) {
            $attachments = $booking->attachments()->get()->all();
            Log::info('Using booking attachments for retry', [
                'message_id' => $message->id,
                'booking_id' => $booking->id,
                'attachments_count' => count($attachments),
            ]);
        }

        Log::info('=== RETRY MESSAGE START ===', [
            'message_id' => $message->id,
            'channel' => $message->channel,
            'booking_id' => $booking->id,
            'retry_count' => $message->retry_count,
            'attachments_count' => count($attachments),
        ]);

        // Update last_retry_at
        $message->update(['last_retry_at' => now()]);

        try {
            switch ($message->channel) {
                case Message::CHANNEL_WHATSAPP:
                    $result = $this->retryWhatsApp($message, $booking, $attachments);
                    break;

                case Message::CHANNEL_EMAIL:
                    $result = $this->retryEmail($message, $booking, $attachments);
                    break;

                case Message::CHANNEL_SMS:
                    $result = $this->retrySms($message, $booking);
                    break;

                default:
                    $result['error'] = "Unknown channel: {$message->channel}";
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('Retry message failed with exception', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('=== RETRY MESSAGE END ===', [
            'message_id' => $message->id,
            'success' => $result['success'],
            'error' => $result['error'],
        ]);

        return $result;
    }

    /**
     * Retry WhatsApp message
     */
    protected function retryWhatsApp(Message $originalMessage, Booking $booking, array $attachments): array
    {
        $result = ['success' => false, 'message' => null, 'error' => null];

        // Get language from template variables or default
        $language = $originalMessage->template_variables['language'] ?? 'en';
        $hasAudioGuide = $booking->has_audio_guide;

        // Get template
        $template = $this->getTemplate('whatsapp', $language, $hasAudioGuide);

        try {
            // Send via TwilioService
            $newMessage = $this->twilioService->sendWhatsApp($booking, $template, $attachments);

            // Mark original message as retried (keep status as failed, add note)
            $originalMessage->update([
                'error_message' => 'Retried successfully. New message ID: ' . $newMessage->id,
            ]);

            $result['success'] = true;
            $result['message'] = $newMessage;

            Log::info('WhatsApp retry successful', [
                'original_id' => $originalMessage->id,
                'new_id' => $newMessage->id,
            ]);

        } catch (\Exception $e) {
            // Increment retry count on the original message
            $originalMessage->update([
                'retry_count' => $originalMessage->retry_count + 1,
                'error_message' => 'Retry failed: ' . $e->getMessage(),
            ]);

            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Retry Email message
     */
    protected function retryEmail(Message $originalMessage, Booking $booking, array $attachments): array
    {
        $result = ['success' => false, 'message' => null, 'error' => null];

        // Get language from template variables
        $language = $originalMessage->template_variables['language'] ?? 'en';

        try {
            // Use beautiful email template
            $newMessage = $this->emailService->sendBeautifulTicketEmail(
                $booking,
                $language,
                $attachments
            );

            // Check if email succeeded
            if ($newMessage->status === Message::STATUS_FAILED) {
                $result['error'] = $newMessage->error_message;
                $originalMessage->update([
                    'retry_count' => $originalMessage->retry_count + 1,
                    'error_message' => 'Retry failed: ' . $newMessage->error_message,
                ]);
            } else {
                // Mark original message as retried (keep status as failed, add note)
                $originalMessage->update([
                    'error_message' => 'Retried successfully. New message ID: ' . $newMessage->id,
                ]);

                $result['success'] = true;
                $result['message'] = $newMessage;

                Log::info('Email retry successful', [
                    'original_id' => $originalMessage->id,
                    'new_id' => $newMessage->id,
                ]);
            }

        } catch (\Exception $e) {
            $originalMessage->update([
                'retry_count' => $originalMessage->retry_count + 1,
                'error_message' => 'Retry failed: ' . $e->getMessage(),
            ]);

            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Retry SMS message
     *
     * Uses config-based SMS messages from config/sms_notifications.php
     */
    protected function retrySms(Message $originalMessage, Booking $booking): array
    {
        $result = ['success' => false, 'message' => null, 'error' => null];

        // Get language from template variables
        $language = $originalMessage->template_variables['language'] ?? 'en';

        // Get SMS body from config (not database template)
        $smsMessages = config('sms_notifications.ticket_email_notification');
        $smsBody = $smsMessages[$language]
            ?? $smsMessages[config('sms_notifications.fallback_language', 'en')]
            ?? $smsMessages['en'];

        // Use sendSmsNotification which returns message instead of throwing
        $newMessage = $this->twilioService->sendSmsNotification($booking, $smsBody, $language);

        // Check if SMS succeeded or failed
        if ($newMessage->status === Message::STATUS_FAILED) {
            $originalMessage->update([
                'retry_count' => $originalMessage->retry_count + 1,
                'error_message' => 'Retry failed: ' . $newMessage->error_message,
            ]);

            $result['error'] = $newMessage->error_message;
        } else {
            // Mark original message as retried (keep status as failed, add note)
            $originalMessage->update([
                'error_message' => 'Retried successfully. New message ID: ' . $newMessage->id,
            ]);

            $result['success'] = true;
            $result['message'] = $newMessage;

            Log::info('SMS retry successful', [
                'original_id' => $originalMessage->id,
                'new_id' => $newMessage->id,
            ]);
        }

        return $result;
    }

    /**
     * Get all retryable failed messages
     *
     * @param int $limit Maximum number of messages to return
     * @param string|null $channel Filter by channel (whatsapp, email, sms)
     * @return Collection
     */
    public function getRetryableMessages(int $limit = 50, ?string $channel = null): Collection
    {
        $query = Message::retryable()
            ->with(['booking:id,customer_name,customer_phone,customer_email'])
            ->orderBy('failed_at', 'asc');

        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Batch retry all retryable failed messages
     *
     * @param int $limit Maximum number of messages to retry
     * @param string|null $channel Filter by channel
     * @return array ['total' => int, 'success' => int, 'failed' => int, 'results' => array]
     */
    public function batchRetryFailedMessages(int $limit = 50, ?string $channel = null): array
    {
        $messages = $this->getRetryableMessages($limit, $channel);

        $results = [
            'total' => $messages->count(),
            'success' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($messages as $message) {
            $retryResult = $this->retrySingleMessage($message);

            $results['results'][] = [
                'message_id' => $message->id,
                'channel' => $message->channel,
                'recipient' => $message->recipient,
                'success' => $retryResult['success'],
                'error' => $retryResult['error'],
            ];

            if ($retryResult['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            // Small delay between retries to avoid rate limiting
            usleep(500000); // 500ms
        }

        return $results;
    }
}
