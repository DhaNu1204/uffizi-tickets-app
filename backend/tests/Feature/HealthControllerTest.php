<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HealthControllerTest extends TestCase
{
    /**
     * Test basic health check returns OK status.
     */
    public function test_health_check_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'database',
                'timestamp',
                'version',
            ])
            ->assertJson([
                'status' => 'ok',
                'database' => 'connected',
            ]);
    }

    /**
     * Test health check timestamp is valid ISO 8601 format.
     */
    public function test_health_check_timestamp_is_valid(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $data = $response->json();
        $timestamp = $data['timestamp'];

        // Verify timestamp can be parsed
        $parsedDate = \DateTime::createFromFormat(\DateTime::ATOM, $timestamp);
        $this->assertNotFalse($parsedDate, 'Timestamp should be valid ISO 8601 format');
    }

    /**
     * Test detailed health check returns all checks.
     */
    public function test_detailed_health_check_returns_all_checks(): void
    {
        $response = $this->getJson('/api/health/detailed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'database',
                    'storage',
                    'cache',
                ],
                'timestamp',
                'version',
                'environment',
            ]);
    }

    /**
     * Test detailed health check database has latency.
     */
    public function test_detailed_health_check_database_has_latency(): void
    {
        $response = $this->getJson('/api/health/detailed');

        $response->assertStatus(200);

        $data = $response->json();

        if ($data['checks']['database']['status'] === 'ok') {
            $this->assertArrayHasKey('latency_ms', $data['checks']['database']);
            $this->assertIsNumeric($data['checks']['database']['latency_ms']);
        }
    }

    /**
     * Test health check does not require authentication.
     */
    public function test_health_check_does_not_require_authentication(): void
    {
        // Ensure no authentication token is sent
        $response = $this->getJson('/api/health');

        // Should not return 401 Unauthorized
        $this->assertNotEquals(401, $response->getStatusCode());
        $response->assertStatus(200);
    }

    /**
     * Test detailed health check does not require authentication.
     */
    public function test_detailed_health_check_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/health/detailed');

        $this->assertNotEquals(401, $response->getStatusCode());
        $response->assertStatus(200);
    }

    /**
     * Test health check returns version from config.
     */
    public function test_health_check_returns_version(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('version', $data);
        $this->assertNotEmpty($data['version']);
    }
}
