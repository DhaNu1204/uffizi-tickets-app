<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to log incoming HTTP requests and outgoing responses.
 *
 * This middleware logs request method, path, client IP, response status,
 * and request duration. It is useful for debugging, performance monitoring,
 * and audit trails.
 *
 * Logging is controlled by:
 * - APP_DEBUG: When true, logs all requests
 * - LOG_REQUEST_ROUTES: Comma-separated list of route prefixes to always log (e.g., "api/bookings,api/webhooks")
 *
 * Configuration in .env:
 *   LOG_REQUESTS=true           # Enable/disable request logging entirely
 *   LOG_REQUEST_ROUTES=api/webhooks,api/bookings/sync  # Routes to always log
 */
class RequestLogger
{
    /**
     * Routes that should always be logged regardless of debug mode.
     * Can be overridden via LOG_REQUEST_ROUTES env var.
     */
    protected array $alwaysLogRoutes = [
        'api/webhook',
        'api/webhooks',
        'api/bookings/sync',
        'api/bookings/import',
    ];

    /**
     * Routes that should never be logged (sensitive or high-frequency).
     */
    protected array $excludeRoutes = [
        'api/health',
        'api/user',
    ];

    /**
     * Headers to redact from logs for security.
     */
    protected array $sensitiveHeaders = [
        'authorization',
        'x-api-key',
        'cookie',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if logging is enabled
        if (!$this->shouldLog($request)) {
            return $next($request);
        }

        // Record start time for duration calculation
        $startTime = microtime(true);

        // Log the incoming request
        $this->logRequest($request);

        // Process the request
        $response = $next($request);

        // Calculate duration
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        // Log the response
        $this->logResponse($request, $response, $durationMs);

        return $response;
    }

    /**
     * Determine if this request should be logged.
     */
    protected function shouldLog(Request $request): bool
    {
        // Check if logging is explicitly disabled
        if (config('logging.log_requests') === false) {
            return false;
        }

        // Check if route is excluded
        foreach ($this->excludeRoutes as $route) {
            if ($request->is($route . '*')) {
                return false;
            }
        }

        // Always log specific routes (webhooks, sync operations)
        $configuredRoutes = config('logging.log_request_routes');
        if ($configuredRoutes) {
            $routes = array_merge($this->alwaysLogRoutes, explode(',', $configuredRoutes));
        } else {
            $routes = $this->alwaysLogRoutes;
        }

        foreach ($routes as $route) {
            if ($request->is(trim($route) . '*')) {
                return true;
            }
        }

        // Log all requests in debug mode
        return config('app.debug', false);
    }

    /**
     * Log the incoming request details.
     */
    protected function logRequest(Request $request): void
    {
        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Add user ID if authenticated
        if ($request->user()) {
            $context['user_id'] = $request->user()->id;
        }

        // Add query parameters (redact sensitive ones)
        $query = $request->query();
        if (!empty($query)) {
            $context['query'] = $this->redactSensitiveData($query);
        }

        // Add relevant headers (redacted)
        $headers = $this->getRelevantHeaders($request);
        if (!empty($headers)) {
            $context['headers'] = $headers;
        }

        Log::channel('request')->info('Incoming request', $context);
    }

    /**
     * Log the response details.
     */
    protected function logResponse(Request $request, Response $response, float $durationMs): void
    {
        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
        ];

        // Add user ID if authenticated
        if ($request->user()) {
            $context['user_id'] = $request->user()->id;
        }

        // Log level based on status code
        if ($response->getStatusCode() >= 500) {
            Log::channel('request')->error('Response completed', $context);
        } elseif ($response->getStatusCode() >= 400) {
            Log::channel('request')->warning('Response completed', $context);
        } else {
            Log::channel('request')->info('Response completed', $context);
        }
    }

    /**
     * Get relevant headers from request, redacting sensitive ones.
     */
    protected function getRelevantHeaders(Request $request): array
    {
        $relevant = [
            'content-type',
            'accept',
            'x-requested-with',
            'x-bokun-hmac',
            'x-bokun-event',
        ];

        $headers = [];
        foreach ($relevant as $header) {
            $value = $request->header($header);
            if ($value !== null) {
                if (in_array(strtolower($header), $this->sensitiveHeaders, true)) {
                    $headers[$header] = '[REDACTED]';
                } else {
                    $headers[$header] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Redact sensitive data from arrays.
     */
    protected function redactSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'api_key', 'auth'];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $data[$key] = '[REDACTED]';
                    break;
                }
            }
        }

        return $data;
    }
}
