<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ManualMessageController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\TemplateAdminController;
use App\Http\Controllers\TwilioWebhookController;

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

// Twilio status callback - verified via Twilio signature
Route::post('/webhooks/twilio/status', [TwilioWebhookController::class, 'status']);

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
    Route::post('/bookings/{id}/wizard-progress', [BookingController::class, 'updateWizardProgress']);

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

    // Messaging Routes
    Route::post('/bookings/{id}/send-ticket', [MessageController::class, 'sendTicket']);
    Route::get('/bookings/{id}/detect-channel', [MessageController::class, 'detectChannel']);
    Route::get('/bookings/{id}/messages', [MessageController::class, 'history']);
    Route::post('/messages/preview', [MessageController::class, 'preview']);
    Route::get('/messages/templates', [MessageController::class, 'templates']);

    // Manual Message Send (rate limited to 10 per minute)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/messages/send-manual', [ManualMessageController::class, 'send']);
    });

    // Manual Message History & Status Sync
    Route::get('/messages/manual-history', [ManualMessageController::class, 'history']);
    Route::post('/messages/sync-status', [ManualMessageController::class, 'syncStatus']);

    // Attachment Routes
    Route::post('/bookings/{id}/attachments', [AttachmentController::class, 'store']);
    Route::get('/bookings/{id}/attachments', [AttachmentController::class, 'index']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);
    Route::get('/attachments/{id}/download', [AttachmentController::class, 'download']);

    // Template Routes (for wizard)
    Route::get('/templates/languages', [TemplateAdminController::class, 'getLanguages']);
    Route::get('/templates/by-language-type', [TemplateAdminController::class, 'getByLanguageAndType']);

    // Template Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/templates', [TemplateAdminController::class, 'index']);
        Route::get('/templates/{id}', [TemplateAdminController::class, 'show']);
        Route::post('/templates', [TemplateAdminController::class, 'store']);
        Route::put('/templates/{id}', [TemplateAdminController::class, 'update']);
        Route::delete('/templates/{id}', [TemplateAdminController::class, 'destroy']);
        Route::post('/templates/{id}/preview', [TemplateAdminController::class, 'preview']);
        Route::post('/templates/{id}/duplicate', [TemplateAdminController::class, 'duplicate']);
    });
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
