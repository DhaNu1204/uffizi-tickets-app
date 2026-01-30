<?php

namespace App\Http\Controllers;

use App\Http\Middleware\PerformanceMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller
 *
 * Provides API health check endpoints for monitoring system status
 * and performance metrics.
 */
class HealthController extends Controller
{
    /**
     * Basic health check endpoint.
     *
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $status = 'ok';
        $httpCode = 200;
        $databaseStatus = 'connected';

        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
        } catch (\Exception $e) {
            $status = 'error';
            $httpCode = 503;
            $databaseStatus = 'disconnected';
        }

        return response()->json([
            'status' => $status,
            'database' => $databaseStatus,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ], $httpCode);
    }

    /**
     * Detailed health check with additional metrics.
     *
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Check database connectivity.
     *
     * @return array
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
        }
    }

    /**
     * Check storage accessibility.
     *
     * @return array
     */
    private function checkStorage(): array
    {
        try {
            $testFile = storage_path('app/.health-check');
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);

            if ($content !== 'test') {
                throw new \Exception('Storage read/write mismatch');
            }

            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage not writable',
            ];
        }
    }

    /**
     * Check cache functionality.
     *
     * @return array
     */
    private function checkCache(): array
    {
        try {
            $key = 'health-check-' . uniqid();
            cache()->put($key, 'test', 10);
            $value = cache()->get($key);
            cache()->forget($key);

            if ($value !== 'test') {
                throw new \Exception('Cache read/write mismatch');
            }

            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache not working',
            ];
        }
    }

    /**
     * Performance metrics endpoint.
     *
     * GET /api/health/performance
     *
     * Returns:
     * - Average response times
     * - Slow request count and percentage
     * - Top slowest endpoints
     * - Database query time
     * - Memory usage
     *
     * @return JsonResponse
     */
    public function performance(): JsonResponse
    {
        // Get API performance metrics
        $metrics = PerformanceMonitor::getMetrics();

        // Get current database latency
        $dbLatency = $this->measureDatabaseLatency();

        // Get memory usage
        $memoryUsage = [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        // Get external API status (if configured)
        $externalApis = $this->checkExternalApis();

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'api_metrics' => $metrics,
            'database' => [
                'latency_ms' => $dbLatency,
                'status' => $dbLatency < 100 ? 'healthy' : ($dbLatency < 500 ? 'slow' : 'critical'),
            ],
            'memory' => $memoryUsage,
            'external_apis' => $externalApis,
            'thresholds' => [
                'slow_response_ms' => (int) env('SLOW_RESPONSE_THRESHOLD_MS', 2000),
                'slow_query_ms' => (int) env('SLOW_QUERY_THRESHOLD_MS', 500),
            ],
        ]);
    }

    /**
     * Measure database latency.
     *
     * @return float
     */
    private function measureDatabaseLatency(): float
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            return round((microtime(true) - $start) * 1000, 2);
        } catch (\Exception $e) {
            return -1;
        }
    }

    /**
     * Check external API connectivity.
     *
     * @return array
     */
    private function checkExternalApis(): array
    {
        $apis = [];

        // Check Twilio (if configured)
        if (config('services.twilio.account_sid')) {
            $apis['twilio'] = [
                'configured' => true,
                'status' => 'configured', // We don't ping Twilio to avoid rate limits
            ];
        }

        // Check AWS S3 (if configured)
        if (config('services.aws.bucket')) {
            $apis['aws_s3'] = [
                'configured' => true,
                'bucket' => config('services.aws.bucket'),
            ];
        }

        // Check Bokun (if configured)
        if (config('services.bokun.access_key')) {
            $apis['bokun'] = [
                'configured' => true,
                'status' => 'configured',
            ];
        }

        return $apis;
    }
}
