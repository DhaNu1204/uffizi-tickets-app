<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to monitor API response times and track performance metrics.
 *
 * Features:
 * - Logs slow responses (>2 seconds)
 * - Tracks average response times in cache
 * - Reports slow responses to Sentry
 * - Adds X-Response-Time header
 */
class PerformanceMonitor
{
    /**
     * Threshold in milliseconds for a "slow" response.
     */
    protected int $slowThreshold;

    /**
     * Cache key for storing performance metrics.
     */
    protected const METRICS_CACHE_KEY = 'api_performance_metrics';

    /**
     * How long to keep metrics in cache (minutes).
     */
    protected const METRICS_TTL = 60;

    public function __construct()
    {
        $this->slowThreshold = (int) env('SLOW_RESPONSE_THRESHOLD_MS', 2000);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Process the request
        $response = $next($request);

        // Calculate metrics
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsedMb = round((memory_get_usage(true) - $startMemory) / 1024 / 1024, 2);

        // Add response time header
        $response->headers->set('X-Response-Time', $durationMs . 'ms');

        // Track metrics
        $this->trackMetrics($request->path(), $durationMs, $memoryUsedMb);

        // Log slow responses
        if ($durationMs > $this->slowThreshold) {
            $this->logSlowResponse($request, $durationMs, $memoryUsedMb);
        }

        return $response;
    }

    /**
     * Track performance metrics in cache for monitoring.
     */
    protected function trackMetrics(string $path, float $durationMs, float $memoryMb): void
    {
        try {
            $metrics = Cache::get(self::METRICS_CACHE_KEY, [
                'total_requests' => 0,
                'total_duration_ms' => 0,
                'slow_requests' => 0,
                'endpoints' => [],
                'started_at' => now()->toIso8601String(),
            ]);

            $metrics['total_requests']++;
            $metrics['total_duration_ms'] += $durationMs;

            if ($durationMs > $this->slowThreshold) {
                $metrics['slow_requests']++;
            }

            // Track per-endpoint metrics (limit to top 50 endpoints)
            $endpoint = $this->normalizeEndpoint($path);
            if (!isset($metrics['endpoints'][$endpoint])) {
                $metrics['endpoints'][$endpoint] = [
                    'count' => 0,
                    'total_ms' => 0,
                    'max_ms' => 0,
                ];
            }

            $metrics['endpoints'][$endpoint]['count']++;
            $metrics['endpoints'][$endpoint]['total_ms'] += $durationMs;
            $metrics['endpoints'][$endpoint]['max_ms'] = max(
                $metrics['endpoints'][$endpoint]['max_ms'],
                $durationMs
            );

            // Limit endpoints tracked
            if (count($metrics['endpoints']) > 50) {
                // Keep top 50 by count
                arsort($metrics['endpoints']);
                $metrics['endpoints'] = array_slice($metrics['endpoints'], 0, 50, true);
            }

            Cache::put(self::METRICS_CACHE_KEY, $metrics, now()->addMinutes(self::METRICS_TTL));

        } catch (\Exception $e) {
            // Don't let metrics tracking break the request
            Log::debug('Failed to track performance metrics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log slow responses with details for debugging.
     */
    protected function logSlowResponse(Request $request, float $durationMs, float $memoryMb): void
    {
        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
            'duration_ms' => $durationMs,
            'threshold_ms' => $this->slowThreshold,
            'memory_mb' => $memoryMb,
            'user_id' => $request->user()?->id,
        ];

        Log::warning('Slow API response detected', $context);

        // Report to Sentry if available
        if (function_exists('app') && app()->bound('sentry')) {
            \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_WARNING,
                \Sentry\Breadcrumb::TYPE_DEFAULT,
                'performance',
                "Slow response: {$durationMs}ms on {$request->method()} {$request->path()}",
                $context
            ));
        }
    }

    /**
     * Normalize endpoint path for grouping (replace IDs with placeholders).
     */
    protected function normalizeEndpoint(string $path): string
    {
        // Replace numeric IDs with :id placeholder
        return preg_replace('/\/\d+/', '/:id', $path);
    }

    /**
     * Get current performance metrics from cache.
     */
    public static function getMetrics(): array
    {
        $metrics = Cache::get(self::METRICS_CACHE_KEY, [
            'total_requests' => 0,
            'total_duration_ms' => 0,
            'slow_requests' => 0,
            'endpoints' => [],
            'started_at' => now()->toIso8601String(),
        ]);

        // Calculate averages
        $avgDuration = $metrics['total_requests'] > 0
            ? round($metrics['total_duration_ms'] / $metrics['total_requests'], 2)
            : 0;

        // Calculate per-endpoint averages
        $endpointMetrics = [];
        foreach ($metrics['endpoints'] as $endpoint => $data) {
            $endpointMetrics[$endpoint] = [
                'count' => $data['count'],
                'avg_ms' => round($data['total_ms'] / $data['count'], 2),
                'max_ms' => $data['max_ms'],
            ];
        }

        // Sort by avg time descending
        uasort($endpointMetrics, fn($a, $b) => $b['avg_ms'] <=> $a['avg_ms']);

        return [
            'total_requests' => $metrics['total_requests'],
            'avg_response_ms' => $avgDuration,
            'slow_requests' => $metrics['slow_requests'],
            'slow_percentage' => $metrics['total_requests'] > 0
                ? round(($metrics['slow_requests'] / $metrics['total_requests']) * 100, 2)
                : 0,
            'started_at' => $metrics['started_at'],
            'endpoints' => array_slice($endpointMetrics, 0, 10, true), // Top 10 slowest
        ];
    }

    /**
     * Reset performance metrics.
     */
    public static function resetMetrics(): void
    {
        Cache::forget(self::METRICS_CACHE_KEY);
    }
}
