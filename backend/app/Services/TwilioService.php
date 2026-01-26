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
     * Send WhatsApp message
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
        $variables = $booking->getTemplateVariables();

        // Create message record
        $message = Message::create([
            'booking_id' => $booking->id,
            'channel' => Message::CHANNEL_WHATSAPP,
            'recipient' => $phone,
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
            $message->markQueued();

            $client = $this->getClient();

            // Prepare media URLs for attachments
            $mediaUrls = [];
            foreach ($message->attachments as $attachment) {
                $url = $attachment->getTemporaryUrl();
                if ($url) {
                    $mediaUrls[] = $url;
                }
            }

            // Build message options
            $options = [
                'from' => "whatsapp:{$this->whatsappFrom}",
                'body' => $message->content,
            ];

            if (!empty($mediaUrls)) {
                $options['mediaUrl'] = $mediaUrls;
            }

            // Add status callback
            if (config('services.twilio.status_callback_url')) {
                $options['statusCallback'] = config('services.twilio.status_callback_url');
            }

            // Send via Twilio
            $twilioMessage = $client->messages->create(
                "whatsapp:{$phone}",
                $options
            );

            $message->markSent($twilioMessage->sid);

            Log::info('WhatsApp sent successfully', [
                'booking_id' => $booking->id,
                'phone' => $phone,
                'message_id' => $message->id,
                'twilio_sid' => $twilioMessage->sid,
            ]);

            return $message;

        } catch (TwilioException $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send WhatsApp', [
                'booking_id' => $booking->id,
                'phone' => $phone,
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
     */
    public function previewWhatsApp(
        Booking $booking,
        MessageTemplate $template
    ): array {
        $variables = $booking->getTemplateVariables();

        return [
            'content' => $template->render($variables),
            'recipient' => $this->formatPhoneNumber($booking->customer_phone ?? ''),
            'channel' => 'whatsapp',
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
