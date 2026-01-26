<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bokun_booking_id',
        'bokun_product_id',
        'booking_channel',
        'product_name',
        'customer_name',
        'customer_email',
        'customer_phone',
        'tour_date',
        'pax',
        'pax_details',
        'participants',
        'status',
        'reference_number',
        'notes',
        'guide_name',
        'has_audio_guide',
        'cancelled_at',
        'tickets_sent_at',
        'audio_guide_sent_at',
        'audio_guide_username',
        'audio_guide_password',
        'audio_guide_url',
    ];

    protected $appends = ['has_incomplete_participants'];

    protected $casts = [
        'tour_date' => 'datetime',
        'pax_details' => 'array',
        'participants' => 'array',
        'has_audio_guide' => 'boolean',
        'cancelled_at' => 'datetime',
        'tickets_sent_at' => 'datetime',
        'audio_guide_sent_at' => 'datetime',
    ];

    /**
     * Check if participant data is incomplete (fewer names than PAX count).
     * This commonly occurs with GetYourGuide bookings where only the lead
     * passenger's name is provided via the API.
     */
    public function getHasIncompleteParticipantsAttribute(): bool
    {
        $participantCount = is_array($this->participants) ? count($this->participants) : 0;
        return $participantCount > 0 && $participantCount < $this->pax;
    }

    /**
     * Check if this booking is from an OTA channel (GetYourGuide, Viator, etc.)
     */
    public function isOtaBooking(): bool
    {
        return in_array($this->booking_channel, ['GetYourGuide', 'Viator', 'TripAdvisor', 'Expedia']);
    }

    /**
     * Guided tour product IDs that require a guide assignment
     */
    public const GUIDED_TOUR_IDS = ['961801', '962885', '962886', '1130528', '1135055'];

    /**
     * Timed Entry Ticket product ID (only product with audio guide option)
     */
    public const TIMED_ENTRY_PRODUCT_ID = '961802';

    /**
     * Audio guide rate IDs and codes for Timed Entry Tickets
     * Rate 2263305 (TG2) = Entry Ticket + Audio Guide
     * Rate 1861234 (TG1) = Entry Ticket ONLY
     */
    public const AUDIO_GUIDE_RATE_ID = '2263305';
    public const AUDIO_GUIDE_RATE_CODE = 'TG2';
    public const TICKET_ONLY_RATE_ID = '1861234';
    public const TICKET_ONLY_RATE_CODE = 'TG1';

    /**
     * Check if this booking is a guided tour (requires guide assignment)
     */
    public function isGuidedTour(): bool
    {
        return in_array((string) $this->bokun_product_id, self::GUIDED_TOUR_IDS);
    }

    /**
     * Check if this booking is a timed entry ticket (can have audio guide)
     */
    public function isTimedEntry(): bool
    {
        return (string) $this->bokun_product_id === self::TIMED_ENTRY_PRODUCT_ID;
    }

    /**
     * Check if audio guide can be applicable to this booking
     * (Only timed entry tickets can have audio guides)
     */
    public function canHaveAudioGuide(): bool
    {
        return $this->isTimedEntry();
    }

    /**
     * Get messages for this booking
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get attachments for this booking
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Check if this booking has had tickets sent
     */
    public function hasTicketsSent(): bool
    {
        return $this->tickets_sent_at !== null;
    }

    /**
     * Check if audio guide credentials are set
     */
    public function hasAudioGuideCredentials(): bool
    {
        return $this->has_audio_guide
            && $this->audio_guide_username
            && $this->audio_guide_password;
    }

    /**
     * Get template variables for message rendering
     */
    public function getTemplateVariables(): array
    {
        $tourDate = $this->tour_date;
        $formattedDate = $tourDate ? $tourDate->format('F j, Y') : '';
        $formattedTime = $tourDate ? $tourDate->format('g:i A') : '';

        return [
            'customer_name' => $this->customer_name ?? 'Guest',
            'customer_email' => $this->customer_email ?? '',
            'tour_date' => $formattedDate,
            'tour_time' => $formattedTime,
            'product_name' => $this->product_name ?? '',
            'pax' => (string) $this->pax,
            'reference_number' => $this->reference_number ?? '',
            'audio_guide_url' => $this->audio_guide_url ?? '',
            'audio_guide_username' => $this->audio_guide_username ?? '',
            'audio_guide_password' => $this->audio_guide_password ?? '',
        ];
    }
}
