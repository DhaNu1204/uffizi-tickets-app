<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use App\Jobs\RetryFailedWebhooks;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * List webhook logs with filtering and pagination.
     *
     * Query Parameters:
     * - status: Filter by status (pending, processed, failed)
     * - event_type: Filter by event type
     * - confirmation_code: Filter by confirmation code
     * - date_from: Filter from date (YYYY-MM-DD)
     * - date_to: Filter until date (YYYY-MM-DD)
     * - per_page: Items per page (default 20, max 100)
     * - sort_by: Sort field (created_at, processed_at, status)
     * - sort_dir: Sort direction (asc, desc)
     */
    public function index(Request $request)
    {
        $query = WebhookLog::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by confirmation code
        if ($request->filled('confirmation_code')) {
            $query->where('confirmation_code', 'like', '%' . $request->confirmation_code . '%');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'processed_at', 'status', 'event_type', 'retry_count'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 20), 100);
        $webhooks = $query->paginate($perPage);

        return response()->json($webhooks);
    }

    /**
     * Get a single webhook log by ID.
     */
    public function show($id)
    {
        $webhook = WebhookLog::findOrFail($id);
        return response()->json($webhook);
    }

    /**
     * Get webhook statistics.
     */
    public function stats()
    {
        $stats = [
            'total' => WebhookLog::count(),
            'pending' => WebhookLog::where('status', 'pending')->count(),
            'processed' => WebhookLog::where('status', 'processed')->count(),
            'failed' => WebhookLog::where('status', 'failed')->count(),
            'retryable' => WebhookLog::retryable(3)->count(),
            'by_event_type' => WebhookLog::selectRaw('event_type, status, COUNT(*) as count')
                ->groupBy('event_type', 'status')
                ->get(),
            'recent_failures' => WebhookLog::where('status', 'failed')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'event_type', 'confirmation_code', 'error_message', 'retry_count', 'created_at']),
        ];

        return response()->json($stats);
    }

    /**
     * Retry a single failed webhook.
     */
    public function retry($id)
    {
        $webhook = WebhookLog::findOrFail($id);

        if ($webhook->status === 'processed') {
            return response()->json([
                'message' => 'Webhook already processed',
            ], 400);
        }

        // Reset and dispatch for retry
        $webhook->resetForRetry();

        // Process synchronously for immediate feedback
        try {
            $result = $this->processWebhook($webhook);
            $webhook->markAsProcessed();

            return response()->json([
                'message' => 'Webhook retried successfully',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            $webhook->markAsFailed($e->getMessage());

            return response()->json([
                'message' => 'Webhook retry failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry all failed webhooks.
     */
    public function retryAll(Request $request)
    {
        $maxRetries = (int) $request->input('max_retries', 3);
        $retryableCount = WebhookLog::retryable($maxRetries)->count();

        if ($retryableCount === 0) {
            return response()->json([
                'message' => 'No failed webhooks to retry',
                'retryable_count' => 0,
            ]);
        }

        RetryFailedWebhooks::dispatch($maxRetries);

        return response()->json([
            'message' => 'Retry job dispatched',
            'retryable_count' => $retryableCount,
        ]);
    }

    /**
     * Delete old webhook logs.
     */
    public function cleanup(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'status' => 'nullable|in:pending,processed,failed',
        ]);

        $days = $validated['days'];
        $status = $validated['status'] ?? null;

        $query = WebhookLog::where('created_at', '<', now()->subDays($days));

        if ($status) {
            $query->where('status', $status);
        }

        $count = $query->count();
        $query->delete();

        return response()->json([
            'message' => "Deleted {$count} webhook logs older than {$days} days",
            'deleted_count' => $count,
        ]);
    }

    /**
     * Process a webhook (used for retry).
     */
    private function processWebhook(WebhookLog $webhook): array
    {
        $payload = $webhook->payload;
        $eventType = $webhook->event_type;

        // Handle cancellation events
        if (in_array($eventType, ['CANCELLED', 'bookings/cancelled', 'cancelled'])) {
            $confirmationCode = $payload['confirmationCode'] ?? null;

            if (!$confirmationCode) {
                return ['message' => 'No confirmation code for cancellation', 'cancelled' => 0];
            }

            $booking = \App\Models\Booking::where('bokun_booking_id', $confirmationCode)->first();

            if ($booking) {
                $booking->delete();
                return ['message' => 'Booking cancelled', 'cancelled' => 1];
            }

            return ['message' => 'Booking not found for cancellation', 'cancelled' => 0];
        }

        // Handle booking creation/update events
        if (!isset($payload['productBookings'])) {
            return ['message' => 'No product bookings found', 'processed' => 0];
        }

        $uffiziProductIds = \App\Services\BokunService::getUffiziProductIds();
        $processedCount = 0;

        foreach ($payload['productBookings'] as $pb) {
            $productId = (string) ($pb['product']['id'] ?? '');

            if (in_array($productId, $uffiziProductIds)) {
                \App\Models\Booking::updateOrCreate(
                    ['bokun_booking_id' => $payload['confirmationCode'] ?? $pb['confirmationCode'] ?? 'UNKNOWN'],
                    [
                        'bokun_product_id' => $productId,
                        'product_name' => $pb['product']['title'] ?? 'Uffizi Tour',
                        'customer_name' => ($payload['customer']['firstName'] ?? 'Guest') . ' ' . ($payload['customer']['lastName'] ?? ''),
                        'tour_date' => isset($pb['date']) ? \Carbon\Carbon::parse($pb['date']) : now(),
                        'pax' => count($pb['passengers'] ?? []),
                    ]
                );
                $processedCount++;
            }
        }

        return ['message' => 'Processed', 'count' => $processedCount];
    }
}
