<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManualEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $emailContent;
    public ?array $attachmentData;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $subject,
        string $content,
        ?array $attachmentData = null
    ) {
        $this->emailSubject = $subject;
        $this->emailContent = $content;
        $this->attachmentData = $attachmentData;
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
            view: 'emails.manual',
            with: [
                'content' => $this->emailContent,
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
        if (!$this->attachmentData) {
            return [];
        }

        return [
            Attachment::fromData(
                fn () => $this->attachmentData['content'],
                $this->attachmentData['name']
            )->withMime($this->attachmentData['mime'] ?? 'application/pdf'),
        ];
    }
}
