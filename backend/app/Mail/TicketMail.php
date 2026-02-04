<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Beautiful HTML email using Blade templates
 *
 * Supports:
 * - 10 languages: en, it, es, de, fr, pt, ja, ko, el, tr
 * - 2 types: audio (with PopGuide link) and non-audio (with online guide)
 */
class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public string $language;
    public bool $hasAudioGuide;
    public array $attachmentFiles;

    // Template variables
    public string $customerName;
    public string $tourDateTime;
    public int $pax;
    public string $referenceNumber;
    public string $onlineGuideUrl;
    public string $knowBeforeYouGoUrl;
    public ?string $audioGuideUrl;

    /**
     * Supported languages
     */
    public const SUPPORTED_LANGUAGES = ['en', 'it', 'es', 'de', 'fr', 'pt', 'ja', 'ko', 'el', 'tr'];

    /**
     * Subject lines by language
     */
    protected const SUBJECTS = [
        'en' => [
            'audio' => 'Your Uffizi Gallery Tickets + Audio Guide',
            'non_audio' => 'Your Uffizi Gallery Tickets',
        ],
        'it' => [
            'audio' => 'I tuoi biglietti + Audioguida',
            'non_audio' => 'I tuoi biglietti per la Galleria degli Uffizi',
        ],
        'es' => [
            'audio' => 'Tus entradas + Audioguía',
            'non_audio' => 'Tus entradas para la Galería Uffizi',
        ],
        'de' => [
            'audio' => 'Ihre Eintrittskarten + Audioguide',
            'non_audio' => 'Ihre Eintrittskarten für die Uffizien',
        ],
        'fr' => [
            'audio' => 'Vos billets + Audioguide',
            'non_audio' => 'Vos billets pour la Galerie des Offices',
        ],
        'pt' => [
            'audio' => 'Seus ingressos + Audioguia',
            'non_audio' => 'Seus ingressos para a Galeria Uffizi',
        ],
        'ja' => [
            'audio' => 'チケット + オーディオガイド',
            'non_audio' => 'ウフィツィ美術館のチケット',
        ],
        'ko' => [
            'audio' => '입장권 + 오디오 가이드',
            'non_audio' => '우피치 미술관 입장권',
        ],
        'el' => [
            'audio' => 'Τα εισιτήριά σας + Ξενάγηση',
            'non_audio' => 'Τα εισιτήριά σας για την Πινακοθήκη Ουφίτσι',
        ],
        'tr' => [
            'audio' => 'Biletleriniz + Sesli Rehber',
            'non_audio' => 'Uffizi Galerisi Biletleriniz',
        ],
    ];

    /**
     * Create a new message instance.
     */
    public function __construct(
        Booking $booking,
        string $language = 'en',
        array $attachmentFiles = []
    ) {
        $this->booking = $booking;
        $this->language = in_array($language, self::SUPPORTED_LANGUAGES) ? $language : 'en';
        $this->hasAudioGuide = (bool) $booking->has_audio_guide;
        $this->attachmentFiles = $attachmentFiles;

        // Prepare template variables
        $this->customerName = $booking->customer_name ?? 'Guest';
        $this->tourDateTime = $this->formatDateTime($booking);
        $this->pax = $booking->pax ?? 1;
        $this->referenceNumber = $booking->reference_number ?? 'N/A';

        // URLs
        $this->onlineGuideUrl = 'https://florencewithlocals.com/uffizi-guide';
        $this->knowBeforeYouGoUrl = 'https://florencewithlocals.com/know-before-you-go';
        $this->audioGuideUrl = $booking->vox_dynamic_link;
    }

    /**
     * Format the date/time for display
     */
    protected function formatDateTime(Booking $booking): string
    {
        if ($booking->tour_date) {
            $date = $booking->tour_date;
            if (is_string($date)) {
                $date = \Carbon\Carbon::parse($date);
            }

            // Format based on language
            $formats = [
                'en' => 'F j, Y \a\t g:i A',
                'it' => 'j F Y \a\l\l\e H:i',
                'es' => 'j \d\e F \d\e Y \a \l\a\s H:i',
                'de' => 'j. F Y \u\m H:i \U\h\r',
                'fr' => 'j F Y à H:i',
                'pt' => 'j \d\e F \d\e Y à\s H:i',
                'ja' => 'Y年n月j日 H:i',
                'ko' => 'Y년 n월 j일 H:i',
                'el' => 'j F Y, H:i',
                'tr' => 'j F Y, H:i',
            ];

            $format = $formats[$this->language] ?? $formats['en'];
            return $date->format($format);
        }

        return 'Date not available';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = $this->hasAudioGuide ? 'audio' : 'non_audio';
        $subject = self::SUBJECTS[$this->language][$type] ?? self::SUBJECTS['en'][$type];

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Determine which template to use
        $templatePath = $this->hasAudioGuide
            ? "emails.tickets.audio.{$this->language}"
            : "emails.tickets.non-audio.{$this->language}";

        return new Content(
            view: $templatePath,
            with: [
                'customerName' => $this->customerName,
                'tourDateTime' => $this->tourDateTime,
                'pax' => $this->pax,
                'referenceNumber' => $this->referenceNumber,
                'onlineGuideUrl' => $this->onlineGuideUrl,
                'knowBeforeYouGoUrl' => $this->knowBeforeYouGoUrl,
                'audioGuideUrl' => $this->audioGuideUrl,
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

        foreach ($this->attachmentFiles as $file) {
            if (isset($file['path']) && isset($file['disk'])) {
                $diskName = $file['disk'];
                $disk = Storage::disk($diskName);

                if ($disk->exists($file['path'])) {
                    try {
                        $content = $disk->get($file['path']);

                        if ($content !== null) {
                            $attachments[] = Attachment::fromData(
                                fn () => $content,
                                $file['name'] ?? basename($file['path'])
                            )->withMime($file['mime'] ?? 'application/pdf');
                        } else {
                            Log::warning('Email attachment content is null', [
                                'disk' => $diskName,
                                'path' => $file['path'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to read email attachment', [
                            'disk' => $diskName,
                            'path' => $file['path'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::warning('Email attachment file not found', [
                        'disk' => $diskName,
                        'path' => $file['path'],
                    ]);
                }
            }
        }

        return $attachments;
    }
}
