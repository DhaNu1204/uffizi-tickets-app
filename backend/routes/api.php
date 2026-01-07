<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\HealthController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// Health check endpoints - no authentication required for monitoring
Route::get('/health', [HealthController::class, 'check']);
Route::get('/health/detailed', [HealthController::class, 'detailed']);

// Webhook endpoint - verified via HMAC signature, not Sanctum
Route::post('/webhook/bokun', [BookingController::class, 'handleWebhook']);

// Authentication routes - rate limited to prevent brute force
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum Authentication Required)
| Rate limited to 60 requests per minute
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Booking CRUD
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/grouped', [BookingController::class, 'groupedByDate']);
    Route::get('/bookings/stats', [BookingController::class, 'stats']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

    // Sync & Import from Bokun - rate limit (10 per minute for dev, reduce for production)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/bookings/sync', [BookingController::class, 'syncBookings']);
        Route::post('/bookings/import', [BookingController::class, 'import']);
    });

    // Auto-sync is lightweight, allow more frequent calls
    Route::post('/bookings/auto-sync', [BookingController::class, 'autoSync']);

    // Webhook Admin Routes
    Route::get('/webhooks', [WebhookController::class, 'index']);
    Route::get('/webhooks/stats', [WebhookController::class, 'stats']);
    Route::get('/webhooks/{id}', [WebhookController::class, 'show']);
    Route::post('/webhooks/{id}/retry', [WebhookController::class, 'retry']);
    Route::post('/webhooks/retry-all', [WebhookController::class, 'retryAll']);
    Route::delete('/webhooks/cleanup', [WebhookController::class, 'cleanup']);
});

/*
|--------------------------------------------------------------------------
| Debug/Test Routes (Only in non-production)
|--------------------------------------------------------------------------
*/
if (config('app.debug') && config('app.env') !== 'production') {
    Route::get('/bookings/test-bokun', function (\App\Services\BokunService $service) {
        return response()->json($service->testConnection());
    });

    Route::get('/bookings/test-search', function (\App\Services\BokunService $service) {
        $results = [];
        $results['seller_role'] = $service->testBookingSearch('SELLER');
        $results['buyer_role'] = $service->testBookingSearch('BUYER');
        $results['no_role'] = $service->testBookingSearch(null);
        return response()->json($results);
    });

    // Debug endpoint to inspect booking structure for participant extraction
    Route::get('/bookings/debug/{confirmationCode}', function (\App\Services\BokunService $service, $confirmationCode) {
        return response()->json($service->debugBookingStructure($confirmationCode));
    });

    // Debug endpoint to see raw booking details from Bokun API
    Route::get('/bookings/raw/{confirmationCode}', function (\App\Services\BokunService $service, $confirmationCode) {
        $details = $service->getBookingDetails($confirmationCode);
        return response()->json($details);
    });
}
