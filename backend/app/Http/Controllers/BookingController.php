<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\WebhookLog;
use App\Services\BokunService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Get Uffizi Product IDs from config
     */
    private function getUffiziProductIds(): array
    {
        return BokunService::getUffiziProductIds();
    }

    /**
     * List bookings with filtering and pagination.
     *
     * Query Parameters:
     * - status: Filter by status (PENDING_TICKET, TICKET_PURCHASED)
     * - product_id: Filter by Bokun product ID
     * - date_from: Filter bookings from this date (YYYY-MM-DD)
     * - date_to: Filter bookings until this date (YYYY-MM-DD)
     * - search: Search customer name
     * - per_page: Items per page (default 20, max 100)
     * - page: Page number
     * - sort_by: Sort field (tour_date, created_at, customer_name)
     * - sort_dir: Sort direction (asc, desc)
     */
    public function index(Request $request)
    {
        $query = Booking::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by product ID
        if ($request->filled('product_id')) {
            $query->where('bokun_product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('tour_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tour_date', '<=', $request->date_to);
        }

        // Enhanced search: customer name, booking ID, reference number, participant names
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('bokun_booking_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('reference_number', 'like', '%' . $searchTerm . '%')
                  ->orWhereRaw("JSON_SEARCH(participants, 'one', ?, NULL, '$[*].name') IS NOT NULL", ['%' . $searchTerm . '%']);
            });
        }

        // Default: Sort by tour_date ASC (today first, then tomorrow, etc.)
        // Within each day, earlier times come first (morning before evening)
        $sortBy = $request->input('sort_by', 'tour_date');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['tour_date', 'created_at', 'customer_name', 'status', 'pax'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('tour_date', 'asc');
        }

        // Secondary sort by time within the same date
        if ($sortBy === 'tour_date') {
            $query->orderByRaw('TIME(tour_date) ASC');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 20), 100);
        $bookings = $query->paginate($perPage);

        return response()->json($bookings);
    }

    /**
     * Get a single booking by ID.
     */
    public function show($id)
    {
        $booking = Booking::findOrFail($id);
        return response()->json($booking);
    }

    /**
     * Create a new booking manually.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bokun_booking_id' => 'required|string|unique:bookings,bokun_booking_id',
            'bokun_product_id' => 'required|string',
            'product_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'tour_date' => 'required|date',
            'pax' => 'required|integer|min:1',
            'status' => 'nullable|in:PENDING_TICKET,TICKET_PURCHASED',
            'reference_number' => 'nullable|string|max:255',
        ]);

        $booking = Booking::create([
            'bokun_booking_id' => $validated['bokun_booking_id'],
            'bokun_product_id' => $validated['bokun_product_id'],
            'product_name' => $validated['product_name'],
            'customer_name' => $validated['customer_name'],
            'tour_date' => Carbon::parse($validated['tour_date']),
            'pax' => $validated['pax'],
            'status' => $validated['status'] ?? 'PENDING_TICKET',
            'reference_number' => $validated['reference_number'] ?? null,
        ]);

        return response()->json($booking, 201);
    }

    /**
     * Update booking status, reference number, notes, guide name, tickets sent, and audio guide sent status.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:PENDING_TICKET,TICKET_PURCHASED',
            'reference_number' => 'nullable|string|max:255',
            'customer_name' => 'sometimes|string|max:255',
            'tour_date' => 'sometimes|date',
            'pax' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'guide_name' => 'nullable|string|max:100',
            'tickets_sent' => 'sometimes|boolean',
            'audio_guide_sent' => 'sometimes|boolean',
        ]);

        $booking = Booking::findOrFail($id);

        if (isset($validated['status'])) {
            $booking->status = $validated['status'];
        }
        if (array_key_exists('reference_number', $validated)) {
            $booking->reference_number = $validated['reference_number'];
        }
        if (isset($validated['customer_name'])) {
            $booking->customer_name = $validated['customer_name'];
        }
        if (isset($validated['tour_date'])) {
            $booking->tour_date = Carbon::parse($validated['tour_date']);
        }
        if (isset($validated['pax'])) {
            $booking->pax = $validated['pax'];
        }
        if (array_key_exists('notes', $validated)) {
            $booking->notes = $validated['notes'];
        }
        if (array_key_exists('guide_name', $validated)) {
            $booking->guide_name = $validated['guide_name'];
        }
        // Handle tickets_sent toggle
        if (isset($validated['tickets_sent'])) {
            $booking->tickets_sent_at = $validated['tickets_sent'] ? Carbon::now() : null;
        }
        // Handle audio_guide_sent toggle (only for bookings with audio guides)
        if (isset($validated['audio_guide_sent'])) {
            $booking->audio_guide_sent_at = $validated['audio_guide_sent'] ? Carbon::now() : null;
        }

        $booking->save();

        // Clear stats cache when booking is updated
        $this->clearStatsCache();

        return response()->json($booking);
    }

    /**
     * Update wizard progress for a booking.
     * Called when user navigates through the ticket sending wizard.
     */
    public function updateWizardProgress(Request $request, $id)
    {
        $validated = $request->validate([
            'step' => 'required|integer|min:1|max:7',
            'action' => 'required|in:start,progress,abandon,complete,save_exit',
        ]);

        $booking = Booking::findOrFail($id);

        switch ($validated['action']) {
            case 'start':
                // Starting wizard - clear any previous abandonment
                $booking->wizard_started_at = Carbon::now();
                $booking->wizard_last_step = $validated['step'];
                $booking->wizard_abandoned_at = null;
                break;

            case 'progress':
                // Moving to a new step
                if (!$booking->wizard_started_at) {
                    $booking->wizard_started_at = Carbon::now();
                }
                $booking->wizard_last_step = $validated['step'];
                $booking->wizard_abandoned_at = null;
                break;

            case 'save_exit':
                // User saved progress and exited - keep as in-progress, not abandoned
                $booking->wizard_last_step = $validated['step'];
                $booking->wizard_abandoned_at = null;
                break;

            case 'abandon':
                // User closed wizard without completing
                $booking->wizard_abandoned_at = Carbon::now();
                break;

            case 'complete':
                // Wizard completed successfully
                $booking->wizard_abandoned_at = null;
                // tickets_sent_at will be set by the send-ticket endpoint
                break;
        }

        $booking->save();

        // Clear stats cache since pending counts may have changed
        $this->clearStatsCache();

        return response()->json([
            'success' => true,
            'wizard_status' => $booking->wizard_status,
        ]);
    }

    /**
     * Soft delete a booking.
     */
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        // Clear stats cache when booking is deleted
        $this->clearStatsCache();

        return response()->json(['message' => 'Booking deleted successfully']);
    }

    /**
     * Clear the stats cache.
     * Called when bookings are modified to ensure fresh data.
     */
    private function clearStatsCache(): void
    {
        // Clear all stats cache keys by pattern
        // Since we use date-based keys, clear the current month's cache
        $dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $dateTo = Carbon::now()->endOfMonth()->toDateString();
        Cache::forget("bookings_stats_{$dateFrom}_{$dateTo}");
    }

    /**
     * Get bookings grouped by date for daily view.
     * Returns bookings starting from today, organized by day.
     */
    public function groupedByDate(Request $request)
    {
        $query = Booking::query();

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Enhanced search: customer name, booking ID, reference number, participant names
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('bokun_booking_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('reference_number', 'like', '%' . $searchTerm . '%')
                  ->orWhereRaw("JSON_SEARCH(participants, 'one', ?, NULL, '$[*].name') IS NOT NULL", ['%' . $searchTerm . '%']);
            });
        }

        // Filter by product ID
        if ($request->filled('product_id')) {
            $query->where('bokun_product_id', $request->product_id);
        }

        // Default: Start from 30 days ago to include recent past bookings
        // This allows users to view historical bookings
        $defaultStartDate = Carbon::today()->subDays(30)->toDateString();
        $startDate = $request->input('date_from', $defaultStartDate);
        $query->whereDate('tour_date', '>=', $startDate);

        // Optional end date filter
        if ($request->filled('date_to')) {
            $query->whereDate('tour_date', '<=', $request->date_to);
        }

        // Order by date, then by time within each day
        $query->orderBy('tour_date', 'asc');

        // Get all bookings (increased limit to handle full date range)
        $limit = min((int) $request->input('limit', 1000), 2000);
        $bookings = $query->limit($limit)->get();

        // Group bookings by date
        $grouped = $bookings->groupBy(function ($booking) {
            return Carbon::parse($booking->tour_date)->format('Y-m-d');
        });

        // Format the response with date labels
        $result = [];
        foreach ($grouped as $date => $dayBookings) {
            $carbonDate = Carbon::parse($date);
            $label = $carbonDate->isToday()
                ? 'Today'
                : ($carbonDate->isTomorrow()
                    ? 'Tomorrow'
                    : $carbonDate->format('l, M j'));

            $result[] = [
                'date' => $date,
                'label' => $label,
                'day_name' => $carbonDate->format('l'),
                'formatted_date' => $carbonDate->format('M j, Y'),
                'is_today' => $carbonDate->isToday(),
                'is_tomorrow' => $carbonDate->isTomorrow(),
                'total_bookings' => $dayBookings->count(),
                'total_pax' => $dayBookings->sum('pax'),
                'pending_count' => $dayBookings->filter(function ($booking) {
                    // Count as pending if: no ticket purchased OR wizard was abandoned
                    return $booking->status === 'PENDING_TICKET' || $booking->wizard_abandoned_at !== null;
                })->count(),
                'bookings' => $dayBookings->sortBy('tour_date')->values(),
            ];
        }

        return response()->json([
            'grouped_bookings' => $result,
            'total_days' => count($result),
            'total_bookings' => $bookings->count(),
        ]);
    }

    /**
     * Get dashboard statistics.
     * Results are cached for 60 seconds to improve performance.
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->endOfMonth()->toDateString());

        // Create a cache key based on the date range
        $cacheKey = "bookings_stats_{$dateFrom}_{$dateTo}";

        // Cache stats for 60 seconds
        $stats = Cache::remember($cacheKey, 60, function () use ($dateFrom, $dateTo) {
            // Total bookings
            $totalBookings = Booking::count();

            // Bookings in date range
            $bookingsInRange = Booking::whereDate('tour_date', '>=', $dateFrom)
                ->whereDate('tour_date', '<=', $dateTo)
                ->count();

            // By status - count abandoned wizards as "pending" (not complete)
            $pendingTickets = Booking::where(function ($q) {
                $q->where('status', 'PENDING_TICKET')
                  ->orWhereNotNull('wizard_abandoned_at');
            })->count();
            $purchasedTickets = Booking::where('status', 'TICKET_PURCHASED')
                ->whereNull('wizard_abandoned_at')
                ->count();

            // Pending in date range (urgent) - includes abandoned wizards
            $pendingInRange = Booking::where(function ($q) {
                $q->where('status', 'PENDING_TICKET')
                  ->orWhereNotNull('wizard_abandoned_at');
            })
                ->whereDate('tour_date', '>=', $dateFrom)
                ->whereDate('tour_date', '<=', $dateTo)
                ->count();

            // Upcoming bookings (next 7 days) needing tickets - includes abandoned wizards
            $upcomingPending = Booking::where(function ($q) {
                $q->where('status', 'PENDING_TICKET')
                  ->orWhereNotNull('wizard_abandoned_at');
            })
                ->whereDate('tour_date', '>=', Carbon::now())
                ->whereDate('tour_date', '<=', Carbon::now()->addDays(7))
                ->count();

            // Total PAX in range
            $totalPaxInRange = Booking::whereDate('tour_date', '>=', $dateFrom)
                ->whereDate('tour_date', '<=', $dateTo)
                ->sum('pax');

            // Bookings by product
            $byProduct = Booking::selectRaw('bokun_product_id, product_name, COUNT(*) as count, SUM(pax) as total_pax')
                ->groupBy('bokun_product_id', 'product_name')
                ->get();

            // Bookings by day in range
            $byDay = Booking::selectRaw('DATE(tour_date) as date, COUNT(*) as count, SUM(pax) as total_pax')
                ->whereDate('tour_date', '>=', $dateFrom)
                ->whereDate('tour_date', '<=', $dateTo)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'summary' => [
                    'total_bookings' => $totalBookings,
                    'bookings_in_range' => $bookingsInRange,
                    'pending_tickets' => $pendingTickets,
                    'purchased_tickets' => $purchasedTickets,
                    'pending_in_range' => $pendingInRange,
                    'upcoming_pending_7_days' => $upcomingPending,
                    'total_pax_in_range' => $totalPaxInRange,
                ],
                'by_product' => $byProduct,
                'by_day' => $byDay,
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ],
            ];
        });

        return response()->json($stats);
    }

    /**
     * Import historical bookings via API.
     */
    public function import(Request $request, BokunService $bokunService)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $startDate = $validated['date_from'];
        $endDate = $validated['date_to'];
        $uffiziProductIds = $this->getUffiziProductIds();

        $page = 1;
        $pageSize = 100;
        $totalImported = 0;
        $totalDuplicates = 0;
        $hasMore = true;

        while ($hasMore) {
            $result = $bokunService->getHistoricalBookings($startDate, $endDate, $page, $pageSize);

            if (!$result['success']) {
                return response()->json([
                    'error' => 'API Error',
                    'message' => $result['error'] ?? 'Unknown error',
                    'imported' => $totalImported,
                    'duplicates' => $totalDuplicates,
                ], 500);
            }

            $bookings = $result['results'];
            $totalCount = $result['totalCount'];

            if (empty($bookings)) {
                break;
            }

            foreach ($bookings as $booking) {
                $productBookings = $booking['productBookings'] ?? [];

                foreach ($productBookings as $pb) {
                    $productId = (string) ($pb['product']['id'] ?? '');

                    if (in_array($productId, $uffiziProductIds)) {
                        $confirmationCode = $booking['confirmationCode'] ?? 'UNKNOWN';

                        if (Booking::where('bokun_booking_id', $confirmationCode)->exists()) {
                            $totalDuplicates++;
                            continue;
                        }

                        Booking::create([
                            'bokun_booking_id' => $confirmationCode,
                            'bokun_product_id' => $productId,
                            'product_name' => $pb['product']['title'] ?? 'Uffizi Tour',
                            'customer_name' => ($booking['customer']['firstName'] ?? 'Guest') . ' ' . ($booking['customer']['lastName'] ?? ''),
                            'tour_date' => isset($pb['date']) ? Carbon::parse($pb['date']) : now(),
                            'pax' => count($pb['passengers'] ?? []),
                            'status' => 'PENDING_TICKET',
                        ]);

                        $totalImported++;
                    }
                }
            }

            $hasMore = count($bookings) === $pageSize && ($page * $pageSize) < $totalCount;
            $page++;

            // Rate limiting
            usleep(150000);
        }

        return response()->json([
            'message' => 'Import completed',
            'imported' => $totalImported,
            'duplicates' => $totalDuplicates,
            'date_range' => [
                'from' => $startDate,
                'to' => $endDate,
            ],
        ]);
    }

    /**
     * Handle Bokun Webhook with HMAC-SHA256 verification and logging.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        // Determine event type from payload
        $eventType = $payload['eventType'] ?? $payload['status'] ?? 'unknown';
        $confirmationCode = $payload['confirmationCode'] ?? null;

        // Log the webhook immediately
        $webhookLog = WebhookLog::create([
            'event_type' => $eventType,
            'confirmation_code' => $confirmationCode,
            'payload' => $payload,
            'headers' => $headers,
            'status' => 'pending',
        ]);

        Log::info('Bokun Webhook Received', [
            'webhook_log_id' => $webhookLog->id,
            'event_type' => $eventType,
            'confirmation_code' => $confirmationCode,
            'payload_size' => strlen($request->getContent())
        ]);

        try {
            // Verify HMAC signature
            $secretKey = config('services.bokun.secret_key');
            if ($secretKey && !BokunService::verifyWebhookSignature($headers, $secretKey)) {
                throw new \Exception('HMAC signature verification failed');
            }

            // Process based on event type
            $result = $this->processWebhookEvent($eventType, $payload);

            $webhookLog->markAsProcessed();

            Log::info("Webhook processed successfully", [
                'webhook_log_id' => $webhookLog->id,
                'result' => $result
            ]);

            return response()->json($result, 200);

        } catch (\Exception $e) {
            $webhookLog->markAsFailed($e->getMessage());

            Log::error('Webhook processing failed', [
                'webhook_log_id' => $webhookLog->id,
                'error' => $e->getMessage()
            ]);

            // Return 200 to prevent Bokun from retrying (we handle retries ourselves)
            return response()->json([
                'message' => 'Webhook received but processing failed',
                'error' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Process webhook event based on type.
     */
    private function processWebhookEvent(string $eventType, array $payload): array
    {
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
            // Set cancelled_at timestamp before soft deleting
            $booking->cancelled_at = now();
            $booking->save();
            $booking->delete(); // Soft delete (sets deleted_at)
            Log::info("Booking cancelled via webhook", ['booking_id' => $booking->id]);
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

        $uffiziProductIds = $this->getUffiziProductIds();
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

    /**
     * Sync bookings from Bokun manually.
     * Uses the product-booking-search endpoint which returns bookings directly.
     * Also fetches detailed booking info to get participant names.
     * Limits detailed fetches to 20 per sync to prevent timeout.
     */
    public function syncBookings(BokunService $bokunService)
    {
        // Increase execution time for sync
        set_time_limit(120);

        $results = $bokunService->getUpcomingBookings();
        $count = 0;
        $skipped = 0;
        $participantsUpdated = 0;
        $participantFetchLimit = 20; // Limit detailed API calls per sync
        $participantFetchCount = 0;
        $uffiziProductIds = $this->getUffiziProductIds();

        Log::info('Sync started', [
            'total_results' => count($results),
            'uffizi_product_ids' => $uffiziProductIds
        ]);

        foreach ($results as $booking) {
            $productId = (string) ($booking['product']['id'] ?? '');

            // Only sync Uffizi products
            if (!in_array($productId, $uffiziProductIds)) {
                $skipped++;
                continue;
            }

            $confirmationCode = $booking['confirmationCode'] ?? $booking['productConfirmationCode'] ?? 'UNKNOWN';

            // Parse the startDateTime - it's in milliseconds timestamp and includes the actual tour time
            // Use startDateTime (has time) instead of startDate (just date at midnight)
            $tourDate = now();
            if (isset($booking['startDateTime'])) {
                $tourDate = Carbon::createFromTimestampMs($booking['startDateTime']);
            } elseif (isset($booking['startDate'])) {
                $tourDate = Carbon::createFromTimestampMs($booking['startDate']);
            }

            // Get participant count
            $pax = $booking['totalParticipants'] ?? 1;

            // Get customer name
            $customerName = trim(
                ($booking['customer']['firstName'] ?? 'Guest') . ' ' .
                ($booking['customer']['lastName'] ?? '')
            );

            // Extract PAX details from priceCategoryBookings and consolidate by type
            $paxCounts = [];
            if (isset($booking['fields']['priceCategoryBookings']) && is_array($booking['fields']['priceCategoryBookings'])) {
                foreach ($booking['fields']['priceCategoryBookings'] as $priceCat) {
                    $title = $priceCat['bookedTitle'] ?? ($priceCat['pricingCategory']['title'] ?? 'Guest');
                    $quantity = $priceCat['quantity'] ?? 1;
                    if ($quantity > 0) {
                        if (!isset($paxCounts[$title])) {
                            $paxCounts[$title] = 0;
                        }
                        $paxCounts[$title] += $quantity;
                    }
                }
            }
            // Convert to array format
            $paxDetails = [];
            foreach ($paxCounts as $type => $quantity) {
                $paxDetails[] = ['type' => $type, 'quantity' => $quantity];
            }

            // Check if we already have participants for this booking
            $existingBooking = Booking::where('bokun_booking_id', $confirmationCode)->first();
            $participants = $existingBooking?->participants;

            // Fetch detailed booking info to get participant names if we don't have them yet
            // Limit the number of detail fetches per sync to prevent timeout
            if (empty($participants) && $participantFetchCount < $participantFetchLimit) {
                try {
                    $bookingDetails = $bokunService->getBookingDetails($confirmationCode);
                    if ($bookingDetails) {
                        $participants = BokunService::extractParticipants($bookingDetails);
                        if (!empty($participants)) {
                            $participantsUpdated++;
                        }
                    }
                    $participantFetchCount++;
                    // Rate limiting - 100ms delay between detail requests
                    usleep(100000);
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch booking details for participants', [
                        'confirmation_code' => $confirmationCode,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Booking::updateOrCreate(
                ['bokun_booking_id' => $confirmationCode],
                [
                    'bokun_product_id' => $productId,
                    'product_name' => $booking['product']['title'] ?? 'Uffizi Tour',
                    'customer_name' => $customerName,
                    'tour_date' => $tourDate,
                    'pax' => $pax,
                    'pax_details' => !empty($paxDetails) ? $paxDetails : null,
                    'participants' => !empty($participants) ? $participants : null,
                ]
            );
            $count++;
        }

        // Count how many bookings still need participant data
        $pendingParticipants = Booking::whereNull('participants')->count();

        Log::info('Sync completed', [
            'synced' => $count,
            'skipped' => $skipped,
            'participants_updated' => $participantsUpdated,
            'pending_participants' => $pendingParticipants
        ]);

        return response()->json([
            'message' => "Synced $count bookings" . ($participantsUpdated > 0 ? ", fetched $participantsUpdated participant lists" : ""),
            'synced' => $count,
            'skipped' => $skipped,
            'participants_updated' => $participantsUpdated,
            'pending_participants' => $pendingParticipants,
            'total_from_api' => count($results)
        ]);
    }

    /**
     * Auto-sync endpoint called when frontend loads.
     * Triggers background sync and returns immediately with current stats.
     */
    public function autoSync()
    {
        // Check how many bookings need participant data or channel data
        $pendingParticipants = Booking::whereNull('participants')->count();
        $pendingChannels = Booking::whereNull('booking_channel')->count();
        $pendingDetails = Booking::where(function ($q) {
            $q->whereNull('participants')->orWhereNull('booking_channel');
        })->count();
        $totalBookings = Booking::count();

        // Count incomplete participant data (has some participants but fewer than PAX)
        $incompleteParticipants = Booking::whereNotNull('participants')
            ->whereRaw('JSON_LENGTH(participants) > 0')
            ->whereRaw('JSON_LENGTH(participants) < pax')
            ->count();

        // Get last sync time from log or cache
        $lastSyncFile = storage_path('logs/bokun-sync.log');
        $lastSync = file_exists($lastSyncFile) ? filemtime($lastSyncFile) : null;
        $minutesSinceSync = $lastSync ? round((time() - $lastSync) / 60) : null;

        // Trigger background sync via Artisan (non-blocking)
        // Only if there are pending details or it's been more than 5 minutes
        $syncTriggered = false;
        if ($pendingDetails > 0 || $minutesSinceSync === null || $minutesSinceSync > 5) {
            try {
                // Run sync in background
                \Illuminate\Support\Facades\Artisan::call('bokun:sync', ['--limit' => 50]);
                $syncTriggered = true;

                // Update pending counts after sync
                $pendingParticipants = Booking::whereNull('participants')->count();
                $pendingChannels = Booking::whereNull('booking_channel')->count();
            } catch (\Exception $e) {
                Log::error('Auto-sync failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'total_bookings' => $totalBookings,
            'pending_participants' => $pendingParticipants,
            'pending_channels' => $pendingChannels,
            'incomplete_participants' => $incompleteParticipants,
            'all_synced' => $pendingParticipants === 0 && $pendingChannels === 0,
            'sync_triggered' => $syncTriggered,
            'last_sync_minutes_ago' => $minutesSinceSync,
        ]);
    }
}
