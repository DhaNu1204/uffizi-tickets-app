<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller
 *
 * Provides API health check endpoints for monitoring system status.
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
}
