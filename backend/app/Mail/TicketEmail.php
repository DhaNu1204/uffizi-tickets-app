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
                $disk = Storage::disk($file['disk']);

                if ($disk->exists($file['path'])) {
                    $attachments[] = Attachment::fromStorage($file['path'])
                        ->as($file['name'] ?? basename($file['path']))
                        ->withMime($file['mime'] ?? 'application/pdf');
                }
            }
        }

        return $attachments;
    }
}
