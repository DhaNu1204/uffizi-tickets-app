<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureSlowQueryLogging();
        $this->configureFailedJobTracking();
    }

    /**
     * Log slow database queries (>500ms) to help identify performance issues.
     */
    protected function configureSlowQueryLogging(): void
    {
        $slowQueryThreshold = (int) env('SLOW_QUERY_THRESHOLD_MS', 500);

        DB::listen(function ($query) use ($slowQueryThreshold) {
            if ($query->time > $slowQueryThreshold) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'threshold_ms' => $slowQueryThreshold,
                ]);

                // Report to Sentry if available
                if (function_exists('app') && app()->bound('sentry')) {
                    \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                        \Sentry\Breadcrumb::LEVEL_WARNING,
                        \Sentry\Breadcrumb::TYPE_DEFAULT,
                        'slow_query',
                        'Slow query: ' . $query->time . 'ms',
                        ['sql' => $query->sql, 'time_ms' => $query->time]
                    ));
                }
            }
        });
    }

    /**
     * Track failed jobs and report to Sentry for monitoring.
     */
    protected function configureFailedJobTracking(): void
    {
        Queue::failing(function (JobFailed $event) {
            Log::error('Job failed', [
                'job' => get_class($event->job),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'exception' => $event->exception->getMessage(),
                'trace' => $event->exception->getTraceAsString(),
            ]);

            // Report to Sentry if available
            if (function_exists('app') && app()->bound('sentry')) {
                \Sentry\captureException($event->exception);
            }
        });
    }
}
