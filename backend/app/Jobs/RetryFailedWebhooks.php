<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\WebhookLog;
use App\Services\BokunService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $maxRetries;

    /**
     * Create a new job instance.
     */
    public function __construct(int $maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $failedWebhooks = WebhookLog::retryable($this->maxRetries)
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();

        Log::info("RetryFailedWebhooks: Processing {$failedWebhooks->count()} failed webhooks");

        foreach ($failedWebhooks as $webhook) {
            $this->retryWebhook($webhook);
        }
    }

    /**
     * Retry processing a single webhook.
     */
    private function retryWebhook(WebhookLog $webhook): void
    {
        Log::info("Retrying webhook", [
            'webhook_id' => $webhook->id,
            'retry_count' => $webhook->retry_count,
            'event_type' => $webhook->event_type
        ]);

        try {
            $webhook->resetForRetry();

            $result = $this->processWebhookPayload($webhook);

            $webhook->markAsProcessed();

            Log::info("Webhook retry successful", [
                'webhook_id' => $webhook->id,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            $webhook->markAsFailed($e->getMessage());

            Log::error("Webhook retry failed", [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process the webhook payload.
     */
    private function processWebhookPayload(WebhookLog $webhook): array
    {
        $payload = $webhook->payload;
        $eventType = $webhook->event_type;

        // Handle cancellation events
        if (in_array($eventType, ['CANCELLED', 'bookings/cancelled', 'cancelled'])) {
            return $this->handleCancellation($payload);
        }

        // Handle booking creation/update events
        return $this->handleBookingEvent($payload);
    }

    /**
     * Handle booking cancellation.
     */
    private function handleCancellation(array $payload): array
    {
        $confirmationCode = $payload['confirmationCode'] ?? null;

        if (!$confirmationCode) {
            return ['message' => 'No confirmation code for cancellation', 'cancelled' => 0];
        }

        $booking = Booking::where('bokun_booking_id', $confirmationCode)->first();

        if ($booking) {
            $booking->delete();
            return ['message' => 'Booking cancelled', 'cancelled' => 1];
        }

        return ['message' => 'Booking not found for cancellation', 'cancelled' => 0];
    }

    /**
     * Handle booking creation/update event.
     */
    private function handleBookingEvent(array $payload): array
    {
        if (!isset($payload['productBookings'])) {
            return ['message' => 'No product bookings found', 'processed' => 0];
        }

        $uffiziProductIds = BokunService::getUffiziProductIds();
        $processedCount = 0;

        foreach ($payload['productBookings'] as $pb) {
            $productId = (string) ($pb['product']['id'] ?? '');

            if (in_array($productId, $uffiziProductIds)) {
                Booking::updateOrCreate(
                    ['bokun_booking_id' => $payload['confirmationCode'] ?? $pb['confirmationCode'] ?? 'UNKNOWN'],
                    [
                        'bokun_product_id' => $productId,
                        'product_name' => $pb['product']['title'] ?? 'Uffizi Tour',
                        'customer_name' => ($payload['customer']['firstName'] ?? 'Guest') . ' ' . ($payload['customer']['lastName'] ?? ''),
                        'tour_date' => isset($pb['date']) ? Carbon::parse($pb['date']) : now(),
                        'pax' => count($pb['passengers'] ?? []),
                    ]
                );
                $processedCount++;
            }
        }

        return ['message' => 'Processed', 'count' => $processedCount];
    }
}
