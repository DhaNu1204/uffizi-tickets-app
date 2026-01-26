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

    /*
    |--------------------------------------------------------------------------
    | extractParticipants Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test extractParticipants from activityBookings with pricingCategoryBookings (direct bookings).
     */
    public function test_extract_participants_from_activity_bookings_pricing_category(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'pricingCategoryBookings' => [
                        [
                            'passengerInfo' => [
                                'firstName' => 'John',
                                'lastName' => 'Smith',
                            ],
                            'pricingCategory' => [
                                'fullTitle' => 'Adult',
                            ],
                        ],
                        [
                            'passengerInfo' => [
                                'firstName' => 'Jane',
                                'lastName' => 'Doe',
                            ],
                            'pricingCategory' => [
                                'title' => 'Child',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertCount(2, $participants);
        $this->assertEquals('John Smith', $participants[0]['name']);
        $this->assertEquals('Adult', $participants[0]['type']);
        $this->assertEquals('Jane Doe', $participants[1]['name']);
        $this->assertEquals('Child', $participants[1]['type']);
    }

    /**
     * Test extractParticipants from activityBookings passengers array (reseller bookings).
     */
    public function test_extract_participants_from_activity_bookings_passengers(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'passengers' => [
                        [
                            'firstName' => 'Marco',
                            'lastName' => 'Rossi',
                            'pricingCategoryTitle' => 'Adult',
                        ],
                        [
                            'firstName' => 'Lucia',
                            'lastName' => 'Bianchi',
                            'category' => 'Senior',
                        ],
                    ],
                ],
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertCount(2, $participants);
        $this->assertEquals('Marco Rossi', $participants[0]['name']);
        $this->assertEquals('Adult', $participants[0]['type']);
        $this->assertEquals('Lucia Bianchi', $participants[1]['name']);
        $this->assertEquals('Senior', $participants[1]['type']);
    }

    /**
     * Test extractParticipants from productBookings passengers array.
     */
    public function test_extract_participants_from_product_bookings(): void
    {
        $bookingDetails = [
            'productBookings' => [
                [
                    'passengers' => [
                        [
                            'firstName' => 'Hans',
                            'lastName' => 'Mueller',
                            'type' => 'Adult',
                        ],
                    ],
                ],
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertCount(1, $participants);
        $this->assertEquals('Hans Mueller', $participants[0]['name']);
        $this->assertEquals('Adult', $participants[0]['type']);
    }

    /**
     * Test extractParticipants from top-level passengers array.
     */
    public function test_extract_participants_from_top_level_passengers(): void
    {
        $bookingDetails = [
            'passengers' => [
                [
                    'firstName' => 'Pierre',
                    'lastName' => 'Dupont',
                    'pricingCategoryTitle' => 'Adult',
                ],
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertCount(1, $participants);
        $this->assertEquals('Pierre Dupont', $participants[0]['name']);
        $this->assertEquals('Adult', $participants[0]['type']);
    }

    /**
     * Test extractParticipants from guests array.
     */
    public function test_extract_participants_from_guests(): void
    {
        $bookingDetails = [
            'guests' => [
                [
                    'firstName' => 'Yuki',
                    'lastName' => 'Tanaka',
                    'type' => 'Adult',
                ],
                [
                    'first_name' => 'Kenji',
                    'last_name' => 'Sato',
                    'category' => 'Child',
                ],
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertCount(2, $participants);
        $this->assertEquals('Yuki Tanaka', $participants[0]['name']);
        $this->assertEquals('Adult', $participants[0]['type']);
        $this->assertEquals('Kenji Sato', $participants[1]['name']);
        $this->assertEquals('Child', $participants[1]['type']);
    }

    /**
     * Test extractParticipants returns empty array when no participants found.
     */
    public function test_extract_participants_returns_empty_when_none_found(): void
    {
        $bookingDetails = [
            'customer' => [
                'firstName' => 'Test',
                'lastName' => 'Customer',
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertIsArray($participants);
        $this->assertEmpty($participants);
    }

    /**
     * Test extractParticipants handles empty names gracefully.
     */
    public function test_extract_participants_skips_empty_names(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'passengers' => [
                        [
                            'firstName' => '',
                            'lastName' => '',
                        ],
                        [
                            'firstName' => 'Valid',
                            'lastName' => 'Name',
                        ],
                    ],
                ],
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertCount(1, $participants);
        $this->assertEquals('Valid Name', $participants[0]['name']);
    }

    /**
     * Test extractParticipants uses Guest as default type.
     */
    public function test_extract_participants_defaults_to_guest_type(): void
    {
        $bookingDetails = [
            'passengers' => [
                [
                    'firstName' => 'No',
                    'lastName' => 'Type',
                    // No type, category, or pricingCategoryTitle
                ],
            ],
        ];

        $participants = BokunService::extractParticipants($bookingDetails);

        $this->assertCount(1, $participants);
        $this->assertEquals('Guest', $participants[0]['type']);
    }

    /*
    |--------------------------------------------------------------------------
    | extractHasAudioGuide Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test extractHasAudioGuide returns true for audio guide rate ID.
     */
    public function test_extract_has_audio_guide_with_rate_id(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'activity' => ['id' => 961802],
                    'pricingCategoryBookings' => [
                        [
                            'rate' => ['id' => 2263305],
                        ],
                    ],
                ],
            ],
        ];

        $result = BokunService::extractHasAudioGuide($bookingDetails);

        $this->assertTrue($result);
    }

    /**
     * Test extractHasAudioGuide returns true for audio guide rate code TG2.
     */
    public function test_extract_has_audio_guide_with_rate_code(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'activity' => ['id' => 961802],
                    'pricingCategoryBookings' => [
                        [
                            'rate' => ['internalName' => 'TG2'],
                        ],
                    ],
                ],
            ],
        ];

        $result = BokunService::extractHasAudioGuide($bookingDetails);

        $this->assertTrue($result);
    }

    /**
     * Test extractHasAudioGuide returns false for non-audio guide rate.
     */
    public function test_extract_has_audio_guide_returns_false_for_standard_rate(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'activity' => ['id' => 961802],
                    'pricingCategoryBookings' => [
                        [
                            'rate' => ['id' => 12345], // Not audio guide rate
                        ],
                    ],
                ],
            ],
        ];

        $result = BokunService::extractHasAudioGuide($bookingDetails);

        $this->assertFalse($result);
    }

    /**
     * Test extractHasAudioGuide returns false for non-timed-entry products.
     */
    public function test_extract_has_audio_guide_returns_false_for_guided_tour(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'activity' => ['id' => 961801], // Guided tour, not timed entry
                    'pricingCategoryBookings' => [
                        [
                            'rate' => ['id' => 2263305],
                        ],
                    ],
                ],
            ],
        ];

        // Should return false because only timed entry (961802) can have audio guide
        $result = BokunService::extractHasAudioGuide($bookingDetails);

        $this->assertFalse($result);
    }

    /**
     * Test extractHasAudioGuide uses provided product ID.
     */
    public function test_extract_has_audio_guide_with_provided_product_id(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'rate' => ['id' => 2263305],
                ],
            ],
        ];

        // Pass product ID explicitly
        $result = BokunService::extractHasAudioGuide($bookingDetails, '961802');

        $this->assertTrue($result);
    }

    /**
     * Test extractHasAudioGuide checks productBookings for rate.
     */
    public function test_extract_has_audio_guide_from_product_bookings(): void
    {
        $bookingDetails = [
            'productBookings' => [
                [
                    'productId' => 961802,
                    'rate' => ['id' => 2263305],
                ],
            ],
        ];

        $result = BokunService::extractHasAudioGuide($bookingDetails);

        $this->assertTrue($result);
    }

    /**
     * Test extractHasAudioGuide returns false when no rate info present.
     */
    public function test_extract_has_audio_guide_returns_false_without_rate(): void
    {
        $bookingDetails = [
            'activityBookings' => [
                [
                    'activity' => ['id' => 961802],
                    'pricingCategoryBookings' => [
                        [
                            // No rate info
                        ],
                    ],
                ],
            ],
        ];

        $result = BokunService::extractHasAudioGuide($bookingDetails);

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | extractBookingChannel Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test extractBookingChannel returns channel title.
     */
    public function test_extract_booking_channel_returns_title(): void
    {
        $bookingDetails = [
            'bookingChannel' => [
                'title' => 'GetYourGuide',
                'id' => 123,
            ],
        ];

        $result = BokunService::extractBookingChannel($bookingDetails);

        $this->assertEquals('GetYourGuide', $result);
    }

    /**
     * Test extractBookingChannel returns direct when no channel.
     */
    public function test_extract_booking_channel_returns_direct_when_missing(): void
    {
        $bookingDetails = [
            'customer' => ['name' => 'Test'],
        ];

        $result = BokunService::extractBookingChannel($bookingDetails);

        $this->assertEquals('direct', $result);
    }

    /**
     * Test extractBookingChannel returns direct when channel has no title.
     */
    public function test_extract_booking_channel_returns_direct_when_no_title(): void
    {
        $bookingDetails = [
            'bookingChannel' => [
                'id' => 123,
            ],
        ];

        $result = BokunService::extractBookingChannel($bookingDetails);

        $this->assertEquals('direct', $result);
    }

    /**
     * Test extractBookingChannel with Viator channel.
     */
    public function test_extract_booking_channel_viator(): void
    {
        $bookingDetails = [
            'bookingChannel' => [
                'title' => 'Viator',
            ],
        ];

        $result = BokunService::extractBookingChannel($bookingDetails);

        $this->assertEquals('Viator', $result);
    }

    /*
    |--------------------------------------------------------------------------
    | extractCustomerContact Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test extractCustomerContact returns email and phone.
     */
    public function test_extract_customer_contact_returns_email_and_phone(): void
    {
        $bookingDetails = [
            'customer' => [
                'email' => 'test@example.com',
                'phoneNumber' => '+1234567890',
            ],
        ];

        $result = BokunService::extractCustomerContact($bookingDetails);

        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('+1234567890', $result['phone']);
    }

    /**
     * Test extractCustomerContact uses alternative phone fields.
     */
    public function test_extract_customer_contact_uses_phone_field(): void
    {
        $bookingDetails = [
            'customer' => [
                'email' => 'test@example.com',
                'phone' => '+0987654321',
            ],
        ];

        $result = BokunService::extractCustomerContact($bookingDetails);

        $this->assertEquals('+0987654321', $result['phone']);
    }

    /**
     * Test extractCustomerContact uses mobilePhone field.
     */
    public function test_extract_customer_contact_uses_mobile_phone(): void
    {
        $bookingDetails = [
            'customer' => [
                'mobilePhone' => '+1111111111',
            ],
        ];

        $result = BokunService::extractCustomerContact($bookingDetails);

        $this->assertEquals('+1111111111', $result['phone']);
    }

    /**
     * Test extractCustomerContact returns nulls when no customer.
     */
    public function test_extract_customer_contact_returns_nulls_when_missing(): void
    {
        $bookingDetails = [];

        $result = BokunService::extractCustomerContact($bookingDetails);

        $this->assertNull($result['email']);
        $this->assertNull($result['phone']);
    }

    /**
     * Test extractCustomerContact returns nulls when customer empty.
     */
    public function test_extract_customer_contact_returns_nulls_when_empty(): void
    {
        $bookingDetails = [
            'customer' => [],
        ];

        $result = BokunService::extractCustomerContact($bookingDetails);

        $this->assertNull($result['email']);
        $this->assertNull($result['phone']);
    }
}
