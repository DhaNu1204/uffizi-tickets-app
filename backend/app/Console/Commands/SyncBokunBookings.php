<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BokunService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBokunBookings extends Command
{
    protected $signature = 'bokun:sync
        {--full : Fetch participant details for ALL bookings without them}
        {--limit=50 : Limit participant fetches per run (default 50)}';

    protected $description = 'Sync bookings from Bokun API and fetch participant details';

    private array $uffiziProductIds;

    public function handle(BokunService $bokunService): int
    {
        $this->uffiziProductIds = BokunService::getUffiziProductIds();
        $fullSync = $this->option('full');
        $limit = (int) $this->option('limit');

        $this->info('Starting Bokun sync...');
        Log::info('Bokun sync started', ['full' => $fullSync, 'limit' => $limit]);

        // Step 1: Sync all bookings from API
        $results = $bokunService->getUpcomingBookings();
        $this->info("Fetched " . count($results) . " bookings from Bokun API");

        $synced = 0;
        $skipped = 0;

        foreach ($results as $booking) {
            $productId = (string) ($booking['product']['id'] ?? '');

            if (!in_array($productId, $this->uffiziProductIds)) {
                $skipped++;
                continue;
            }

            $this->syncBooking($booking);
            $synced++;
        }

        $this->info("Synced: $synced bookings, Skipped: $skipped (non-Uffizi)");

        // Step 2: Check for cancelled bookings
        $cancelled = $this->checkForCancellations($bokunService, $results);
        if ($cancelled > 0) {
            $this->info("Removed $cancelled cancelled bookings");
        }

        // Step 3: Fetch details for bookings that need participants, booking channel, OR customer contact
        $query = Booking::where(function ($q) {
                $q->whereNull('participants')
                  ->orWhereNull('booking_channel')
                  ->orWhereNull('customer_email');
            })
            ->orderBy('tour_date', 'asc');

        if (!$fullSync) {
            $query->limit($limit);
        }

        $bookingsNeedingDetails = $query->get();
        $total = $bookingsNeedingDetails->count();

        if ($total === 0) {
            $this->info('All bookings already have participant and channel data.');
            Log::info('Bokun sync completed - all data populated', ['synced' => $synced]);
            return Command::SUCCESS;
        }

        $this->info("Fetching details for $total bookings...");

        $updated = 0;
        $failed = 0;

        foreach ($bookingsNeedingDetails as $index => $booking) {
            try {
                $details = $bokunService->getBookingDetails($booking->bokun_booking_id);

                if ($details) {
                    $participants = BokunService::extractParticipants($details);
                    $bookingChannel = BokunService::extractBookingChannel($details);
                    $customerContact = BokunService::extractCustomerContact($details);
                    $hasAudioGuide = BokunService::extractHasAudioGuide($details, $booking->bokun_product_id);

                    // Always save the booking channel, customer contact, and audio guide flag
                    $booking->booking_channel = $bookingChannel;
                    $booking->customer_email = $customerContact['email'];
                    $booking->customer_phone = $customerContact['phone'];
                    $booking->has_audio_guide = $hasAudioGuide;

                    if (!empty($participants)) {
                        $booking->participants = $participants;
                        $booking->save();
                        $updated++;

                        // Show warning if participant count < pax (incomplete data)
                        $pax = $booking->pax;
                        $participantCount = count($participants);
                        $audioGuideLabel = $hasAudioGuide ? ' [AUDIO GUIDE]' : '';
                        if ($participantCount < $pax) {
                            $this->line("  ⚠ {$booking->bokun_booking_id}: {$participantCount}/{$pax} participants ({$bookingChannel}){$audioGuideLabel}");
                        } else {
                            $this->line("  ✓ {$booking->bokun_booking_id}: {$participantCount} participants{$audioGuideLabel}");
                        }
                    } else {
                        // Mark as empty array to avoid re-fetching
                        $booking->participants = [];
                        $booking->save();
                        $audioGuideLabel = $hasAudioGuide ? ' [AUDIO GUIDE]' : '';
                        $this->line("  - {$booking->bokun_booking_id}: no participant data available ({$bookingChannel}){$audioGuideLabel}");
                    }
                }

                // Rate limiting - 150ms between API calls
                usleep(150000);

            } catch (\Exception $e) {
                $failed++;
                Log::warning('Failed to fetch booking details', [
                    'booking_id' => $booking->bokun_booking_id,
                    'error' => $e->getMessage()
                ]);
                $this->error("  ✗ {$booking->bokun_booking_id}: {$e->getMessage()}");
            }

            // Progress indicator
            if (($index + 1) % 10 === 0) {
                $this->info("Progress: " . ($index + 1) . "/$total");
            }
        }

        $remaining = Booking::where(function ($q) {
            $q->whereNull('participants')
              ->orWhereNull('booking_channel')
              ->orWhereNull('customer_email');
        })->count();

        $this->newLine();
        $this->info("Completed: $updated updated, $failed failed, $remaining remaining");

        Log::info('Bokun sync completed', [
            'synced' => $synced,
            'participants_updated' => $updated,
            'failed' => $failed,
            'remaining' => $remaining
        ]);

        return Command::SUCCESS;
    }

    /**
     * Check for bookings in our DB that are no longer in the API (cancelled)
     */
    private function checkForCancellations(BokunService $bokunService, array $apiResults): int
    {
        // Get all confirmation codes from API results
        $apiConfirmationCodes = collect($apiResults)->map(function ($booking) {
            return $booking['confirmationCode'] ?? $booking['productConfirmationCode'] ?? null;
        })->filter()->toArray();

        // Find bookings in our DB with future dates that are NOT in the API results
        $potentiallyCancelled = Booking::whereDate('tour_date', '>=', Carbon::today())
            ->whereNotIn('bokun_booking_id', $apiConfirmationCodes)
            ->get();

        if ($potentiallyCancelled->isEmpty()) {
            return 0;
        }

        $this->info("Checking " . $potentiallyCancelled->count() . " bookings for cancellations...");

        $cancelledCount = 0;

        foreach ($potentiallyCancelled as $booking) {
            try {
                // Check booking status in Bokun
                $details = $bokunService->getBookingDetails($booking->bokun_booking_id);

                if ($details && isset($details['status']) && $details['status'] === 'CANCELLED') {
                    // Set cancelled_at timestamp before soft deleting
                    $booking->cancelled_at = now();
                    $booking->save();
                    $booking->delete(); // Soft delete (sets deleted_at)
                    $cancelledCount++;
                    $this->line("  ✗ {$booking->bokun_booking_id}: CANCELLED - removed");
                    Log::info('Booking cancelled and removed', [
                        'booking_id' => $booking->bokun_booking_id,
                        'customer' => $booking->customer_name
                    ]);
                }

                // Rate limiting
                usleep(100000);

            } catch (\Exception $e) {
                Log::warning('Failed to check booking cancellation status', [
                    'booking_id' => $booking->bokun_booking_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $cancelledCount;
    }

    private function syncBooking(array $booking): void
    {
        $confirmationCode = $booking['confirmationCode'] ?? $booking['productConfirmationCode'] ?? 'UNKNOWN';
        $productId = (string) ($booking['product']['id'] ?? '');

        // Parse tour date
        $tourDate = now();
        if (isset($booking['startDateTime'])) {
            $tourDate = Carbon::createFromTimestampMs($booking['startDateTime']);
        } elseif (isset($booking['startDate'])) {
            $tourDate = Carbon::createFromTimestampMs($booking['startDate']);
        }

        // Get customer name
        $customerName = trim(
            ($booking['customer']['firstName'] ?? 'Guest') . ' ' .
            ($booking['customer']['lastName'] ?? '')
        );

        // Extract PAX details
        $paxCounts = [];
        if (isset($booking['fields']['priceCategoryBookings']) && is_array($booking['fields']['priceCategoryBookings'])) {
            foreach ($booking['fields']['priceCategoryBookings'] as $priceCat) {
                $title = $priceCat['bookedTitle'] ?? ($priceCat['pricingCategory']['title'] ?? 'Guest');
                $quantity = $priceCat['quantity'] ?? 1;
                if ($quantity > 0) {
                    $paxCounts[$title] = ($paxCounts[$title] ?? 0) + $quantity;
                }
            }
        }

        $paxDetails = [];
        foreach ($paxCounts as $type => $quantity) {
            $paxDetails[] = ['type' => $type, 'quantity' => $quantity];
        }

        // Get existing booking to preserve participants
        $existingBooking = Booking::where('bokun_booking_id', $confirmationCode)->first();

        Booking::updateOrCreate(
            ['bokun_booking_id' => $confirmationCode],
            [
                'bokun_product_id' => $productId,
                'product_name' => $booking['product']['title'] ?? 'Uffizi Tour',
                'customer_name' => $customerName,
                'tour_date' => $tourDate,
                'pax' => $booking['totalParticipants'] ?? 1,
                'pax_details' => !empty($paxDetails) ? $paxDetails : null,
                // Keep existing participants if already set
                'participants' => $existingBooking?->participants,
            ]
        );
    }
}
