<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test unauthenticated access is denied.
     */
    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->getJson('/api/bookings');
        $response->assertStatus(401);
    }

    /**
     * Test listing bookings.
     */
    public function test_can_list_bookings(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->count(5)->create();

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'bokun_booking_id',
                        'bokun_product_id',
                        'product_name',
                        'customer_name',
                        'tour_date',
                        'pax',
                        'status',
                    ]
                ],
                'current_page',
                'total',
            ]);
    }

    /**
     * Test filtering bookings by status.
     */
    public function test_can_filter_bookings_by_status(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->count(3)->create(['status' => 'PENDING_TICKET']);
        Booking::factory()->count(2)->create(['status' => 'TICKET_PURCHASED']);

        $response = $this->getJson('/api/bookings?status=PENDING_TICKET');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    /**
     * Test filtering bookings by date range.
     */
    public function test_can_filter_bookings_by_date_range(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->create(['tour_date' => '2025-01-15']);
        Booking::factory()->create(['tour_date' => '2025-01-20']);
        Booking::factory()->create(['tour_date' => '2025-02-10']);

        $response = $this->getJson('/api/bookings?date_from=2025-01-01&date_to=2025-01-31');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('total'));
    }

    /**
     * Test searching bookings by customer name.
     */
    public function test_can_search_bookings_by_customer_name(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->create(['customer_name' => 'John Smith']);
        Booking::factory()->create(['customer_name' => 'Jane Doe']);
        Booking::factory()->create(['customer_name' => 'John Doe']);

        $response = $this->getJson('/api/bookings?search=John');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('total'));
    }

    /**
     * Test getting a single booking.
     */
    public function test_can_get_single_booking(): void
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create();

        $response = $this->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $booking->id,
                'bokun_booking_id' => $booking->bokun_booking_id,
            ]);
    }

    /**
     * Test getting non-existent booking returns 404.
     */
    public function test_get_nonexistent_booking_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/bookings/99999');

        $response->assertStatus(404);
    }

    /**
     * Test creating a booking.
     */
    public function test_can_create_booking(): void
    {
        Sanctum::actingAs($this->user);

        $bookingData = [
            'bokun_booking_id' => 'TEST-123',
            'bokun_product_id' => '961802',
            'product_name' => 'Uffizi Gallery Tour',
            'customer_name' => 'John Smith',
            'tour_date' => '2025-02-15',
            'pax' => 4,
        ];

        $response = $this->postJson('/api/bookings', $bookingData);

        $response->assertStatus(201)
            ->assertJson([
                'bokun_booking_id' => 'TEST-123',
                'status' => 'PENDING_TICKET',
            ]);

        $this->assertDatabaseHas('bookings', [
            'bokun_booking_id' => 'TEST-123',
        ]);
    }

    /**
     * Test creating booking with invalid data.
     */
    public function test_create_booking_validation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/bookings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'bokun_booking_id',
                'bokun_product_id',
                'product_name',
                'customer_name',
                'tour_date',
                'pax',
            ]);
    }

    /**
     * Test creating duplicate booking fails.
     */
    public function test_create_duplicate_booking_fails(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->create(['bokun_booking_id' => 'EXISTING-123']);

        $response = $this->postJson('/api/bookings', [
            'bokun_booking_id' => 'EXISTING-123',
            'bokun_product_id' => '961802',
            'product_name' => 'Tour',
            'customer_name' => 'John',
            'tour_date' => '2025-02-15',
            'pax' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bokun_booking_id']);
    }

    /**
     * Test updating a booking.
     */
    public function test_can_update_booking(): void
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create(['status' => 'PENDING_TICKET']);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'status' => 'TICKET_PURCHASED',
            'reference_number' => 'REF-456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'TICKET_PURCHASED',
                'reference_number' => 'REF-456',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'TICKET_PURCHASED',
            'reference_number' => 'REF-456',
        ]);
    }

    /**
     * Test updating a guided tour booking with guide_name.
     */
    public function test_can_update_booking_with_guide_name(): void
    {
        Sanctum::actingAs($this->user);

        // Create a guided tour booking (product ID 961801 is a guided tour)
        $booking = Booking::factory()->create([
            'bokun_product_id' => '961801',
            'status' => 'PENDING_TICKET',
            'guide_name' => null,
        ]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'guide_name' => 'Marco Rossi',
            'reference_number' => 'UFF-789',
            'status' => 'TICKET_PURCHASED',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'guide_name' => 'Marco Rossi',
                'reference_number' => 'UFF-789',
                'status' => 'TICKET_PURCHASED',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'guide_name' => 'Marco Rossi',
            'reference_number' => 'UFF-789',
        ]);
    }

    /**
     * Test updating guide_name to null (clearing it).
     */
    public function test_can_clear_guide_name(): void
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create([
            'bokun_product_id' => '961801',
            'guide_name' => 'Previous Guide',
        ]);

        $response = $this->putJson("/api/bookings/{$booking->id}", [
            'guide_name' => null,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'guide_name' => null,
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'guide_name' => null,
        ]);
    }

    /**
     * Test deleting a booking (soft delete).
     */
    public function test_can_delete_booking(): void
    {
        Sanctum::actingAs($this->user);

        $booking = Booking::factory()->create();

        $response = $this->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Booking deleted successfully']);

        $this->assertSoftDeleted('bookings', ['id' => $booking->id]);
    }

    /**
     * Test getting booking statistics.
     */
    public function test_can_get_booking_stats(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->count(3)->create([
            'status' => 'PENDING_TICKET',
            'tour_date' => now()->addDays(3),
        ]);
        Booking::factory()->count(2)->create([
            'status' => 'TICKET_PURCHASED',
            'tour_date' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/bookings/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_bookings',
                    'pending_tickets',
                    'purchased_tickets',
                    'upcoming_pending_7_days',
                ],
                'by_product',
                'by_day',
                'date_range',
            ]);
    }

    /**
     * Test pagination works correctly.
     */
    public function test_pagination_works(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->count(25)->create();

        $response = $this->getJson('/api/bookings?per_page=10&page=2');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(2, $response->json('current_page'));
        $this->assertEquals(25, $response->json('total'));
    }

    /**
     * Test sorting bookings.
     */
    public function test_can_sort_bookings(): void
    {
        Sanctum::actingAs($this->user);

        Booking::factory()->create(['customer_name' => 'Alice']);
        Booking::factory()->create(['customer_name' => 'Charlie']);
        Booking::factory()->create(['customer_name' => 'Bob']);

        $response = $this->getJson('/api/bookings?sort_by=customer_name&sort_dir=asc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Alice', $data[0]['customer_name']);
        $this->assertEquals('Bob', $data[1]['customer_name']);
        $this->assertEquals('Charlie', $data[2]['customer_name']);
    }
}
