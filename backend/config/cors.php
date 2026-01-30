<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. Explicit configuration improves security by limiting
    | attack surface.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Explicitly list allowed HTTP methods (avoid wildcards for security)
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        // Local development (only enabled in non-production)
        env('APP_ENV') !== 'production' ? 'http://localhost:5173' : null,
        env('APP_ENV') !== 'production' ? 'http://localhost:5174' : null,
        env('APP_ENV') !== 'production' ? 'http://localhost:5175' : null,
        env('APP_ENV') !== 'production' ? 'http://127.0.0.1:5173' : null,
        env('APP_ENV') !== 'production' ? 'http://127.0.0.1:5174' : null,
        env('APP_ENV') !== 'production' ? 'http://127.0.0.1:5175' : null,
        // Production domain
        'https://uffizi.deetech.cc',
        // Additional allowed origins from environment
        env('CORS_ALLOWED_ORIGIN'),
    ]),

    'allowed_origins_patterns' => [],

    // Explicitly list allowed headers (avoid wildcards for security)
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
    ],

    // Headers that can be exposed to the browser
    'exposed_headers' => [
        'X-Request-Id',
    ],

    // Cache preflight requests for 1 hour (reduces OPTIONS requests)
    'max_age' => 3600,

    'supports_credentials' => true,

];
