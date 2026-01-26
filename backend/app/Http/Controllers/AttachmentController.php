<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * Upload attachment for a booking
     * POST /api/bookings/{id}/attachments
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:' . (MessageAttachment::MAX_SIZE / 1024), // Convert to KB
            ],
        ], [
            'file.required' => 'A PDF file is required',
            'file.mimes' => 'Only PDF files are allowed',
            'file.max' => 'File size must not exceed 10 MB',
        ]);

        $file = $request->file('file');

        // Generate unique filename
        $storedName = Str::uuid() . '.pdf';
        $path = "attachments/{$booking->id}/{$storedName}";

        // Determine which disk to use
        $disk = config('filesystems.default', 'local');
        if (config('services.aws.bucket')) {
            $disk = 's3';
        }

        // Store the file
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        // Create attachment record
        $attachment = MessageAttachment::create([
            'booking_id' => $booking->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'attachment' => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
                'created_at' => $attachment->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * List attachments for a booking
     * GET /api/bookings/{id}/attachments
     */
    public function index(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $attachments = $booking->attachments()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'attachments' => $attachments->map(function ($a) {
                return [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'size' => $a->size,
                    'mime_type' => $a->mime_type,
                    'message_id' => $a->message_id,
                    'created_at' => $a->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Delete an attachment
     * DELETE /api/attachments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $attachment = MessageAttachment::findOrFail($id);

        // Don't allow deletion if already attached to a sent message
        if ($attachment->message_id) {
            $message = $attachment->message;
            if ($message && in_array($message->status, ['sent', 'delivered', 'read'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete attachment from sent message',
                ], 422);
            }
        }

        // Delete file and record
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted',
        ]);
    }

    /**
     * Get a temporary download URL for an attachment
     * GET /api/attachments/{id}/download
     */
    public function download(int $id): JsonResponse
    {
        $attachment = MessageAttachment::findOrFail($id);

        if (!$attachment->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'File not found',
            ], 404);
        }

        // For S3, generate presigned URL
        if ($attachment->disk === 's3') {
            $url = $attachment->getTemporaryUrl(60); // 60 minutes

            return response()->json([
                'success' => true,
                'url' => $url,
                'expires_at' => now()->addMinutes(60)->toIso8601String(),
            ]);
        }

        // For local storage, we'd need a download route
        // This could return a signed URL or stream the file
        return response()->json([
            'success' => false,
            'error' => 'Download not supported for local storage',
        ], 501);
    }
}
