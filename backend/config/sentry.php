<?php

/**
 * Sentry Laravel SDK Configuration
 *
 * This configuration file is for the Sentry error tracking integration.
 * Install the SDK with: composer require sentry/sentry-laravel
 *
 * @see https://docs.sentry.io/platforms/php/guides/laravel/
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells the SDK where to send the events. If this value is not
    | provided, the SDK will not send any events.
    |
    | Get your DSN from: https://sentry.io > Project Settings > Client Keys
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | This will be sent with each event to help distinguish between
    | production, staging, and development environments.
    |
    */

    'environment' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | Track which version of your application the error occurred in.
    | This is typically the git commit SHA or a version number.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | Set a sampling rate for events. A value of 0.0 means no events are sent,
    | and 1.0 means all events are sent. Useful for high-traffic apps.
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Profiles Sample Rate
    |--------------------------------------------------------------------------
    |
    | Set a sampling rate for profiling. This requires traces to be enabled.
    | Profiling helps identify performance bottlenecks.
    |
    */

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | If this flag is enabled, certain personally identifiable information (PII)
    | is added by active integrations. By default, this is disabled.
    |
    | Enable with caution and ensure compliance with privacy regulations.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Configure how breadcrumbs are captured. Breadcrumbs are a trail of events
    | that happened before an error occurred.
    |
    */

    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => true,

        // Capture SQL queries as breadcrumbs
        'sql_queries' => true,

        // Capture bindings in SQL queries (may contain sensitive data)
        'sql_bindings' => false,

        // Capture queue job information
        'queue_info' => true,

        // Capture HTTP client requests
        'http_client_requests' => true,

        // Capture command information
        'command_info' => true,

        // Capture notifications
        'notifications' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring (tracing) options.
    |
    */

    'tracing' => [
        // Capture queue jobs as transactions
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_JOBS', true),

        // Capture queue jobs as spans (requires queue_job_transactions to be false)
        'queue_jobs' => true,

        // Capture SQL queries as spans
        'sql_queries' => true,

        // Set a threshold for slow SQL queries (in ms)
        // Only queries slower than this will be captured as spans
        'sql_origin' => true,

        // Capture views as spans
        'views' => true,

        // Capture HTTP client requests as spans
        'http_client_requests' => true,

        // Capture Redis commands as spans
        'redis_commands' => env('SENTRY_TRACE_REDIS', false),

        // Capture file system operations as spans
        'file_system' => false,

        // Configure default integrations
        'default_integrations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | In-App Frames
    |--------------------------------------------------------------------------
    |
    | Define which paths should be considered "in-app" for stack trace grouping.
    | Files outside these paths are considered external/vendor code.
    |
    */

    'in_app_include' => [
        base_path('app'),
    ],

    'in_app_exclude' => [
        base_path('vendor'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before each event is sent. You can modify the
    | event or return null to discard it entirely.
    |
    | Example uses:
    | - Filter out certain exceptions
    | - Scrub sensitive data
    | - Add custom tags
    |
    */

    // 'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
    //     // Don't report 404 errors
    //     if ($hint && $hint->exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
    //         return null;
    //     }
    //     return $event;
    // },

    /*
    |--------------------------------------------------------------------------
    | Controllers Base Namespace
    |--------------------------------------------------------------------------
    |
    | Define the base namespace for your controllers. This is used to properly
    | report controller names in transactions.
    |
    */

    'controllers_base_namespace' => 'App\\Http\\Controllers',

];
