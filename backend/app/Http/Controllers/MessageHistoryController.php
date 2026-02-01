<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageHistoryController extends Controller
{
    /**
     * List all messages with filtering and pagination
     * GET /api/messages
     */
    public function index(Request $request): JsonResponse
    {
        $query = Message::with(['booking:id,customer_name,customer_phone,bokun_booking_id'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by channel
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by booking_id
        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by recipient or booking info
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('bokun_booking_id', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $messages = $query->paginate($perPage);

        return response()->json($messages);
    }

    /**
     * Get message details
     * GET /api/messages/{id}
     */
    public function show(int $id): JsonResponse
    {
        $message = Message::with(['booking', 'attachments'])->findOrFail($id);
        return response()->json($message);
    }

    /**
     * Get message statistics
     * GET /api/messages/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $stats = [
            'total' => Message::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])->count(),
            'by_status' => Message::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_channel' => Message::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->selectRaw('channel, count(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel'),
            'failed' => Message::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->where('status', 'failed')
                ->count(),
            'success_rate' => null,
        ];

        if ($stats['total'] > 0) {
            $delivered = Message::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->whereIn('status', ['sent', 'delivered', 'read'])
                ->count();
            $stats['success_rate'] = round(($delivered / $stats['total']) * 100, 1);
        }

        return response()->json($stats);
    }

    /**
     * Retry a failed message
     * POST /api/messages/{id}/retry
     */
    public function retry(int $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        if ($message->status !== 'failed') {
            return response()->json([
                'success' => false,
                'error' => 'Only failed messages can be retried'
            ], 422);
        }

        // Mark for retry - actual retry would need to re-send via MessagingService
        $message->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message marked for retry'
        ]);
    }
}
