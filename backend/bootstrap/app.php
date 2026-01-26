<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\RequestLogger;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([HandleCors::class]);

        // Add security headers to all API responses
        $middleware->appendToGroup('api', SecurityHeaders::class);

        // Add request logging middleware (configurable via LOG_REQUESTS env var)
        // Logs request method, path, client IP, response status, and duration
        // Only logs when APP_DEBUG=true or for specific routes (webhooks, sync)
        $middleware->appendToGroup('api', RequestLogger::class);

        // Note: Do NOT use statefulApi() - we use token-based auth, not cookie-based
        // statefulApi() enables CSRF protection which breaks token auth from same-origin requests
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Integrate Sentry error reporting (only if SDK is installed and DSN is configured)
        if (class_exists(Integration::class) && !empty(env('SENTRY_LARAVEL_DSN'))) {
            Integration::handles($exceptions);
        }

        // Return JSON for unauthenticated API requests instead of redirecting
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'Please login to access this resource.'
                ], 401);
            }
        });
    })->create();

// Only include debug routes in local/development environment
if (env('APP_DEBUG', false) && env('APP_ENV') !== 'production') {
    require_once __DIR__ . '/../routes/debug.php';
}
