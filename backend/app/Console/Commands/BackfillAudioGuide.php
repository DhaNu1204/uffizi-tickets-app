<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BokunService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillAudioGuide extends Command
{
    protected $signature = 'bookings:backfill-audio-guide
                            {--limit=50 : Limit number of bookings to process per run}
                            {--dry-run : Preview what would be updated without making changes}';

    protected $description = 'Backfill audio guide information for existing Timed Entry Ticket bookings';

    public function handle(BokunService $bokunService): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Audio Guide Backfill');
        $this->line('  - Product: Timed Entry Tickets (961802)');
        $this->line('  - Audio Guide Rate: TG2 (2263305)');
        $this->line('  - Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->line('  - Limit: ' . $limit);
        $this->newLine();

        // Get Timed Entry bookings that haven't been checked yet
        // We'll process all product 961802 bookings that don't have audio guide set
        // (default is false, so we need another way to track if checked)
        $bookings = Booking::where('bokun_product_id', Booking::TIMED_ENTRY_PRODUCT_ID)
            ->whereNull('deleted_at')
            ->orderBy('tour_date', 'desc')
            ->limit($limit)
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No Timed Entry bookings found to process.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$bookings->count()} Timed Entry bookings...");
        $this->newLine();

        $updated = 0;
        $audioGuideCount = 0;
        $ticketOnlyCount = 0;
        $failed = 0;
        $alreadySet = 0;

        foreach ($bookings as $index => $booking) {
            try {
                // Fetch booking details from Bokun
                $details = $bokunService->getBookingDetails($booking->bokun_booking_id);

                if (!$details) {
                    $this->line("  - {$booking->bokun_booking_id}: Failed to fetch details");
                    $failed++;
                    continue;
                }

                // Extract audio guide status
                $hasAudioGuide = BokunService::extractHasAudioGuide($details, $booking->bokun_product_id);

                if ($hasAudioGuide) {
                    $audioGuideCount++;
                    $label = '[AUDIO GUIDE]';
                } else {
                    $ticketOnlyCount++;
                    $label = '[TICKET ONLY]';
                }

                // Check if value changed
                $currentValue = (bool) $booking->has_audio_guide;
                if ($currentValue === $hasAudioGuide) {
                    $this->line("  = {$booking->bokun_booking_id}: {$label} (no change)");
                    $alreadySet++;
                } else {
                    if (!$dryRun) {
                        $booking->has_audio_guide = $hasAudioGuide;
                        $booking->save();
                    }
                    $this->line("  ✓ {$booking->bokun_booking_id}: {$label} " . ($dryRun ? '(would update)' : '(updated)'));
                    $updated++;
                }

                // Rate limiting - 150ms between API calls
                usleep(150000);

                // Progress indicator
                if (($index + 1) % 10 === 0) {
                    $this->info("Progress: " . ($index + 1) . "/{$bookings->count()}");
                }

            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ {$booking->bokun_booking_id}: {$e->getMessage()}");
                Log::warning('Audio guide backfill failed', [
                    'booking_id' => $booking->bokun_booking_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        $this->info('Backfill Summary:');
        $this->line("  - Processed: {$bookings->count()}");
        $this->line("  - Audio Guide bookings: {$audioGuideCount}");
        $this->line("  - Ticket Only bookings: {$ticketOnlyCount}");
        $this->line("  - Updated: {$updated}");
        $this->line("  - Already correct: {$alreadySet}");
        $this->line("  - Failed: {$failed}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN - No changes were made. Run without --dry-run to apply changes.');
        }

        // Check remaining
        $remaining = Booking::where('bokun_product_id', Booking::TIMED_ENTRY_PRODUCT_ID)
            ->whereNull('deleted_at')
            ->count();

        $this->newLine();
        $this->info("Total Timed Entry bookings in database: {$remaining}");

        Log::info('Audio guide backfill completed', [
            'processed' => $bookings->count(),
            'audio_guide' => $audioGuideCount,
            'ticket_only' => $ticketOnlyCount,
            'updated' => $updated,
            'failed' => $failed,
            'dry_run' => $dryRun,
        ]);

        return Command::SUCCESS;
    }
}
