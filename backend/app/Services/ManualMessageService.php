<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;
use App\Mail\ManualEmail;

class ManualMessageService
{
    protected ?TwilioClient $twilioClient = null;
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
    protected function getTwilioClient(): TwilioClient
    {
        if ($this->twilioClient === null) {
            if (empty($this->accountSid) || empty($this->authToken)) {
                throw new \RuntimeException('Twilio credentials not configured');
            }
            $this->twilioClient = new TwilioClient($this->accountSid, $this->authToken);
        }

        return $this->twilioClient;
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
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Send WhatsApp message
     */
    public function sendWhatsApp(
        string $phone,
        string $content,
        ?UploadedFile $attachment = null
    ): Message {
        $phone = $this->formatPhoneNumber($phone);

        // Create message record (without booking)
        $message = Message::create([
            'booking_id' => null,
            'channel' => Message::CHANNEL_WHATSAPP,
            'recipient' => $phone,
            'content' => $content,
            'template_id' => null,
            'template_variables' => null,
            'status' => Message::STATUS_PENDING,
        ]);

        try {
            $message->markQueued();

            $client = $this->getTwilioClient();

            // Build message options
            $options = [
                'from' => "whatsapp:{$this->whatsappFrom}",
                'body' => $content,
            ];

            // Handle attachment if provided
            if ($attachment) {
                $mediaUrl = $this->uploadAttachmentForWhatsApp($attachment);
                if ($mediaUrl) {
                    $options['mediaUrl'] = [$mediaUrl];
                }
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

            Log::info('Manual WhatsApp sent successfully', [
                'phone' => $phone,
                'message_id' => $message->id,
                'twilio_sid' => $twilioMessage->sid,
            ]);

            return $message;

        } catch (TwilioException $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send manual WhatsApp', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload attachment to S3 and return public URL for WhatsApp
     */
    protected function uploadAttachmentForWhatsApp(UploadedFile $file): ?string
    {
        try {
            // Generate unique filename
            $filename = 'manual-attachments/' . uniqid() . '_' . $file->getClientOriginalName();

            // Upload to S3
            $path = Storage::disk('s3')->putFileAs(
                'manual-attachments',
                $file,
                basename($filename),
                'public'
            );

            // Get the public URL
            $url = Storage::disk('s3')->url($path);

            Log::info('Attachment uploaded for WhatsApp', ['url' => $url]);

            return $url;

        } catch (\Exception $e) {
            Log::error('Failed to upload attachment for WhatsApp', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send SMS message
     */
    public function sendSms(string $phone, string $content): Message
    {
        $phone = $this->formatPhoneNumber($phone);

        // Create message record (without booking)
        $message = Message::create([
            'booking_id' => null,
            'channel' => Message::CHANNEL_SMS,
            'recipient' => $phone,
            'content' => $content,
            'template_id' => null,
            'template_variables' => null,
            'status' => Message::STATUS_PENDING,
        ]);

        try {
            $message->markQueued();

            $client = $this->getTwilioClient();

            // Build message options
            $options = [
                'from' => $this->smsFrom,
                'body' => $content,
            ];

            // Add status callback
            if (config('services.twilio.status_callback_url')) {
                $options['statusCallback'] = config('services.twilio.status_callback_url');
            }

            // Send via Twilio
            $twilioMessage = $client->messages->create($phone, $options);

            $message->markSent($twilioMessage->sid);

            Log::info('Manual SMS sent successfully', [
                'phone' => $phone,
                'message_id' => $message->id,
                'twilio_sid' => $twilioMessage->sid,
            ]);

            return $message;

        } catch (TwilioException $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send manual SMS', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send Email message
     */
    public function sendEmail(
        string $email,
        string $subject,
        string $content,
        ?UploadedFile $attachment = null
    ): Message {
        // Create message record (without booking)
        $message = Message::create([
            'booking_id' => null,
            'channel' => Message::CHANNEL_EMAIL,
            'recipient' => $email,
            'subject' => $subject,
            'content' => $content,
            'template_id' => null,
            'template_variables' => null,
            'status' => Message::STATUS_PENDING,
        ]);

        try {
            $message->markQueued();

            // Prepare attachment
            $attachmentData = null;
            if ($attachment) {
                $attachmentData = [
                    'content' => $attachment->get(),
                    'name' => $attachment->getClientOriginalName(),
                    'mime' => $attachment->getMimeType(),
                ];
            }

            // Send email
            Mail::to($email)->send(new ManualEmail(
                subject: $subject,
                content: $content,
                attachmentData: $attachmentData
            ));

            $message->markSent();

            Log::info('Manual email sent successfully', [
                'email' => $email,
                'message_id' => $message->id,
            ]);

            return $message;

        } catch (\Exception $e) {
            $message->markFailed($e->getMessage());

            Log::error('Failed to send manual email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
