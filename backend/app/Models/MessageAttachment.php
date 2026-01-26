<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'booking_id',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'public_url',
        'expires_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Maximum file size in bytes (10 MB)
     */
    public const MAX_SIZE = 10 * 1024 * 1024;

    /**
     * Allowed MIME types
     */
    public const ALLOWED_TYPES = [
        'application/pdf',
    ];

    /**
     * URL expiration time in minutes for S3 presigned URLs
     */
    public const URL_EXPIRATION_MINUTES = 60 * 24; // 24 hours

    /**
     * Get the message for this attachment
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the booking for this attachment
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the full storage path
     */
    public function getFullPath(): string
    {
        return $this->path;
    }

    /**
     * Get a temporary/presigned URL for the file
     */
    public function getTemporaryUrl(int $minutes = null): ?string
    {
        $minutes = $minutes ?? self::URL_EXPIRATION_MINUTES;

        // If we have a valid cached public URL, return it
        if ($this->public_url && $this->expires_at && $this->expires_at->isFuture()) {
            return $this->public_url;
        }

        $disk = Storage::disk($this->disk);

        // For S3, generate a presigned URL
        if ($this->disk === 's3') {
            $url = $disk->temporaryUrl($this->path, now()->addMinutes($minutes));

            // Cache the URL
            $this->update([
                'public_url' => $url,
                'expires_at' => now()->addMinutes($minutes),
            ]);

            return $url;
        }

        // For local storage, return a relative URL (would need a route to serve)
        return null;
    }

    /**
     * Get file contents
     */
    public function getContents(): ?string
    {
        return Storage::disk($this->disk)->get($this->path);
    }

    /**
     * Check if file exists
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Delete the physical file
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::disk($this->disk)->delete($this->path);
        }

        return true;
    }

    /**
     * Boot method to clean up file on model delete
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (MessageAttachment $attachment) {
            $attachment->deleteFile();
        });
    }

    /**
     * Scope for expired attachments
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for orphaned attachments (no message attached, older than 24 hours)
     */
    public function scopeOrphaned($query)
    {
        return $query->whereNull('message_id')
            ->where('created_at', '<', now()->subHours(24));
    }
}
