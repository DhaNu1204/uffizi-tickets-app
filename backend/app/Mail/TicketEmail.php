<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TicketEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $emailContent;
    public array $emailAttachments;
    public Booking $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $subject,
        string $content,
        array $attachments = [],
        Booking $booking = null
    ) {
        $this->emailSubject = $subject;
        $this->emailContent = $content;
        $this->emailAttachments = $attachments;
        $this->booking = $booking;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket',
            with: [
                'content' => $this->emailContent,
                'booking' => $this->booking,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->emailAttachments as $file) {
            if (isset($file['path']) && isset($file['disk'])) {
                $diskName = $file['disk'];
                $disk = Storage::disk($diskName);

                if ($disk->exists($file['path'])) {
                    try {
                        // For S3 and other remote disks, use fromData() with content
                        // This is more reliable than fromStorageDisk() which can return null
                        $content = $disk->get($file['path']);

                        if ($content !== null) {
                            $attachments[] = Attachment::fromData(
                                fn () => $content,
                                $file['name'] ?? basename($file['path'])
                            )->withMime($file['mime'] ?? 'application/pdf');
                        } else {
                            \Illuminate\Support\Facades\Log::warning('Email attachment content is null', [
                                'disk' => $diskName,
                                'path' => $file['path'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to read email attachment', [
                            'disk' => $diskName,
                            'path' => $file['path'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning('Email attachment file not found', [
                        'disk' => $diskName,
                        'path' => $file['path'],
                    ]);
                }
            }
        }

        return $attachments;
    }
}
