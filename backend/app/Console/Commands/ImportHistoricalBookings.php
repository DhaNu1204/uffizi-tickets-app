<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BokunService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportHistoricalBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:import
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--page-size=100 : Number of bookings per page}
                            {--dry-run : Show what would be imported without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import historical bookings from Bokun API';

    private BokunService $bokunService;

    public function __construct(BokunService $bokunService)
    {
        parent::__construct();
        $this->bokunService = $bokunService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('from') ?? Carbon::now()->subYear()->format('Y-m-d');
        $endDate = $this->option('to') ?? Carbon::now()->format('Y-m-d');
        $pageSize = (int) $this->option('page-size');
        $dryRun = $this->option('dry-run');

        $this->info("Importing bookings from {$startDate} to {$endDate}");
        if ($dryRun) {
            $this->warn('DRY RUN - No data will be saved');
        }

        $uffiziProductIds = BokunService::getUffiziProductIds();
        if (empty($uffiziProductIds)) {
            $this->error('No Uffizi product IDs configured. Check UFFIZI_PRODUCT_IDS in .env');
            return 1;
        }

        $this->info('Looking for Uffizi products: ' . implode(', ', $uffiziProductIds));

        $page = 1;
        $totalImported = 0;
        $totalDuplicates = 0;
        $hasMore = true;

        $this->output->progressStart();

        while ($hasMore) {
            $this->output->progressAdvance();

            $result = $this->bokunService->getHistoricalBookings($startDate, $endDate, $page, $pageSize);

            if (!$result['success']) {
                $this->error("\nAPI Error on page {$page}: " . ($result['error'] ?? 'Unknown error'));
                Log::error('Historical booking import failed', ['page' => $page, 'error' => $result['error'] ?? null]);
                return 1;
            }

            $bookings = $result['results'];
            $totalCount = $result['totalCount'];

            if (empty($bookings)) {
                $hasMore = false;
                break;
            }

            foreach ($bookings as $booking) {
                $imported = $this->processBooking($booking, $uffiziProductIds, $dryRun);
                if ($imported === true) {
                    $totalImported++;
                } elseif ($imported === 'duplicate') {
                    $totalDuplicates++;
                }
            }

            // Check if there are more pages
            $hasMore = count($bookings) === $pageSize && ($page * $pageSize) < $totalCount;
            $page++;

            // Rate limiting: Bokun allows 400 requests/minute
            // Sleep briefly between pages to be safe
            usleep(150000); // 150ms delay
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info("Import completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Pages processed', $page - 1],
                ['New bookings imported', $totalImported],
                ['Duplicate bookings skipped', $totalDuplicates],
            ]
        );

        return 0;
    }

    /**
     * Process a single booking from the API
     *
     * @return bool|string true if imported, 'duplicate' if skipped, false if not Uffizi
     */
    private function processBooking(array $booking, array $uffiziProductIds, bool $dryRun): bool|string
    {
        $productBookings = $booking['productBookings'] ?? [];

        foreach ($productBookings as $pb) {
            $productId = (string) ($pb['product']['id'] ?? '');

            if (in_array($productId, $uffiziProductIds)) {
                $confirmationCode = $booking['confirmationCode'] ?? 'UNKNOWN';

                // Check for duplicate
                if (Booking::where('bokun_booking_id', $confirmationCode)->exists()) {
                    return 'duplicate';
                }

                if ($dryRun) {
                    $this->line("Would import: {$confirmationCode} - {$pb['product']['title']} on " . ($pb['date'] ?? 'unknown date'));
                    return true;
                }

                try {
                    Booking::create([
                        'bokun_booking_id' => $confirmationCode,
                        'bokun_product_id' => $productId,
                        'product_name' => $pb['product']['title'] ?? 'Uffizi Tour',
                        'customer_name' => ($booking['customer']['firstName'] ?? 'Guest') . ' ' . ($booking['customer']['lastName'] ?? ''),
                        'tour_date' => isset($pb['date']) ? Carbon::parse($pb['date']) : now(),
                        'pax' => count($pb['passengers'] ?? []),
                        'status' => 'PENDING_TICKET',
                    ]);

                    return true;
                } catch (\Exception $e) {
                    $this->error("Failed to import {$confirmationCode}: " . $e->getMessage());
                    Log::error('Booking import failed', [
                        'confirmation_code' => $confirmationCode,
                        'error' => $e->getMessage()
                    ]);
                    return false;
                }
            }
        }

        return false;
    }
}
