<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'channel',
        'booking_id',
        'status',
        'last_message_at',
        'unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Channel constants
     */
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_SMS = 'sms';

    /**
     * WhatsApp 24-hour messaging window in hours
     */
    public const WHATSAPP_WINDOW_HOURS = 24;

    /**
     * Get the booking linked to this conversation
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get all messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message in this conversation
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    /**
     * Scope for active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for conversations with unread messages
     */
    public function scopeWithUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }

    /**
     * Scope for conversations by channel
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Find or create a conversation for a phone number and channel
     */
    public static function findOrCreateByPhone(string $phoneNumber, string $channel, ?int $bookingId = null): self
    {
        $normalized = self::normalizePhoneNumber($phoneNumber);

        $conversation = self::where('phone_number', $normalized)
            ->where('channel', $channel)
            ->first();

        if ($conversation) {
            // Update booking link if provided and not already set
            if ($bookingId && !$conversation->booking_id) {
                $conversation->update(['booking_id' => $bookingId]);
            }
            return $conversation;
        }

        // Try to find a matching booking by phone number
        if (!$bookingId) {
            $booking = self::findBookingByPhone($normalized);
            $bookingId = $booking?->id;
        }

        return self::create([
            'phone_number' => $normalized,
            'channel' => $channel,
            'booking_id' => $bookingId,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Find a booking by phone number (tries exact and partial match)
     */
    protected static function findBookingByPhone(string $phone): ?Booking
    {
        // Try exact match first
        $booking = Booking::where('customer_phone', $phone)
            ->orderBy('tour_date', 'desc')
            ->first();

        if ($booking) {
            return $booking;
        }

        // Try partial match (last 10 digits)
        $digits = preg_replace('/\D/', '', $phone);
        $lastDigits = substr($digits, -10);

        return Booking::whereRaw("REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$lastDigits}"])
            ->orderBy('tour_date', 'desc')
            ->first();
    }

    /**
     * Normalize phone number to E.164 format
     */
    public static function normalizePhoneNumber(string $phone): string
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
     * Increment unread count
     */
    public function incrementUnread(): void
    {
        $this->increment('unread_count');
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Mark all messages as read
     */
    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }

    /**
     * Archive the conversation
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Reactivate an archived conversation
     */
    public function reactivate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Check if WhatsApp 24-hour window is open
     * (Can send free-form messages within 24 hours of customer's last message)
     */
    public function isWhatsAppWindowOpen(): bool
    {
        if ($this->channel !== self::CHANNEL_WHATSAPP) {
            return false;
        }

        $lastInbound = $this->messages()
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if (!$lastInbound) {
            return false;
        }

        return $lastInbound->created_at->diffInHours(now()) < self::WHATSAPP_WINDOW_HOURS;
    }

    /**
     * Get time remaining in WhatsApp window (in minutes)
     */
    public function getWhatsAppWindowRemaining(): ?int
    {
        if ($this->channel !== self::CHANNEL_WHATSAPP) {
            return null;
        }

        $lastInbound = $this->messages()
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if (!$lastInbound) {
            return null;
        }

        $windowEnd = $lastInbound->created_at->addHours(self::WHATSAPP_WINDOW_HOURS);
        $remaining = now()->diffInMinutes($windowEnd, false);

        return max(0, $remaining);
    }

    /**
     * Get the display name for this conversation
     */
    public function getDisplayName(): string
    {
        if ($this->booking) {
            return $this->booking->customer_name ?? $this->phone_number;
        }

        return $this->phone_number;
    }

    /**
     * Get a preview of the last message (truncated)
     */
    public function getLastMessagePreview(int $length = 50): ?string
    {
        $lastMessage = $this->messages()->latest()->first();

        if (!$lastMessage) {
            return null;
        }

        $content = $lastMessage->content;

        if (strlen($content) <= $length) {
            return $content;
        }

        return substr($content, 0, $length) . '...';
    }
}
