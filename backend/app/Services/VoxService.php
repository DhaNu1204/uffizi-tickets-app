<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * VOX/PopGuide Audio Guide Integration Service
 *
 * This service handles creating audio guide accounts for customers via the PopGuide API.
 * It generates dynamic links that customers can use to access their audio guide.
 *
 * API Documentation: PopGuide Partners API v3
 * Production: https://popguide.herokuapp.com
 * Staging: https://popguide-staging.herokuapp.com
 */
class VoxService
{
    protected ?string $apiKey;
    protected ?string $apiSecret;
    protected string $baseUrl;

    /**
     * PopMap IDs for different museums/attractions
     * These are the PopGuide content IDs for each audio guide
     */
    protected array $popMapIds = [
        'uffizi' => 698,
        // Add more museums here as needed:
        // 'accademia' => XXX,
        // 'palazzo_vecchio' => XXX,
    ];

    public function __construct()
    {
        $this->apiKey = config('services.vox.api_key');
        $this->apiSecret = config('services.vox.api_secret');
        $this->baseUrl = config('services.vox.base_url', 'https://popguide-staging.herokuapp.com');
    }

    /**
     * Check if VOX service is configured with credentials
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Get Bearer token for API authentication
     * VOX uses query parameters: ?k={api_key}&s={api_secret}
     * Tokens are cached for 23 hours (expires in 24h)
     */
    protected function getToken(): ?string
    {
        $cacheKey = 'vox_api_token';

        // Return cached token if valid
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // VOX uses query parameters k and s for authentication
            $url = $this->baseUrl . '/partners_api/v3/sessions?' . http_build_query([
                'k' => $this->apiKey,
                's' => $this->apiSecret,
            ]);

            $response = Http::timeout(30)->post($url);

            Log::info('VOX session response', [
                'status' => $response->status(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['success']) && $data['success'] === true && isset($data['token'])) {
                    $token = $data['token'];

                    // Cache for 23 hours (token expires in 24h)
                    Cache::put($cacheKey, $token, now()->addHours(23));

                    Log::info('VOX API token obtained successfully', [
                        'expires_at' => $data['expires_at'] ?? 'unknown',
                    ]);

                    return $token;
                }
            }

            Log::error('VOX token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('VOX token retrieval failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get headers for API requests
     * Requires valid Bearer token from getToken()
     */
    protected function getHeaders(): array
    {
        $token = $this->getToken();

        if (!$token) {
            throw new \RuntimeException('Failed to obtain VOX API token');
        }

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    /**
     * Create VOX/PopGuide account for booking
     * Returns dynamic_link for the audio guide
     *
     * @param Booking $booking The booking to create account for
     * @return array Result with success status, dynamic_link, account_id, credentials, and any error
     */
    public function createAccount(Booking $booking): array
    {
        $result = [
            'success' => false,
            'dynamic_link' => null,
            'account_id' => null,
            'username' => null,
            'password' => null,
            'error' => null,
        ];

        // Validate booking has required info
        if (!$booking->customer_name) {
            $result['error'] = 'Customer name is required';
            return $result;
        }

        // Check if service is configured
        if (!$this->isConfigured()) {
            Log::warning('VOX service not configured - API credentials missing');
            $result['error'] = 'VOX API not configured - credentials missing';
            return $result;
        }

        // Get PopMap ID for the product
        $popMapId = $this->getPopMapId($booking->bokun_product_id);
        if (!$popMapId) {
            $result['error'] = 'No PopMap ID configured for this product';
            return $result;
        }

        try {
            // Build request payload according to PopGuide API spec
            $payload = [
                'name' => $booking->bokun_booking_id . ' - ' . $booking->customer_name,
                'qty' => (string) $booking->pax,
                'payment_method' => 'contract',
                'share_creds' => 'true',
                'termination_date' => $booking->tour_date->addDays(7)->format('Y-m-d'),
                'accesses' => [
                    [
                        'pop_map_id' => (string) $popMapId,
                    ]
                ],
            ];

            Log::info('Creating VOX account', [
                'booking_id' => $booking->id,
                'bokun_id' => $booking->bokun_booking_id,
                'customer' => $booking->customer_name,
                'pax' => $booking->pax,
                'pop_map_id' => $popMapId,
                'payload' => $payload,
            ]);

            // Make API request
            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/partners_api/v3/accounts', $payload);

            $data = $response->json();

            Log::info('VOX API response', [
                'booking_id' => $booking->id,
                'status' => $response->status(),
                'response' => $data,
            ]);

            if ($response->successful() && isset($data['success']) && $data['success'] === true) {
                $group = $data['group'] ?? [];
                $credentials = $group['credentials'][0] ?? [];

                $result['success'] = true;
                $result['account_id'] = (string) ($group['id'] ?? '');
                $result['dynamic_link'] = $credentials['dynamic_link'] ?? null;
                $result['username'] = $credentials['username'] ?? null;
                $result['password'] = $credentials['password'] ?? null;

                // Update booking with VOX details
                $booking->setVoxAccount(
                    $result['dynamic_link'] ?? '',
                    $result['account_id']
                );

                // Also update audio guide credentials if returned
                if ($result['username'] && $result['password']) {
                    $booking->update([
                        'audio_guide_username' => $result['username'],
                        'audio_guide_password' => $result['password'],
                        'audio_guide_url' => $result['dynamic_link'],
                    ]);
                }

                Log::info('VOX account created successfully', [
                    'booking_id' => $booking->id,
                    'vox_account_id' => $result['account_id'],
                    'dynamic_link' => $result['dynamic_link'],
                    'username' => $result['username'],
                ]);

            } else {
                $result['error'] = $data['error'] ?? $data['message'] ?? 'Failed to create VOX account (status: ' . $response->status() . ')';
                Log::error('VOX account creation failed', [
                    'booking_id' => $booking->id,
                    'status' => $response->status(),
                    'response' => $data,
                ]);
            }

        } catch (\Exception $e) {
            $result['error'] = 'VOX API error: ' . $e->getMessage();
            Log::error('VOX account creation exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $result;
    }

    /**
     * Get PopMap ID for a Bokun product ID
     *
     * @param string|int $productId Bokun product ID
     * @return int|null PopMap ID or null if not mapped
     */
    public function getPopMapId(string|int $productId): ?int
    {
        // Uffizi Timed Entry (961802) - main product with audio guide
        if ((string) $productId === '961802') {
            return $this->popMapIds['uffizi'];
        }

        // Add more product -> PopMap mappings here as needed
        // Example:
        // if ((string) $productId === 'ACCADEMIA_PRODUCT_ID') {
        //     return $this->popMapIds['accademia'];
        // }

        return null;
    }

    /**
     * Test API connection and authentication
     *
     * @return array Connection test results
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'configured' => false,
                'error' => 'VOX API credentials not configured',
            ];
        }

        try {
            // Clear any cached token to force fresh auth
            $this->clearToken();

            // Try to get a fresh token
            $token = $this->getToken();

            if (!$token) {
                return [
                    'success' => false,
                    'configured' => true,
                    'has_token' => false,
                    'error' => 'Failed to obtain authentication token',
                    'base_url' => $this->baseUrl,
                    'environment' => config('services.vox.environment', 'unknown'),
                ];
            }

            // Test API with a simple accounts list request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->get($this->baseUrl . '/partners_api/v3/accounts', [
                    'page' => 1,
                    'per_page' => 1,
                ]);

            return [
                'success' => $response->successful(),
                'configured' => true,
                'has_token' => true,
                'auth_method' => 'bearer_token',
                'base_url' => $this->baseUrl,
                'environment' => config('services.vox.environment', 'unknown'),
                'test_response_status' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'configured' => true,
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl,
            ];
        }
    }

    /**
     * Clear cached authentication token
     * Useful when credentials change or for testing
     */
    public function clearToken(): void
    {
        Cache::forget('vox_api_token');
    }

    /**
     * Get account details from VOX
     *
     * @param string $accountId VOX account ID
     * @return array Account details or error
     */
    public function getAccount(string $accountId): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/partners_api/v3/accounts/' . $accountId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'account' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get account: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Revoke/deactivate a VOX account
     *
     * @param string $accountId The VOX account ID to revoke
     * @return array Result with success status
     */
    public function revokeAccount(string $accountId): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders($this->getHeaders())
                ->delete($this->baseUrl . '/partners_api/v3/accounts/' . $accountId);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
