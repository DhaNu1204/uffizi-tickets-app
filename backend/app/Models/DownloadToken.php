<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DownloadToken extends Model
{
    protected $fillable = [
        'token',
        'attachment_id',
        'booking_id',
        's3_path',
        'filename',
        'mime_type',
        'expires_at',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'download_count' => 'integer',
    ];

    /**
     * Generate a unique 8-character token
     */
    public static function generateUniqueToken(): string
    {
        do {
            // Generate 8-char alphanumeric token
            $token = Str::random(8);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Increment download count and update last downloaded time
     */
    public function recordDownload(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Get the full short URL
     * Format: https://uffizi.deetech.cc/api/t/{token}.pdf (~44 chars)
     * .pdf extension required by Twilio/WhatsApp for media validation
     */
    public function getShortUrl(): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/api/t/{$this->token}.pdf";
    }

    /**
     * Relationship to MessageAttachment
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(MessageAttachment::class, 'attachment_id');
    }

    /**
     * Relationship to Booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope to get non-expired tokens
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired tokens (for cleanup)
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
