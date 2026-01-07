<?php

namespace Tests\Unit;

use App\Services\BokunService;
use Tests\TestCase;

class BokunServiceTest extends TestCase
{
    /**
     * Test getUffiziProductIds returns configured product IDs.
     */
    public function test_get_uffizi_product_ids(): void
    {
        config(['services.bokun.uffizi_product_ids' => ['111', '222', '333']]);

        $ids = BokunService::getUffiziProductIds();

        $this->assertIsArray($ids);
        $this->assertEquals(['111', '222', '333'], $ids);
    }

    /**
     * Test getUffiziProductIds returns empty array when not configured.
     */
    public function test_get_uffizi_product_ids_empty_when_not_configured(): void
    {
        config(['services.bokun.uffizi_product_ids' => []]);

        $ids = BokunService::getUffiziProductIds();

        $this->assertIsArray($ids);
        $this->assertEmpty($ids);
    }

    /**
     * Test webhook signature verification with valid signature.
     * Per Bokun docs: HMAC is calculated over sorted x-bokun-* headers (excluding x-bokun-hmac).
     */
    public function test_verify_webhook_signature_valid(): void
    {
        $secretKey = 'test-secret-key';

        // Create some x-bokun-* headers
        $headers = [
            'x-bokun-timestamp' => '1234567890',
            'x-bokun-event' => 'booking.created',
        ];

        // Calculate expected HMAC the same way as the service
        $bokunHeaders = [];
        foreach ($headers as $name => $value) {
            $lowerName = strtolower($name);
            if (str_starts_with($lowerName, 'x-bokun-')) {
                $bokunHeaders[$lowerName] = $value;
            }
        }
        ksort($bokunHeaders);
        $stringToSign = http_build_query($bokunHeaders);
        $expectedHmac = hash_hmac('sha256', $stringToSign, $secretKey);

        // Add the HMAC to headers
        $headers['x-bokun-hmac'] = $expectedHmac;

        $result = BokunService::verifyWebhookSignature($headers, $secretKey);

        $this->assertTrue($result);
    }

    /**
     * Test webhook signature verification with invalid signature.
     */
    public function test_verify_webhook_signature_invalid(): void
    {
        $secretKey = 'test-secret-key';

        $headers = [
            'x-bokun-timestamp' => '1234567890',
            'x-bokun-hmac' => 'invalid-signature',
        ];

        $result = BokunService::verifyWebhookSignature($headers, $secretKey);

        $this->assertFalse($result);
    }

    /**
     * Test webhook signature verification with missing HMAC header.
     */
    public function test_verify_webhook_signature_missing_hmac(): void
    {
        $secretKey = 'test-secret-key';

        // Missing x-bokun-hmac header
        $headers = [
            'x-bokun-timestamp' => '1234567890',
            'x-bokun-event' => 'booking.created',
        ];

        $result = BokunService::verifyWebhookSignature($headers, $secretKey);

        $this->assertFalse($result);
    }

    /**
     * Test webhook signature verification with empty headers.
     */
    public function test_verify_webhook_signature_empty_headers(): void
    {
        $secretKey = 'test-secret-key';

        $result = BokunService::verifyWebhookSignature([], $secretKey);

        $this->assertFalse($result);
    }

    /**
     * Test service instantiation with config.
     */
    public function test_service_uses_config(): void
    {
        config([
            'services.bokun.access_key' => 'test-access-key',
            'services.bokun.secret_key' => 'test-secret-key',
            'services.bokun.base_url' => 'https://test-api.bokun.io',
        ]);

        $service = app(BokunService::class);

        // Service should be instantiated without errors
        $this->assertInstanceOf(BokunService::class, $service);
    }
}
