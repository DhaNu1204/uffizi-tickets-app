<?php

namespace Database\Factories;

use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookLog>
 */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'processed', 'failed'];
        $eventTypes = ['bookings/confirmed', 'bookings/cancelled', 'bookings/updated', 'unknown'];

        return [
            'event_type' => fake()->randomElement($eventTypes),
            'confirmation_code' => strtoupper(fake()->bothify('??-######')),
            'payload' => [
                'confirmationCode' => strtoupper(fake()->bothify('??-######')),
                'productBookings' => [],
            ],
            'headers' => [
                'content-type' => ['application/json'],
                'x-bokun-signature' => [fake()->sha256()],
            ],
            'status' => fake()->randomElement($statuses),
            'error_message' => null,
            'retry_count' => 0,
            'processed_at' => null,
        ];
    }

    /**
     * Indicate that the webhook is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the webhook is processed.
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the webhook failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
            'retry_count' => fake()->numberBetween(1, 3),
        ]);
    }

    /**
     * Set a specific event type.
     */
    public function eventType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => $type,
        ]);
    }
}
