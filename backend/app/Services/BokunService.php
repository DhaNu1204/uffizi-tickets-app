<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BokunService
{
    private $accessKey;
    private $secretKey;
    private $baseUrl;

    public function __construct()
    {
        $this->accessKey = config('services.bokun.access_key');
        $this->secretKey = config('services.bokun.secret_key');
        $this->baseUrl = config('services.bokun.base_url', 'https://api.bokun.io');
    }

    /**
     * Get Uffizi product IDs from config
     */
    public static function getUffiziProductIds(): array
    {
        return config('services.bokun.uffizi_product_ids', []);
    }

    /**
     * Verify webhook HMAC-SHA256 signature per Bokun documentation
     */
    public static function verifyWebhookSignature(array $headers, string $secretKey): bool
    {
        $providedHmac = $headers['X-Bokun-HMAC'] ?? $headers['x-bokun-hmac'] ?? null;

        if (!$providedHmac) {
            Log::warning('Webhook missing X-Bokun-HMAC header');
            return false;
        }

        $bokunHeaders = [];
        foreach ($headers as $name => $value) {
            $lowerName = strtolower($name);
            if (str_starts_with($lowerName, 'x-bokun-') && $lowerName !== 'x-bokun-hmac') {
                $bokunHeaders[$lowerName] = is_array($value) ? $value[0] : $value;
            }
        }

        ksort($bokunHeaders);
        $stringToSign = http_build_query($bokunHeaders);
        $calculatedHmac = hash_hmac('sha256', $stringToSign, $secretKey);

        return hash_equals($calculatedHmac, $providedHmac);
    }

    /**
     * Get bookings using the search endpoint with date range
     */
    public function getBookings($startDate = null, $endDate = null)
    {
        // Use GET endpoint with query parameters per documentation
        $start = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : Carbon::now()->subDays(30)->format('Y-m-d');
        $end = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : Carbon::now()->addMonths(6)->format('Y-m-d');

        $path = '/booking.json/search?start=' . $start . '&end=' . $end;
        $method = 'GET';

        $headers = $this->getHeaders($method, $path);

        $response = Http::withHeaders($headers)->get($this->baseUrl . $path);

        if ($response->failed()) {
            Log::error('Bokun API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'path' => $path
            ]);
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch upcoming bookings using the product-booking-search endpoint
     * This is the correct endpoint for fetching product bookings
     */
    public function getUpcomingBookings()
    {
        // CORRECT endpoint: POST /booking.json/product-booking-search
        $path = '/booking.json/product-booking-search';
        $method = 'POST';

        $startDate = Carbon::now()->subDays(30)->format('Y-m-d') . 'T00:00:00.000Z';
        $endDate = Carbon::now()->addDays(180)->format('Y-m-d') . 'T23:59:59.999Z';

        $requestBody = [
            'bookingStatuses' => ['CONFIRMED'],
            'pageSize' => 10000,
            'startDateRange' => [
                'from' => $startDate,
                'to' => $endDate
            ]
        ];

        $body = json_encode($requestBody);
        $headers = $this->getHeaders($method, $path);

        $response = Http::withHeaders($headers)
            ->withBody($body, 'application/json;charset=UTF-8')
            ->post($this->baseUrl . $path);

        if ($response->successful()) {
            $data = $response->json();

            Log::info('Bokun API Success - product-booking-search endpoint', [
                'count' => isset($data['results']) ? count($data['results']) : 0,
                'totalHits' => $data['totalHits'] ?? 0,
            ]);

            // Return the results array
            return $data['results'] ?? [];
        }

        Log::error('Bokun API Error - product-booking-search endpoint', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 500),
            'path' => $path,
            'request_body' => $requestBody
        ]);

        return [];
    }

    /**
     * Fetch historical bookings with pagination for large imports
     */
    public function getHistoricalBookings($startDate, $endDate, $page = 1, $pageSize = 100)
    {
        $path = '/booking.json/booking-search';
        $method = 'POST';

        $requestBody = [
            'bookingRole' => 'SELLER',
            'bookingStatuses' => ['CONFIRMED', 'PENDING', 'CANCELLED'],
            'pageSize' => $pageSize,
            'page' => $page,
            'startDateRange' => [
                'from' => Carbon::parse($startDate)->format('Y-m-d') . 'T00:00:00.000Z',
                'to' => Carbon::parse($endDate)->format('Y-m-d') . 'T23:59:59.999Z'
            ]
        ];

        $body = json_encode($requestBody);
        $headers = $this->getHeaders($method, $path);

        $response = Http::withHeaders($headers)
            ->withBody($body, 'application/json;charset=UTF-8')
            ->post($this->baseUrl . $path);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'results' => $data['results'] ?? [],
                'totalCount' => $data['totalCount'] ?? 0,
                'page' => $page,
                'pageSize' => $pageSize
            ];
        }

        Log::error('Bokun API Error - historical bookings', [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 500),
            'page' => $page
        ]);

        return [
            'success' => false,
            'results' => [],
            'error' => $response->body()
        ];
    }

    /**
     * Get single booking details by confirmation code
     */
    public function getBookingDetails($confirmationCode)
    {
        $path = '/booking.json/booking/' . $confirmationCode;
        $method = 'GET';

        $headers = $this->getHeaders($method, $path);
        $response = Http::withHeaders($headers)->get($this->baseUrl . $path);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Bokun API Error - booking details', [
            'status' => $response->status(),
            'confirmation_code' => $confirmationCode
        ]);

        return null;
    }

    /**
     * Test connection with activity search
     */
    public function testConnection()
    {
        $path = '/activity.json/search';
        $method = 'POST';

        $body = json_encode(['page' => 1, 'pageSize' => 5]);
        $headers = $this->getHeaders($method, $path);

        $response = Http::withHeaders($headers)
            ->withBody($body, 'application/json;charset=UTF-8')
            ->post($this->baseUrl . $path);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json()
        ];
    }

    /**
     * Generate authentication headers per Bokun documentation
     * String to sign: Date + AccessKey + HTTPMethod + Path
     */
    private function getHeaders($method, $path, $body = "")
    {
        // Date format: "YYYY-MM-DD HH:MM:SS" in UTC
        $date = gmdate('Y-m-d H:i:s');

        // Concatenation order per documentation: Date + AccessKey + Method + Path
        $stringToSign = $date . $this->accessKey . $method . $path;

        // Generate HMAC-SHA1 signature and Base64 encode
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));

        return [
            'X-Bokun-Date' => $date,
            'X-Bokun-AccessKey' => $this->accessKey,
            'X-Bokun-Signature' => $signature,
            'Content-Type' => 'application/json;charset=UTF-8',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Extract booking channel from detailed booking response.
     * Returns channel title like 'GetYourGuide', 'Viator', or 'direct' for direct bookings.
     */
    public static function extractBookingChannel(array $bookingDetails): string
    {
        $channel = $bookingDetails['bookingChannel'] ?? null;

        if (!$channel) {
            return 'direct';
        }

        // Return the channel title if available
        return $channel['title'] ?? 'direct';
    }

    /**
     * Extract customer contact information from detailed booking response.
     * Returns array with email and phone.
     */
    public static function extractCustomerContact(array $bookingDetails): array
    {
        $customer = $bookingDetails['customer'] ?? [];

        return [
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phoneNumber'] ?? $customer['phone'] ?? $customer['mobilePhone'] ?? null,
        ];
    }

    /**
     * Extract participant names from detailed booking response.
     * Handles multiple booking structures including direct bookings and reseller bookings (GetYourGuide, Viator).
     *
     * @param array $bookingDetails The response from getBookingDetails()
     * @return array Array of participants [{name, type}]
     */
    public static function extractParticipants(array $bookingDetails): array
    {
        $participants = [];
        $foundParticipants = false;

        // Method 1: Check activityBookings → pricingCategoryBookings → passengerInfo (direct bookings)
        if (isset($bookingDetails['activityBookings'])) {
            foreach ($bookingDetails['activityBookings'] as $activity) {
                // Check pricingCategoryBookings for passengerInfo
                if (isset($activity['pricingCategoryBookings'])) {
                    foreach ($activity['pricingCategoryBookings'] as $pcb) {
                        $passengerInfo = $pcb['passengerInfo'] ?? null;
                        if ($passengerInfo) {
                            $firstName = trim($passengerInfo['firstName'] ?? '');
                            $lastName = trim($passengerInfo['lastName'] ?? '');
                            $fullName = trim("$firstName $lastName");

                            if (!empty($fullName)) {
                                $type = $pcb['pricingCategory']['fullTitle']
                                    ?? $pcb['pricingCategory']['title']
                                    ?? $pcb['bookedTitle']
                                    ?? 'Guest';

                                $participants[] = [
                                    'name' => $fullName,
                                    'type' => $type,
                                ];
                                $foundParticipants = true;
                            }
                        }
                    }
                }

                // Method 2: Check activityBookings → passengers array (reseller bookings)
                if (isset($activity['passengers']) && is_array($activity['passengers'])) {
                    foreach ($activity['passengers'] as $passenger) {
                        $firstName = trim($passenger['firstName'] ?? '');
                        $lastName = trim($passenger['lastName'] ?? '');
                        $fullName = trim("$firstName $lastName");

                        if (!empty($fullName)) {
                            $type = $passenger['pricingCategoryTitle']
                                ?? $passenger['category']
                                ?? $passenger['type']
                                ?? 'Guest';

                            $participants[] = [
                                'name' => $fullName,
                                'type' => $type,
                            ];
                            $foundParticipants = true;
                        }
                    }
                }
            }
        }

        // Method 3: Check productBookings (alternative structure)
        if (!$foundParticipants && isset($bookingDetails['productBookings'])) {
            foreach ($bookingDetails['productBookings'] as $pb) {
                // Check passengers array in productBookings
                if (isset($pb['passengers']) && is_array($pb['passengers'])) {
                    foreach ($pb['passengers'] as $passenger) {
                        $firstName = trim($passenger['firstName'] ?? '');
                        $lastName = trim($passenger['lastName'] ?? '');
                        $fullName = trim("$firstName $lastName");

                        if (!empty($fullName)) {
                            $type = $passenger['pricingCategoryTitle']
                                ?? $passenger['category']
                                ?? $passenger['type']
                                ?? 'Guest';

                            $participants[] = [
                                'name' => $fullName,
                                'type' => $type,
                            ];
                            $foundParticipants = true;
                        }
                    }
                }

                // Check fields.passengers (nested structure)
                if (isset($pb['fields']['passengers']) && is_array($pb['fields']['passengers'])) {
                    foreach ($pb['fields']['passengers'] as $passenger) {
                        $firstName = trim($passenger['firstName'] ?? '');
                        $lastName = trim($passenger['lastName'] ?? '');
                        $fullName = trim("$firstName $lastName");

                        if (!empty($fullName)) {
                            $type = $passenger['pricingCategoryTitle'] ?? 'Guest';
                            $participants[] = [
                                'name' => $fullName,
                                'type' => $type,
                            ];
                            $foundParticipants = true;
                        }
                    }
                }
            }
        }

        // Method 4: Check top-level passengers array
        if (!$foundParticipants && isset($bookingDetails['passengers']) && is_array($bookingDetails['passengers'])) {
            foreach ($bookingDetails['passengers'] as $passenger) {
                $firstName = trim($passenger['firstName'] ?? '');
                $lastName = trim($passenger['lastName'] ?? '');
                $fullName = trim("$firstName $lastName");

                if (!empty($fullName)) {
                    $type = $passenger['pricingCategoryTitle']
                        ?? $passenger['category']
                        ?? $passenger['type']
                        ?? 'Guest';

                    $participants[] = [
                        'name' => $fullName,
                        'type' => $type,
                    ];
                }
            }
        }

        // Method 5: Check guests array (some systems use this)
        if (!$foundParticipants && isset($bookingDetails['guests']) && is_array($bookingDetails['guests'])) {
            foreach ($bookingDetails['guests'] as $guest) {
                $firstName = trim($guest['firstName'] ?? $guest['first_name'] ?? '');
                $lastName = trim($guest['lastName'] ?? $guest['last_name'] ?? '');
                $fullName = trim("$firstName $lastName");

                if (!empty($fullName)) {
                    $type = $guest['type'] ?? $guest['category'] ?? 'Guest';
                    $participants[] = [
                        'name' => $fullName,
                        'type' => $type,
                    ];
                }
            }
        }

        // Log if no participants found for debugging
        if (empty($participants)) {
            Log::debug('No participants extracted from booking', [
                'has_activityBookings' => isset($bookingDetails['activityBookings']),
                'has_productBookings' => isset($bookingDetails['productBookings']),
                'has_passengers' => isset($bookingDetails['passengers']),
                'has_guests' => isset($bookingDetails['guests']),
                'top_level_keys' => array_keys($bookingDetails),
            ]);
        }

        return $participants;
    }

    /**
     * Debug method to log the full structure of a booking for investigation
     * Call this to see the actual API response structure
     */
    public function debugBookingStructure(string $confirmationCode): array
    {
        $bookingDetails = $this->getBookingDetails($confirmationCode);

        if (!$bookingDetails) {
            return ['error' => 'Booking not found'];
        }

        // Log the full structure
        Log::info('DEBUG: Full booking structure for ' . $confirmationCode, [
            'top_level_keys' => array_keys($bookingDetails),
            'customer' => $bookingDetails['customer'] ?? null,
            'totalParticipants' => $bookingDetails['totalParticipants'] ?? null,
        ]);

        // Check for activityBookings structure
        if (isset($bookingDetails['activityBookings'])) {
            foreach ($bookingDetails['activityBookings'] as $idx => $activity) {
                Log::info("DEBUG: activityBookings[$idx] keys", [
                    'keys' => array_keys($activity),
                    'has_passengers' => isset($activity['passengers']),
                    'has_pricingCategoryBookings' => isset($activity['pricingCategoryBookings']),
                ]);

                if (isset($activity['passengers'])) {
                    Log::info("DEBUG: activityBookings[$idx].passengers", [
                        'count' => count($activity['passengers']),
                        'sample' => $activity['passengers'][0] ?? null,
                    ]);
                }

                if (isset($activity['pricingCategoryBookings'])) {
                    Log::info("DEBUG: activityBookings[$idx].pricingCategoryBookings", [
                        'count' => count($activity['pricingCategoryBookings']),
                        'sample_keys' => isset($activity['pricingCategoryBookings'][0])
                            ? array_keys($activity['pricingCategoryBookings'][0])
                            : [],
                        'sample_passengerInfo' => $activity['pricingCategoryBookings'][0]['passengerInfo'] ?? null,
                    ]);
                }
            }
        }

        // Check for productBookings structure
        if (isset($bookingDetails['productBookings'])) {
            foreach ($bookingDetails['productBookings'] as $idx => $pb) {
                Log::info("DEBUG: productBookings[$idx] keys", [
                    'keys' => array_keys($pb),
                    'has_passengers' => isset($pb['passengers']),
                    'fields_keys' => isset($pb['fields']) ? array_keys($pb['fields']) : [],
                ]);

                if (isset($pb['passengers'])) {
                    Log::info("DEBUG: productBookings[$idx].passengers", [
                        'count' => count($pb['passengers']),
                        'sample' => $pb['passengers'][0] ?? null,
                    ]);
                }
            }
        }

        // Extract participants using the improved method
        $participants = self::extractParticipants($bookingDetails);

        return [
            'confirmation_code' => $confirmationCode,
            'top_level_keys' => array_keys($bookingDetails),
            'customer_name' => ($bookingDetails['customer']['firstName'] ?? '') . ' ' . ($bookingDetails['customer']['lastName'] ?? ''),
            'total_participants' => $bookingDetails['totalParticipants'] ?? null,
            'extracted_participants' => $participants,
            'extracted_count' => count($participants),
        ];
    }

    /**
     * Test booking search with different parameters
     */
    public function testBookingSearch($role = null)
    {
        $path = '/booking.json/booking-search';
        $method = 'POST';

        // Very wide date range to catch all bookings
        $startDate = '2024-01-01T00:00:00.000Z';
        $endDate = '2026-12-31T23:59:59.999Z';

        $requestBody = [
            'bookingStatuses' => ['CONFIRMED', 'PENDING', 'CANCELLED'],
            'pageSize' => 100,
            'startDateRange' => [
                'from' => $startDate,
                'to' => $endDate
            ]
        ];

        if ($role) {
            $requestBody['bookingRole'] = $role;
        }

        $body = json_encode($requestBody);
        $headers = $this->getHeaders($method, $path);

        $response = Http::withHeaders($headers)
            ->withBody($body, 'application/json;charset=UTF-8')
            ->post($this->baseUrl . $path);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'count' => isset($data['results']) ? count($data['results']) : 0,
                'has_results' => isset($data['results']),
                'sample' => isset($data['results'][0]) ? $data['results'][0]['confirmationCode'] : null
            ];
        }

        return [
            'success' => false,
            'status' => $response->status(),
            'error' => substr($response->body(), 0, 200)
        ];
    }
}
