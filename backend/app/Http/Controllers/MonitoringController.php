<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller for delivery monitoring and alerting.
 *
 * Provides endpoints for tracking message delivery health,
 * failed messages, and channel-specific statistics.
 */
class MonitoringController extends Controller
{
    /**
     * Alert thresholds
     */
    protected const DELIVERY_RATE_THRESHOLD = 0.90; // 90%
    protected const CONSECUTIVE_FAILURE_THRESHOLD = 3;

    /**
     * Cache keys
     */
    protected const STATS_CACHE_KEY = 'delivery_monitoring_stats';
    protected const ALERTS_CACHE_KEY = 'delivery_alerts';

    /**
     * Get delivery statistics overview.
     *
     * GET /api/monitoring/delivery-stats
     *
     * Returns:
     * - Overall delivery rates
     * - Stats by channel (WhatsApp, SMS, Email)
     * - Average delivery time
     * - Trends (24h, 7d, 30d)
     */
    public function deliveryStats(Request $request): JsonResponse
    {
        $period = $request->input('period', '7d');
        $startDate = $this->getStartDate($period);

        // Overall stats
        $totalMessages = Message::where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->count();

        $deliveredMessages = Message::where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['delivered', 'read'])
            ->count();

        $failedMessages = Message::where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->where('status', 'failed')
            ->count();

        $pendingMessages = Message::where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['pending', 'queued', 'sent'])
            ->count();

        // Calculate delivery rate
        $deliveryRate = $totalMessages > 0
            ? round(($deliveredMessages / $totalMessages) * 100, 2)
            : 0;

        // Stats by channel
        $channelStats = $this->getChannelStats($startDate);

        // Average delivery time (sent_at to delivered_at)
        $avgDeliveryTime = $this->getAverageDeliveryTime($startDate);

        // Check for alerts
        $alerts = $this->checkAlerts($deliveryRate, $channelStats);

        return response()->json([
            'period' => $period,
            'start_date' => $startDate->toIso8601String(),
            'overview' => [
                'total' => $totalMessages,
                'delivered' => $deliveredMessages,
                'failed' => $failedMessages,
                'pending' => $pendingMessages,
                'delivery_rate' => $deliveryRate,
                'delivery_rate_status' => $deliveryRate >= (self::DELIVERY_RATE_THRESHOLD * 100) ? 'healthy' : 'warning',
            ],
            'by_channel' => $channelStats,
            'avg_delivery_time_seconds' => $avgDeliveryTime,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Get failed messages with details.
     *
     * GET /api/monitoring/failed-messages
     */
    public function failedMessages(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 50), 100);
        $channel = $request->input('channel');

        $query = Message::where('direction', 'outbound')
            ->where('status', 'failed')
            ->orderBy('failed_at', 'desc');

        if ($channel) {
            $query->where('channel', $channel);
        }

        $messages = $query->take($limit)->get();

        // Group by error type
        $errorGroups = $messages->groupBy(function ($msg) {
            return $this->categorizeError($msg->error_message);
        });

        return response()->json([
            'total_failed' => $messages->count(),
            'messages' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'booking_id' => $msg->booking_id,
                    'channel' => $msg->channel,
                    'recipient' => $msg->recipient,
                    'error_message' => $msg->error_message,
                    'error_category' => $this->categorizeError($msg->error_message),
                    'retry_count' => $msg->retry_count,
                    'failed_at' => $msg->failed_at?->toIso8601String(),
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            }),
            'error_breakdown' => $errorGroups->map->count(),
        ]);
    }

    /**
     * Get channel health status.
     *
     * GET /api/monitoring/channel-health
     */
    public function channelHealth(): JsonResponse
    {
        $channels = ['whatsapp', 'sms', 'email'];
        $health = [];

        foreach ($channels as $channel) {
            // Get last 10 messages for this channel
            $recentMessages = Message::where('direction', 'outbound')
                ->where('channel', $channel)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $failedCount = $recentMessages->where('status', 'failed')->count();
            $consecutiveFailures = $this->countConsecutiveFailures($recentMessages);

            // Calculate recent delivery rate (last hour)
            $lastHourStats = $this->getChannelStatsForPeriod($channel, now()->subHour());

            $health[$channel] = [
                'status' => $this->determineChannelStatus($consecutiveFailures, $lastHourStats['delivery_rate'] ?? 0),
                'recent_messages' => $recentMessages->count(),
                'recent_failures' => $failedCount,
                'consecutive_failures' => $consecutiveFailures,
                'last_hour' => $lastHourStats,
                'last_message_at' => $recentMessages->first()?->created_at?->toIso8601String(),
            ];

            // Log alert if channel has issues
            if ($consecutiveFailures >= self::CONSECUTIVE_FAILURE_THRESHOLD) {
                $this->logChannelAlert($channel, $consecutiveFailures);
            }
        }

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'channels' => $health,
            'overall_status' => $this->determineOverallStatus($health),
        ]);
    }

    /**
     * Get daily delivery summary for logging/alerting.
     *
     * GET /api/monitoring/daily-summary
     */
    public function dailySummary(): JsonResponse
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $todayStats = $this->getStatsForDate($today);
        $yesterdayStats = $this->getStatsForDate($yesterday);

        // Log daily summary
        Log::info('Daily delivery summary', [
            'date' => $today->toDateString(),
            'total_sent' => $todayStats['total'],
            'delivered' => $todayStats['delivered'],
            'failed' => $todayStats['failed'],
            'delivery_rate' => $todayStats['delivery_rate'],
            'comparison' => [
                'yesterday_rate' => $yesterdayStats['delivery_rate'],
                'change' => $todayStats['delivery_rate'] - $yesterdayStats['delivery_rate'],
            ],
        ]);

        return response()->json([
            'today' => $todayStats,
            'yesterday' => $yesterdayStats,
            'trend' => [
                'rate_change' => round($todayStats['delivery_rate'] - $yesterdayStats['delivery_rate'], 2),
                'volume_change' => $todayStats['total'] - $yesterdayStats['total'],
            ],
        ]);
    }

    /**
     * Get stats by channel for a given period.
     */
    protected function getChannelStats(Carbon $startDate): array
    {
        $channels = ['whatsapp', 'sms', 'email'];
        $stats = [];

        foreach ($channels as $channel) {
            $total = Message::where('direction', 'outbound')
                ->where('channel', $channel)
                ->where('created_at', '>=', $startDate)
                ->count();

            $delivered = Message::where('direction', 'outbound')
                ->where('channel', $channel)
                ->where('created_at', '>=', $startDate)
                ->whereIn('status', ['delivered', 'read'])
                ->count();

            $failed = Message::where('direction', 'outbound')
                ->where('channel', $channel)
                ->where('created_at', '>=', $startDate)
                ->where('status', 'failed')
                ->count();

            $stats[$channel] = [
                'total' => $total,
                'delivered' => $delivered,
                'failed' => $failed,
                'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Get stats for a specific channel and period.
     */
    protected function getChannelStatsForPeriod(string $channel, Carbon $startDate): array
    {
        $total = Message::where('direction', 'outbound')
            ->where('channel', $channel)
            ->where('created_at', '>=', $startDate)
            ->count();

        $delivered = Message::where('direction', 'outbound')
            ->where('channel', $channel)
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['delivered', 'read'])
            ->count();

        return [
            'total' => $total,
            'delivered' => $delivered,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get stats for a specific date.
     */
    protected function getStatsForDate(Carbon $date): array
    {
        $endDate = $date->copy()->endOfDay();

        $total = Message::where('direction', 'outbound')
            ->whereBetween('created_at', [$date, $endDate])
            ->count();

        $delivered = Message::where('direction', 'outbound')
            ->whereBetween('created_at', [$date, $endDate])
            ->whereIn('status', ['delivered', 'read'])
            ->count();

        $failed = Message::where('direction', 'outbound')
            ->whereBetween('created_at', [$date, $endDate])
            ->where('status', 'failed')
            ->count();

        return [
            'date' => $date->toDateString(),
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate average delivery time in seconds.
     */
    protected function getAverageDeliveryTime(Carbon $startDate): ?float
    {
        $messages = Message::where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('sent_at')
            ->whereNotNull('delivered_at')
            ->get();

        if ($messages->isEmpty()) {
            return null;
        }

        $totalSeconds = $messages->sum(function ($msg) {
            return $msg->delivered_at->diffInSeconds($msg->sent_at);
        });

        return round($totalSeconds / $messages->count(), 2);
    }

    /**
     * Count consecutive failures from recent messages.
     */
    protected function countConsecutiveFailures($messages): int
    {
        $count = 0;
        foreach ($messages as $msg) {
            if ($msg->status === 'failed') {
                $count++;
            } else {
                break;
            }
        }
        return $count;
    }

    /**
     * Categorize error message for grouping.
     */
    protected function categorizeError(?string $errorMessage): string
    {
        if (!$errorMessage) {
            return 'unknown';
        }

        $errorMessage = strtolower($errorMessage);

        if (str_contains($errorMessage, 'invalid') && str_contains($errorMessage, 'number')) {
            return 'invalid_number';
        }
        if (str_contains($errorMessage, 'undeliverable') || str_contains($errorMessage, 'unreachable')) {
            return 'unreachable';
        }
        if (str_contains($errorMessage, 'rate') || str_contains($errorMessage, 'limit')) {
            return 'rate_limited';
        }
        if (str_contains($errorMessage, 'timeout')) {
            return 'timeout';
        }
        if (str_contains($errorMessage, 'blocked') || str_contains($errorMessage, 'spam')) {
            return 'blocked';
        }
        if (str_contains($errorMessage, 'template')) {
            return 'template_error';
        }

        return 'other';
    }

    /**
     * Determine channel health status.
     */
    protected function determineChannelStatus(int $consecutiveFailures, float $deliveryRate): string
    {
        if ($consecutiveFailures >= self::CONSECUTIVE_FAILURE_THRESHOLD) {
            return 'critical';
        }
        if ($deliveryRate < (self::DELIVERY_RATE_THRESHOLD * 100)) {
            return 'warning';
        }
        return 'healthy';
    }

    /**
     * Determine overall system status.
     */
    protected function determineOverallStatus(array $channelHealth): string
    {
        $statuses = array_column($channelHealth, 'status');

        if (in_array('critical', $statuses)) {
            return 'critical';
        }
        if (in_array('warning', $statuses)) {
            return 'warning';
        }
        return 'healthy';
    }

    /**
     * Check for alerts and return any active alerts.
     */
    protected function checkAlerts(float $deliveryRate, array $channelStats): array
    {
        $alerts = [];

        // Check overall delivery rate
        if ($deliveryRate < (self::DELIVERY_RATE_THRESHOLD * 100)) {
            $alerts[] = [
                'type' => 'delivery_rate',
                'severity' => 'warning',
                'message' => "Overall delivery rate ({$deliveryRate}%) is below threshold (" . (self::DELIVERY_RATE_THRESHOLD * 100) . "%)",
            ];

            // Log the alert
            Log::warning('Delivery rate alert', [
                'current_rate' => $deliveryRate,
                'threshold' => self::DELIVERY_RATE_THRESHOLD * 100,
            ]);
        }

        // Check channel-specific rates
        foreach ($channelStats as $channel => $stats) {
            if ($stats['total'] > 0 && $stats['delivery_rate'] < 80) {
                $alerts[] = [
                    'type' => 'channel_degraded',
                    'severity' => 'warning',
                    'channel' => $channel,
                    'message' => ucfirst($channel) . " delivery rate ({$stats['delivery_rate']}%) is low",
                ];
            }
        }

        return $alerts;
    }

    /**
     * Log channel alert.
     */
    protected function logChannelAlert(string $channel, int $consecutiveFailures): void
    {
        Log::error('Channel health alert', [
            'channel' => $channel,
            'consecutive_failures' => $consecutiveFailures,
            'threshold' => self::CONSECUTIVE_FAILURE_THRESHOLD,
            'message' => "Channel {$channel} has {$consecutiveFailures} consecutive failures",
        ]);
    }

    /**
     * Convert period string to start date.
     */
    protected function getStartDate(string $period): Carbon
    {
        return match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subWeek(),
        };
    }
}
