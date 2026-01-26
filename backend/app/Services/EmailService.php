<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketEmail;

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
}
