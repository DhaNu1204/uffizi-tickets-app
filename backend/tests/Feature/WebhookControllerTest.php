<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Disable HMAC verification for webhook tests
        config(['services.bokun.secret_key' => null]);
    }

    /**
     * Test webhook endpoint accepts POST requests.
     */
    public function test_webhook_endpoint_accepts_post(): void
    {
        $response = $this->postJson('/api/webhook/bokun', [
            'confirmationCode' => 'TEST-123',
            'productBookings' => [],
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test webhook logs payload.
     */
    public function test_webhook_logs_payload(): void
    {
        $payload = [
            'confirmationCode' => 'TEST-123',
            'eventType' => 'bookings/confirmed',
            'productBookings' => [],
        ];

        $this->postJson('/api/webhook/bokun', $payload);

        $this->assertDatabaseHas('webhook_logs', [
            'confirmation_code' => 'TEST-123',
            'event_type' => 'bookings/confirmed',
        ]);
    }

    /**
     * Test webhook creates booking for Uffizi product.
     */
    public function test_webhook_creates_booking_for_uffizi_product(): void
    {
        $payload = [
            'confirmationCode' => 'BOOK-456',
            'customer' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'productBookings' => [
                [
                    'product' => [
                        'id' => 961802,
                        'title' => 'Uffizi Gallery Tour',
                    ],
                    'date' => '2025-03-15',
                    'passengers' => [
                        ['name' => 'John'],
                        ['name' => 'Jane'],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/bokun', $payload);

        $response->assertStatus(200)
            ->assertJson(['count' => 1]);

        $this->assertDatabaseHas('bookings', [
            'bokun_booking_id' => 'BOOK-456',
            'bokun_product_id' => '961802',
            'customer_name' => 'John Doe',
            'pax' => 2,
        ]);
    }

    /**
     * Test webhook ignores non-Uffizi products.
     */
    public function test_webhook_ignores_non_uffizi_products(): void
    {
        $payload = [
            'confirmationCode' => 'BOOK-789',
            'customer' => ['firstName' => 'Test', 'lastName' => 'User'],
            'productBookings' => [
                [
                    'product' => [
                        'id' => 999999,
                        'title' => 'Some Other Tour',
                    ],
                    'date' => '2025-03-15',
                    'passengers' => [],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/bokun', $payload);

        $response->assertStatus(200)
            ->assertJson(['count' => 0]);

        $this->assertDatabaseMissing('bookings', [
            'bokun_booking_id' => 'BOOK-789',
        ]);
    }

    /**
     * Test webhook handles cancellation.
     */
    public function test_webhook_handles_cancellation(): void
    {
        $booking = Booking::factory()->create([
            'bokun_booking_id' => 'CANCEL-123',
        ]);

        $payload = [
            'confirmationCode' => 'CANCEL-123',
            'eventType' => 'CANCELLED',
        ];

        $response = $this->postJson('/api/webhook/bokun', $payload);

        $response->assertStatus(200)
            ->assertJson(['cancelled' => 1]);

        $this->assertSoftDeleted('bookings', ['id' => $booking->id]);
    }

    /**
     * Test listing webhook logs requires auth.
     */
    public function test_listing_webhook_logs_requires_auth(): void
    {
        $response = $this->getJson('/api/webhooks');
        $response->assertStatus(401);
    }

    /**
     * Test can list webhook logs.
     */
    public function test_can_list_webhook_logs(): void
    {
        Sanctum::actingAs($this->user);

        WebhookLog::factory()->count(5)->create();

        $response = $this->getJson('/api/webhooks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'event_type',
                        'confirmation_code',
                        'status',
                        'created_at',
                    ]
                ],
                'total',
            ]);
    }

    /**
     * Test can filter webhook logs by status.
     */
    public function test_can_filter_webhook_logs_by_status(): void
    {
        Sanctum::actingAs($this->user);

        WebhookLog::factory()->count(3)->create(['status' => 'processed']);
        WebhookLog::factory()->count(2)->create(['status' => 'failed']);

        $response = $this->getJson('/api/webhooks?status=failed');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('total'));
    }

    /**
     * Test can get webhook stats.
     */
    public function test_can_get_webhook_stats(): void
    {
        Sanctum::actingAs($this->user);

        WebhookLog::factory()->count(3)->create(['status' => 'processed']);
        WebhookLog::factory()->count(2)->create(['status' => 'failed']);
        WebhookLog::factory()->count(1)->create(['status' => 'pending']);

        $response = $this->getJson('/api/webhooks/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 6,
                'processed' => 3,
                'failed' => 2,
                'pending' => 1,
            ]);
    }

    /**
     * Test can retry single webhook.
     */
    public function test_can_retry_single_webhook(): void
    {
        Sanctum::actingAs($this->user);

        $webhook = WebhookLog::factory()->create([
            'status' => 'failed',
            'event_type' => 'unknown',
            'payload' => ['productBookings' => []],
        ]);

        $response = $this->postJson("/api/webhooks/{$webhook->id}/retry");

        $response->assertStatus(200);
    }

    /**
     * Test cannot retry already processed webhook.
     */
    public function test_cannot_retry_processed_webhook(): void
    {
        Sanctum::actingAs($this->user);

        $webhook = WebhookLog::factory()->create(['status' => 'processed']);

        $response = $this->postJson("/api/webhooks/{$webhook->id}/retry");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Webhook already processed']);
    }

    /**
     * Test can trigger retry all failed webhooks.
     */
    public function test_can_retry_all_failed_webhooks(): void
    {
        Sanctum::actingAs($this->user);

        WebhookLog::factory()->count(3)->create([
            'status' => 'failed',
            'retry_count' => 1,
        ]);

        $response = $this->postJson('/api/webhooks/retry-all');

        $response->assertStatus(200)
            ->assertJson(['retryable_count' => 3]);
    }

    /**
     * Test cleanup old webhook logs.
     */
    public function test_can_cleanup_old_webhook_logs(): void
    {
        Sanctum::actingAs($this->user);

        // Create old logs
        WebhookLog::factory()->count(3)->create([
            'created_at' => now()->subDays(40),
            'status' => 'processed',
        ]);

        // Create recent logs
        WebhookLog::factory()->count(2)->create([
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->deleteJson('/api/webhooks/cleanup', [
            'days' => 30,
            'status' => 'processed',
        ]);

        $response->assertStatus(200)
            ->assertJson(['deleted_count' => 3]);

        $this->assertEquals(2, WebhookLog::count());
    }
}
