<?php

namespace Tests\Unit;

use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WebhookLogTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test pending scope.
     */
    public function test_pending_scope(): void
    {
        WebhookLog::factory()->count(3)->create(['status' => 'pending']);
        WebhookLog::factory()->count(2)->create(['status' => 'processed']);
        WebhookLog::factory()->count(1)->create(['status' => 'failed']);

        $pending = WebhookLog::pending()->get();

        $this->assertCount(3, $pending);
        $this->assertTrue($pending->every(fn ($log) => $log->status === 'pending'));
    }

    /**
     * Test retryable scope.
     */
    public function test_retryable_scope(): void
    {
        // Retryable: failed with retry_count < max
        WebhookLog::factory()->create(['status' => 'failed', 'retry_count' => 1]);
        WebhookLog::factory()->create(['status' => 'failed', 'retry_count' => 2]);

        // Not retryable: max retries reached
        WebhookLog::factory()->create(['status' => 'failed', 'retry_count' => 3]);
        WebhookLog::factory()->create(['status' => 'failed', 'retry_count' => 5]);

        // Not retryable: processed
        WebhookLog::factory()->create(['status' => 'processed']);

        $retryable = WebhookLog::retryable(3)->get();

        $this->assertCount(2, $retryable);
    }

    /**
     * Test markAsProcessed method.
     */
    public function test_mark_as_processed(): void
    {
        $webhook = WebhookLog::factory()->create(['status' => 'pending']);

        $this->assertNull($webhook->processed_at);

        $webhook->markAsProcessed();

        $webhook->refresh();
        $this->assertEquals('processed', $webhook->status);
        $this->assertNotNull($webhook->processed_at);
    }

    /**
     * Test markAsFailed method.
     */
    public function test_mark_as_failed(): void
    {
        $webhook = WebhookLog::factory()->create([
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        $webhook->markAsFailed('Test error message');

        $webhook->refresh();
        $this->assertEquals('failed', $webhook->status);
        $this->assertEquals('Test error message', $webhook->error_message);
        $this->assertEquals(1, $webhook->retry_count);
    }

    /**
     * Test markAsFailed increments retry count.
     */
    public function test_mark_as_failed_increments_retry_count(): void
    {
        $webhook = WebhookLog::factory()->create([
            'status' => 'pending',
            'retry_count' => 2,
        ]);

        $webhook->markAsFailed('Another error');

        $webhook->refresh();
        $this->assertEquals(3, $webhook->retry_count);
    }

    /**
     * Test resetForRetry method.
     */
    public function test_reset_for_retry(): void
    {
        $webhook = WebhookLog::factory()->create([
            'status' => 'failed',
            'error_message' => 'Previous error',
            'retry_count' => 2,
        ]);

        $webhook->resetForRetry();

        $webhook->refresh();
        $this->assertEquals('pending', $webhook->status);
        $this->assertNull($webhook->error_message);
        $this->assertEquals(2, $webhook->retry_count); // Count not reset
    }

    /**
     * Test payload is cast to array.
     */
    public function test_payload_cast_to_array(): void
    {
        $webhook = WebhookLog::factory()->create([
            'payload' => ['key' => 'value', 'nested' => ['data' => 123]],
        ]);

        $webhook->refresh();

        $this->assertIsArray($webhook->payload);
        $this->assertEquals('value', $webhook->payload['key']);
        $this->assertEquals(123, $webhook->payload['nested']['data']);
    }

    /**
     * Test headers is cast to array.
     */
    public function test_headers_cast_to_array(): void
    {
        $webhook = WebhookLog::factory()->create([
            'headers' => ['content-type' => ['application/json']],
        ]);

        $webhook->refresh();

        $this->assertIsArray($webhook->headers);
        $this->assertEquals(['application/json'], $webhook->headers['content-type']);
    }

    /**
     * Test processed_at is cast to datetime.
     */
    public function test_processed_at_cast_to_datetime(): void
    {
        $webhook = WebhookLog::factory()->create([
            'status' => 'processed',
            'processed_at' => '2025-01-15 10:30:00',
        ]);

        $webhook->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $webhook->processed_at);
        $this->assertEquals('2025-01-15', $webhook->processed_at->toDateString());
    }
}
