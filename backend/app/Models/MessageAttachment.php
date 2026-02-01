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
     * Default: 7 days (10080 minutes) - maximum allowed by AWS S3 SigV4
     */
    public const URL_EXPIRATION_MINUTES = 7 * 24 * 60; // 7 days (AWS max)

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
     *
     * @param int|null $minutes Expiry in minutes. Defaults to 14 days from config.
     * @return string|null Pre-signed URL or null on failure
     */
    public function getTemporaryUrl(int $minutes = null): ?string
    {
        // Default to 14 days from config, fallback to constant
        $minutes = $minutes ?? (config('whatsapp_templates.pdf_url_expiry_days', 14) * 24 * 60);

        if (!$this->path) {
            return null;
        }

        // If we have a valid cached public URL with enough time remaining, return it
        // Only use cache if it has at least 1 day remaining
        if ($this->public_url && $this->expires_at && $this->expires_at->isAfter(now()->addDay())) {
            return $this->public_url;
        }

        try {
            $disk = Storage::disk($this->disk ?? 's3');

            // For S3, generate a presigned URL
            if ($this->disk === 's3') {
                if (!$disk->exists($this->path)) {
                    \Illuminate\Support\Facades\Log::warning('S3 file not found for presigned URL', [
                        'attachment_id' => $this->id,
                        'path' => $this->path,
                    ]);
                    return null;
                }

                $url = $disk->temporaryUrl($this->path, now()->addMinutes($minutes));

                // Cache the URL
                $this->update([
                    'public_url' => $url,
                    'expires_at' => now()->addMinutes($minutes),
                ]);

                \Illuminate\Support\Facades\Log::info('Generated S3 presigned URL', [
                    'attachment_id' => $this->id,
                    'expiry_days' => round($minutes / 60 / 24, 1),
                    'expires_at' => now()->addMinutes($minutes)->toDateTimeString(),
                ]);

                return $url;
            }

            // For local storage, generate a signed public URL
            if ($this->disk === 'local') {
                $signature = self::generateSignature($this->id);
                $url = url("/api/public/attachments/{$this->id}/{$signature}");

                // Cache the URL
                $this->update([
                    'public_url' => $url,
                    'expires_at' => now()->addMinutes($minutes),
                ]);

                return $url;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to generate temporary URL', [
                'attachment_id' => $this->id,
                'disk' => $this->disk,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Generate a signature for secure public access to local files
     */
    public static function generateSignature(int $attachmentId): string
    {
        $key = config('app.key');
        return hash_hmac('sha256', "attachment-{$attachmentId}", $key);
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
