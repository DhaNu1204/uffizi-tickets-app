<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOldBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:cleanup
                            {--months=2 : Delete bookings older than this many months (default: 2)}
                            {--dry-run : Preview what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old bookings (past tour dates) to keep the database clean';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = (int) $this->option('months');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $cutoffDate = Carbon::now()->subMonths($months)->startOfDay();

        $this->info("Cleanup Settings:");
        $this->line("  - Delete bookings with tour_date before: {$cutoffDate->format('Y-m-d')}");
        $this->line("  - Mode: " . ($dryRun ? 'DRY RUN (no changes)' : 'LIVE'));
        $this->newLine();

        // Find bookings to delete
        $bookingsToDelete = Booking::where('tour_date', '<', $cutoffDate)
            ->whereNull('deleted_at')
            ->orderBy('tour_date')
            ->get();

        if ($bookingsToDelete->isEmpty()) {
            $this->info("No bookings found older than {$months} months. Nothing to clean up.");
            return Command::SUCCESS;
        }

        // Show summary
        $this->warn("Found {$bookingsToDelete->count()} bookings to delete:");
        $this->newLine();

        // Group by month for summary
        $byMonth = $bookingsToDelete->groupBy(function ($booking) {
            return $booking->tour_date->format('Y-m');
        });

        $this->table(
            ['Month', 'Bookings', 'Date Range'],
            $byMonth->map(function ($bookings, $month) {
                return [
                    $month,
                    $bookings->count(),
                    $bookings->min('tour_date')->format('M d') . ' - ' . $bookings->max('tour_date')->format('M d'),
                ];
            })->values()->toArray()
        );

        $this->newLine();

        // Show status breakdown
        $statusBreakdown = $bookingsToDelete->groupBy('status');
        $this->line("Status breakdown:");
        foreach ($statusBreakdown as $status => $bookings) {
            $this->line("  - {$status}: {$bookings->count()}");
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("DRY RUN complete. No bookings were deleted.");
            $this->line("Run without --dry-run to actually delete these bookings.");
            return Command::SUCCESS;
        }

        // Confirm deletion
        if (!$force && !$this->confirm("Are you sure you want to permanently delete {$bookingsToDelete->count()} bookings?")) {
            $this->info("Cleanup cancelled.");
            return Command::SUCCESS;
        }

        // Perform deletion
        $this->info("Deleting bookings...");

        $deletedCount = 0;
        $errors = [];

        foreach ($bookingsToDelete as $booking) {
            try {
                // Force delete (not soft delete) to actually remove from database
                $booking->forceDelete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'booking_id' => $booking->bokun_booking_id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Log the cleanup
        Log::info('Bookings cleanup completed', [
            'months_threshold' => $months,
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
            'deleted_count' => $deletedCount,
            'errors_count' => count($errors),
        ]);

        $this->newLine();
        $this->info("Cleanup complete!");
        $this->line("  - Deleted: {$deletedCount} bookings");

        if (!empty($errors)) {
            $this->error("  - Errors: " . count($errors));
            foreach ($errors as $error) {
                $this->line("    - {$error['booking_id']}: {$error['error']}");
            }
        }

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }
}
