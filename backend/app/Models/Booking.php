<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'cancelled_at',
        'tickets_sent_at',
    ];

    protected $appends = ['has_incomplete_participants'];

    protected $casts = [
        'tour_date' => 'datetime',
        'pax_details' => 'array',
        'participants' => 'array',
        'cancelled_at' => 'datetime',
        'tickets_sent_at' => 'datetime',
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
     * Check if this booking is a guided tour (requires guide assignment)
     */
    public function isGuidedTour(): bool
    {
        return in_array((string) $this->bokun_product_id, self::GUIDED_TOUR_IDS);
    }
}
