<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MessageTemplate;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Send ticket to customer
     * POST /api/bookings/{id}/send-ticket
     */
    public function sendTicket(Request $request, int $id): JsonResponse
    {
        Log::info('sendTicket called', [
            'booking_id' => $id,
            'request_data' => $request->all(),
        ]);

        $booking = Booking::findOrFail($id);

        // Validate booking is for Timed Entry only
        if (!$booking->isTimedEntry()) {
            Log::warning('sendTicket 422: Not timed entry', ['booking_id' => $id, 'product_id' => $booking->bokun_product_id]);
            return response()->json([
                'success' => false,
                'error' => 'Ticket sending is only available for Timed Entry tickets',
            ], 422);
        }

        // Validate booking has reference number
        if (!$booking->reference_number) {
            Log::warning('sendTicket 422: No reference number', ['booking_id' => $id]);
            return response()->json([
                'success' => false,
                'error' => 'Booking must have a ticket reference number before sending',
            ], 422);
        }

        // Validate audio guide credentials if booking has audio guide
        if ($booking->has_audio_guide) {
            if (!$booking->audio_guide_username || !$booking->audio_guide_password) {
                Log::warning('sendTicket 422: Missing audio credentials', ['booking_id' => $id]);
                return response()->json([
                    'success' => false,
                    'error' => 'Audio guide credentials are required for bookings with audio guide',
                ], 422);
            }
        }

        $validated = $request->validate([
            'language' => 'sometimes|string',
            'attachment_ids' => 'sometimes|array',
            'attachment_ids.*' => 'integer|exists:message_attachments,id',
            'custom_subject' => 'sometimes|string|max:255',
            'custom_content' => 'sometimes|string|min:50',
        ]);

        $language = $validated['language'] ?? 'en';
        $attachmentIds = $validated['attachment_ids'] ?? [];
        $customMessage = null;

        Log::info('=== SEND TICKET: VALIDATED DATA ===', [
            'booking_id' => $id,
            'language' => $language,
            'attachment_ids' => $attachmentIds,
            'attachment_count' => count($attachmentIds),
        ]);

        // Handle custom message
        if ($language === 'custom') {
            if (empty($validated['custom_subject']) || empty($validated['custom_content'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Custom message requires both subject and content',
                ], 422);
            }
            $customMessage = [
                'subject' => $validated['custom_subject'],
                'content' => $validated['custom_content'],
            ];
        }

        // Validate at least one attachment
        if (empty($attachmentIds)) {
            Log::warning('sendTicket 422: No attachments', ['booking_id' => $id, 'attachment_ids' => $attachmentIds]);
            return response()->json([
                'success' => false,
                'error' => 'At least one PDF attachment is required',
            ], 422);
        }

        // CRITICAL: Verify all attachments belong to THIS booking
        $validAttachments = \App\Models\MessageAttachment::whereIn('id', $attachmentIds)
            ->where('booking_id', $booking->id)
            ->get();

        if ($validAttachments->count() !== count($attachmentIds)) {
            $invalidIds = array_diff($attachmentIds, $validAttachments->pluck('id')->toArray());
            Log::error('SECURITY: Attachment mismatch - possible wrong PDF!', [
                'booking_id' => $id,
                'requested_ids' => $attachmentIds,
                'valid_ids' => $validAttachments->pluck('id')->toArray(),
                'invalid_ids' => $invalidIds,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'One or more attachments do not belong to this booking. Please re-upload the correct PDF.',
            ], 422);
        }

        Log::info('Attachment validation passed', [
            'booking_id' => $id,
            'valid_attachment_ids' => $validAttachments->pluck('id')->toArray(),
            'filenames' => $validAttachments->pluck('original_name')->toArray(),
        ]);

        try {
            $result = $this->messagingService->sendTicket($booking, $language, $attachmentIds, $customMessage);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket sent successfully',
                    'channel_used' => $result['channel_used'],
                    'messages' => collect($result['messages'])->map(function ($msg) {
                        return [
                            'id' => $msg->id,
                            'channel' => $msg->channel,
                            'status' => $msg->status,
                            'recipient' => $msg->recipient,
                        ];
                    }),
                ]);
            }

            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to send ticket', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send ticket: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect which channel will be used
     * GET /api/bookings/{id}/detect-channel
     */
    public function detectChannel(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $channel = $this->messagingService->detectChannel($booking);

        return response()->json($channel);
    }

    /**
     * Preview message content
     * POST /api/messages/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'language' => 'sometimes|string',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $language = $validated['language'] ?? 'en';

        // For custom language, return empty previews (custom content handled in frontend)
        if ($language === 'custom') {
            return response()->json([
                'channel_detection' => $this->messagingService->detectChannel($booking),
                'previews' => [],
            ]);
        }

        $previews = $this->messagingService->preview($booking, $language);
        $channel = $this->messagingService->detectChannel($booking);

        return response()->json([
            'channel_detection' => $channel,
            'previews' => $previews,
        ]);
    }

    /**
     * Get message history for a booking
     * GET /api/bookings/{id}/messages
     */
    public function history(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $messages = $this->messagingService->getHistory($booking);

        return response()->json([
            'messages' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'channel' => $msg->channel,
                    'recipient' => $msg->recipient,
                    'status' => $msg->status,
                    'content' => $msg->content,
                    'subject' => $msg->subject,
                    'error_message' => $msg->error_message,
                    'sent_at' => $msg->sent_at?->toIso8601String(),
                    'delivered_at' => $msg->delivered_at?->toIso8601String(),
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Get available templates
     * GET /api/messages/templates
     */
    public function templates(Request $request): JsonResponse
    {
        $channel = $request->query('channel');
        $language = $request->query('language');

        $query = MessageTemplate::where('is_active', true);

        if ($channel) {
            $query->where('channel', $channel);
        }

        if ($language) {
            $query->where('language', $language);
        }

        $templates = $query->orderBy('channel')
            ->orderBy('language')
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json([
            'templates' => $templates->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                    'channel' => $t->channel,
                    'language' => $t->language,
                    'subject' => $t->subject,
                    'content' => $t->content,
                    'is_default' => $t->is_default,
                ];
            }),
            'languages' => MessageTemplate::LANGUAGES,
            'channels' => MessageTemplate::CHANNELS,
        ]);
    }
}
