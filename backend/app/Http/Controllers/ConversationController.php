<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\IncomingMessageService;
use App\Services\TwilioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    protected TwilioService $twilioService;
    protected IncomingMessageService $incomingService;

    public function __construct(TwilioService $twilioService, IncomingMessageService $incomingService)
    {
        $this->twilioService = $twilioService;
        $this->incomingService = $incomingService;
    }

    /**
     * List all conversations
     * GET /api/conversations
     */
    public function index(Request $request): JsonResponse
    {
        $query = Conversation::query()
            ->with(['booking:id,customer_name,tour_date,bokun_booking_id', 'latestMessage'])
            ->orderByDesc('last_message_at');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to active conversations
            $query->active();
        }

        // Filter by channel
        if ($request->has('channel')) {
            $query->byChannel($request->channel);
        }

        // Filter by unread
        if ($request->boolean('unread_only')) {
            $query->withUnread();
        }

        // Search by phone number or booking name
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('booking', function ($bq) use ($search) {
                        $bq->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('bokun_booking_id', 'like', "%{$search}%");
                    });
            });
        }

        $conversations = $query->paginate($request->get('per_page', 20));

        // Transform the data to include computed fields
        $conversations->getCollection()->transform(function ($conversation) {
            return $this->transformConversation($conversation);
        });

        return response()->json($conversations);
    }

    /**
     * Get a single conversation with messages
     * GET /api/conversations/{id}
     */
    public function show(int $id): JsonResponse
    {
        $conversation = Conversation::with([
            'booking:id,customer_name,customer_email,tour_date,bokun_booking_id,product_name,pax',
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
        ])->findOrFail($id);

        // Mark as read when viewing
        $conversation->markAsRead();

        return response()->json([
            'conversation' => $this->transformConversation($conversation),
            'messages' => $conversation->messages->map(fn($msg) => $this->transformMessage($msg)),
        ]);
    }

    /**
     * Send a reply in a conversation
     * POST /api/conversations/{id}/reply
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:4096',
        ]);

        $conversation = Conversation::findOrFail($id);

        // Check WhatsApp 24-hour window
        if ($conversation->channel === Conversation::CHANNEL_WHATSAPP) {
            if (!$conversation->isWhatsAppWindowOpen()) {
                return response()->json([
                    'error' => 'WhatsApp 24-hour messaging window has expired. Cannot send free-form messages.',
                    'window_expired' => true,
                ], 422);
            }
        }

        try {
            $message = $this->twilioService->sendReply(
                $conversation,
                $request->message
            );

            Log::info('Reply sent', [
                'conversation_id' => $id,
                'message_id' => $message->id,
            ]);

            return response()->json([
                'message' => 'Reply sent successfully',
                'data' => $this->transformMessage($message),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send reply', [
                'conversation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to send reply: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark conversation as read
     * PUT /api/conversations/{id}/read
     */
    public function markRead(int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->markAsRead();

        return response()->json([
            'message' => 'Conversation marked as read',
        ]);
    }

    /**
     * Link conversation to a booking
     * PUT /api/conversations/{id}/booking
     */
    public function linkBooking(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $conversation = Conversation::findOrFail($id);

        $this->incomingService->linkToBooking($conversation, $request->booking_id);

        return response()->json([
            'message' => 'Conversation linked to booking',
        ]);
    }

    /**
     * Archive a conversation
     * DELETE /api/conversations/{id}
     */
    public function archive(int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->archive();

        return response()->json([
            'message' => 'Conversation archived',
        ]);
    }

    /**
     * Get unread count for badge display
     * GET /api/conversations/unread-count
     */
    public function unreadCount(): JsonResponse
    {
        $count = Conversation::active()
            ->where('unread_count', '>', 0)
            ->sum('unread_count');

        $conversationsWithUnread = Conversation::active()
            ->where('unread_count', '>', 0)
            ->count();

        return response()->json([
            'total_unread' => $count,
            'conversations_with_unread' => $conversationsWithUnread,
        ]);
    }

    /**
     * Transform conversation for API response
     */
    protected function transformConversation(Conversation $conversation): array
    {
        $latestMessage = $conversation->latestMessage->first();

        return [
            'id' => $conversation->id,
            'phone_number' => $conversation->phone_number,
            'channel' => $conversation->channel,
            'status' => $conversation->status,
            'unread_count' => $conversation->unread_count,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'display_name' => $conversation->getDisplayName(),
            'last_message_preview' => $conversation->getLastMessagePreview(60),
            'whatsapp_window_open' => $conversation->isWhatsAppWindowOpen(),
            'whatsapp_window_remaining' => $conversation->getWhatsAppWindowRemaining(),
            'booking' => $conversation->booking ? [
                'id' => $conversation->booking->id,
                'customer_name' => $conversation->booking->customer_name,
                'tour_date' => $conversation->booking->tour_date?->toIso8601String(),
                'bokun_booking_id' => $conversation->booking->bokun_booking_id,
            ] : null,
            'created_at' => $conversation->created_at->toIso8601String(),
        ];
    }

    /**
     * Transform message for API response
     */
    protected function transformMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'channel' => $message->channel,
            'content' => $message->content,
            'sender_name' => $message->sender_name,
            'status' => $message->status,
            'external_id' => $message->external_id,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'delivered_at' => $message->delivered_at?->toIso8601String(),
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
