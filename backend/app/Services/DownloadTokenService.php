<?php

namespace App\Services;

use App\Models\DownloadToken;
use App\Models\MessageAttachment;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadTokenService
{
    /**
     * Default token expiry in days
     */
    protected int $defaultExpiryDays = 7;

    /**
     * Create a download token for an attachment
     */
    public function createToken(MessageAttachment $attachment, Booking $booking, ?int $expiryDays = null): DownloadToken
    {
        $expiryDays = $expiryDays ?? $this->defaultExpiryDays;

        $token = DownloadToken::create([
            'token' => DownloadToken::generateUniqueToken(),
            'attachment_id' => $attachment->id,
            'booking_id' => $booking->id,
            's3_path' => $attachment->path,
            'filename' => $attachment->original_name ?? $attachment->stored_name ?? 'ticket.pdf',
            'mime_type' => $attachment->mime_type ?? 'application/pdf',
            'expires_at' => now()->addDays($expiryDays),
        ]);

        Log::info('Download token created', [
            'token' => $token->token,
            'attachment_id' => $attachment->id,
            'booking_id' => $booking->id,
            'expires_at' => $token->expires_at->toDateTimeString(),
        ]);

        return $token;
    }

    /**
     * Get short URL for an attachment (creates token if needed)
     */
    public function getShortUrl(MessageAttachment $attachment, Booking $booking): string
    {
        // Check if valid token already exists for this attachment
        $existingToken = DownloadToken::where('attachment_id', $attachment->id)
            ->where('booking_id', $booking->id)
            ->valid()
            ->first();

        if ($existingToken) {
            return $existingToken->getShortUrl();
        }

        // Create new token
        $token = $this->createToken($attachment, $booking);
        return $token->getShortUrl();
    }

    /**
     * Find token by its string value
     */
    public function findByToken(string $token): ?DownloadToken
    {
        return DownloadToken::where('token', $token)->first();
    }

    /**
     * Get file content from S3 or local storage
     */
    public function getFileFromStorage(DownloadToken $downloadToken): ?string
    {
        try {
            // Determine which disk to use based on attachment
            $attachment = $downloadToken->attachment;
            $disk = $attachment ? ($attachment->disk ?? 's3') : 's3';

            $storage = Storage::disk($disk);

            if (!$storage->exists($downloadToken->s3_path)) {
                Log::error('File not found in storage', [
                    'token' => $downloadToken->token,
                    'disk' => $disk,
                    'path' => $downloadToken->s3_path,
                ]);
                return null;
            }

            return $storage->get($downloadToken->s3_path);

        } catch (\Exception $e) {
            Log::error('Failed to fetch file from storage', [
                'token' => $downloadToken->token,
                'path' => $downloadToken->s3_path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Cleanup expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $count = DownloadToken::expired()->count();
        DownloadToken::expired()->delete();

        Log::info('Expired download tokens cleaned up', ['count' => $count]);

        return $count;
    }
}
