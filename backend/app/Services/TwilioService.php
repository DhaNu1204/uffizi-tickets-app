<?php

namespace App\Services;

use App\Models\Booking;
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
     * Check if phone number has WhatsApp
     * Uses Twilio Lookup API
     */
    public function hasWhatsApp(string $phoneNumber): bool
    {
        try {
            $client = $this->getClient();

            // Clean phone number
            $phone = $this->formatPhoneNumber($phoneNumber);

            // Use Lookup API with line type intelligence
            $lookup = $client->lookups->v2->phoneNumbers($phone)
                ->fetch(['fields' => 'line_type_intelligence']);

            // Check if mobile (WhatsApp typically works on mobile)
            $lineType = $lookup->lineTypeIntelligence['type'] ?? null;

            // WhatsApp is typically available on mobile numbers
            // This is a heuristic - not 100% accurate
            $mobileTypes = ['mobile', 'voip'];

            return in_array($lineType, $mobileTypes, true);

        } catch (TwilioException $e) {
            Log::warning('WhatsApp lookup failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            // Default to assuming WhatsApp is available for mobile-looking numbers
            return $this->looksLikeMobile($phoneNumber);
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
}
