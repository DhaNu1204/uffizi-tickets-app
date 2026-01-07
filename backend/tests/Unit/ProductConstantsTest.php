<?php

namespace Tests\Unit;

use App\Constants\ProductConstants;
use PHPUnit\Framework\TestCase;

class ProductConstantsTest extends TestCase
{
    /**
     * Test TIMED_ENTRY_ID constant.
     */
    public function test_timed_entry_id_constant(): void
    {
        $this->assertEquals('961802', ProductConstants::TIMED_ENTRY_ID);
    }

    /**
     * Test GUIDED_TOUR_IDS constant contains all guided tour IDs.
     */
    public function test_guided_tour_ids_contains_expected_ids(): void
    {
        $expectedIds = ['961801', '962885', '962886', '1130528', '1135055'];

        $this->assertEquals($expectedIds, ProductConstants::GUIDED_TOUR_IDS);
        $this->assertCount(5, ProductConstants::GUIDED_TOUR_IDS);
    }

    /**
     * Test isGuidedTour returns true for guided tour IDs.
     */
    public function test_is_guided_tour_returns_true_for_guided_tours(): void
    {
        foreach (ProductConstants::GUIDED_TOUR_IDS as $id) {
            $this->assertTrue(ProductConstants::isGuidedTour($id), "Failed for ID: $id");
        }
    }

    /**
     * Test isGuidedTour returns false for timed entry ID.
     */
    public function test_is_guided_tour_returns_false_for_timed_entry(): void
    {
        $this->assertFalse(ProductConstants::isGuidedTour(ProductConstants::TIMED_ENTRY_ID));
    }

    /**
     * Test isGuidedTour accepts integer input.
     */
    public function test_is_guided_tour_accepts_integer(): void
    {
        $this->assertTrue(ProductConstants::isGuidedTour(961801));
        $this->assertFalse(ProductConstants::isGuidedTour(961802));
    }

    /**
     * Test isTimedEntry returns true for timed entry ID.
     */
    public function test_is_timed_entry_returns_true_for_timed_entry(): void
    {
        $this->assertTrue(ProductConstants::isTimedEntry('961802'));
        $this->assertTrue(ProductConstants::isTimedEntry(961802));
    }

    /**
     * Test isTimedEntry returns false for guided tour IDs.
     */
    public function test_is_timed_entry_returns_false_for_guided_tours(): void
    {
        foreach (ProductConstants::GUIDED_TOUR_IDS as $id) {
            $this->assertFalse(ProductConstants::isTimedEntry($id), "Failed for ID: $id");
        }
    }

    /**
     * Test getAllProductIds returns all 6 product IDs.
     */
    public function test_get_all_product_ids_returns_all_ids(): void
    {
        $allIds = ProductConstants::getAllProductIds();

        $this->assertCount(6, $allIds);
        $this->assertContains('961802', $allIds);
        $this->assertContains('961801', $allIds);
        $this->assertContains('962885', $allIds);
        $this->assertContains('962886', $allIds);
        $this->assertContains('1130528', $allIds);
        $this->assertContains('1135055', $allIds);
    }

    /**
     * Test getProductName returns correct names.
     */
    public function test_get_product_name_returns_correct_names(): void
    {
        $this->assertEquals('Timed Entry Tickets', ProductConstants::getProductName('961802'));
        $this->assertEquals('Small Group Guided Tour', ProductConstants::getProductName('961801'));
        $this->assertEquals('VIP Private Tour', ProductConstants::getProductName('962886'));
    }

    /**
     * Test getProductName returns null for invalid IDs.
     */
    public function test_get_product_name_returns_null_for_invalid_id(): void
    {
        $this->assertNull(ProductConstants::getProductName('999999'));
        $this->assertNull(ProductConstants::getProductName('invalid'));
    }

    /**
     * Test getShortName returns correct short names.
     */
    public function test_get_short_name_returns_correct_names(): void
    {
        $this->assertEquals('Entry', ProductConstants::getShortName('961802'));
        $this->assertEquals('Group Tour', ProductConstants::getShortName('961801'));
        $this->assertEquals('VIP Tour', ProductConstants::getShortName('962886'));
    }

    /**
     * Test getShortName returns null for invalid IDs.
     */
    public function test_get_short_name_returns_null_for_invalid_id(): void
    {
        $this->assertNull(ProductConstants::getShortName('999999'));
    }

    /**
     * Test isValidProduct returns true for valid IDs.
     */
    public function test_is_valid_product_returns_true_for_valid_ids(): void
    {
        foreach (ProductConstants::getAllProductIds() as $id) {
            $this->assertTrue(ProductConstants::isValidProduct($id), "Failed for ID: $id");
        }
    }

    /**
     * Test isValidProduct returns false for invalid IDs.
     */
    public function test_is_valid_product_returns_false_for_invalid_ids(): void
    {
        $this->assertFalse(ProductConstants::isValidProduct('999999'));
        $this->assertFalse(ProductConstants::isValidProduct('invalid'));
        $this->assertFalse(ProductConstants::isValidProduct(''));
    }

    /**
     * Test getProductIdsString returns comma-separated string.
     */
    public function test_get_product_ids_string_returns_comma_separated(): void
    {
        $result = ProductConstants::getProductIdsString();

        $this->assertIsString($result);
        $this->assertStringContainsString(',', $result);
        $this->assertStringContainsString('961802', $result);
        $this->assertStringContainsString('961801', $result);
    }

    /**
     * Test PRODUCT_NAMES constant has all products.
     */
    public function test_product_names_has_all_products(): void
    {
        $this->assertCount(6, ProductConstants::PRODUCT_NAMES);

        foreach (ProductConstants::getAllProductIds() as $id) {
            $this->assertArrayHasKey($id, ProductConstants::PRODUCT_NAMES);
        }
    }

    /**
     * Test PRODUCT_SHORT_NAMES constant has all products.
     */
    public function test_product_short_names_has_all_products(): void
    {
        $this->assertCount(6, ProductConstants::PRODUCT_SHORT_NAMES);

        foreach (ProductConstants::getAllProductIds() as $id) {
            $this->assertArrayHasKey($id, ProductConstants::PRODUCT_SHORT_NAMES);
        }
    }
}
