<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productIds = ['961802', '961801', '962885', '962886', '1130528'];
        $productNames = [
            '961802' => 'Uffizi Gallery Skip-the-Line Tour',
            '961801' => 'Uffizi Gallery Guided Tour',
            '962885' => 'Uffizi & Accademia Combo Tour',
            '962886' => 'Florence Art Tour with Uffizi',
            '1130528' => 'Uffizi VIP Experience',
        ];

        $productId = fake()->randomElement($productIds);

        return [
            'bokun_booking_id' => strtoupper(fake()->unique()->bothify('??-######')),
            'bokun_product_id' => $productId,
            'product_name' => $productNames[$productId],
            'customer_name' => fake()->name(),
            'tour_date' => fake()->dateTimeBetween('now', '+3 months'),
            'pax' => fake()->numberBetween(1, 8),
            'status' => fake()->randomElement(['PENDING_TICKET', 'TICKET_PURCHASED']),
            'reference_number' => fake()->optional(0.3)->numerify('REF-######'),
        ];
    }

    /**
     * Indicate that the booking is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING_TICKET',
            'reference_number' => null,
        ]);
    }

    /**
     * Indicate that the booking has tickets purchased.
     */
    public function purchased(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'TICKET_PURCHASED',
            'reference_number' => fake()->numerify('REF-######'),
        ]);
    }

    /**
     * Set a specific tour date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'tour_date' => $date,
        ]);
    }
}
