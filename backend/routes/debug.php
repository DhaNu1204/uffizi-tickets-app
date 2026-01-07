<?php

use Illuminate\Support\Facades\Route;
use App\Services\BokunService;

/*
|--------------------------------------------------------------------------
| Debug Routes - Only available in non-production environments
|--------------------------------------------------------------------------
| These routes are conditionally loaded in bootstrap/app.php
| based on APP_DEBUG and APP_ENV settings.
*/

Route::get('/debug/bokun', function (BokunService $service) {
    // Use the service's testConnection method which uses config-based credentials
    return response()->json([
        'test_connection' => $service->testConnection(),
        'config_check' => [
            'access_key_set' => !empty(config('services.bokun.access_key')),
            'secret_key_set' => !empty(config('services.bokun.secret_key')),
            'base_url' => config('services.bokun.base_url'),
            'uffizi_products' => config('services.bokun.uffizi_product_ids'),
        ]
    ]);
});
