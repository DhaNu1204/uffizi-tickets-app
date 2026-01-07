<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use PHPUnit\Framework\TestCase;

class BookingStatusEnumTest extends TestCase
{
    /**
     * Test that enum has expected cases.
     */
    public function test_enum_has_expected_cases(): void
    {
        $cases = BookingStatus::cases();

        $this->assertCount(2, $cases);
        $this->assertEquals('PENDING_TICKET', BookingStatus::PENDING_TICKET->value);
        $this->assertEquals('TICKET_PURCHASED', BookingStatus::TICKET_PURCHASED->value);
    }

    /**
     * Test label method returns correct strings.
     */
    public function test_label_returns_correct_strings(): void
    {
        $this->assertEquals('Pending Ticket', BookingStatus::PENDING_TICKET->label());
        $this->assertEquals('Ticket Purchased', BookingStatus::TICKET_PURCHASED->label());
    }

    /**
     * Test color method returns correct colors.
     */
    public function test_color_returns_correct_colors(): void
    {
        $this->assertEquals('yellow', BookingStatus::PENDING_TICKET->color());
        $this->assertEquals('green', BookingStatus::TICKET_PURCHASED->color());
    }

    /**
     * Test cssClass method returns correct class names.
     */
    public function test_css_class_returns_correct_class_names(): void
    {
        $this->assertEquals('status-pending', BookingStatus::PENDING_TICKET->cssClass());
        $this->assertEquals('status-purchased', BookingStatus::TICKET_PURCHASED->cssClass());
    }

    /**
     * Test isPending method.
     */
    public function test_is_pending_method(): void
    {
        $this->assertTrue(BookingStatus::PENDING_TICKET->isPending());
        $this->assertFalse(BookingStatus::TICKET_PURCHASED->isPending());
    }

    /**
     * Test isPurchased method.
     */
    public function test_is_purchased_method(): void
    {
        $this->assertFalse(BookingStatus::PENDING_TICKET->isPurchased());
        $this->assertTrue(BookingStatus::TICKET_PURCHASED->isPurchased());
    }

    /**
     * Test values static method returns all values.
     */
    public function test_values_returns_all_values(): void
    {
        $values = BookingStatus::values();

        $this->assertCount(2, $values);
        $this->assertContains('PENDING_TICKET', $values);
        $this->assertContains('TICKET_PURCHASED', $values);
    }

    /**
     * Test options static method returns value => label array.
     */
    public function test_options_returns_value_label_array(): void
    {
        $options = BookingStatus::options();

        $this->assertCount(2, $options);
        $this->assertArrayHasKey('PENDING_TICKET', $options);
        $this->assertArrayHasKey('TICKET_PURCHASED', $options);
        $this->assertEquals('Pending Ticket', $options['PENDING_TICKET']);
        $this->assertEquals('Ticket Purchased', $options['TICKET_PURCHASED']);
    }

    /**
     * Test fromString with valid values.
     */
    public function test_from_string_with_valid_values(): void
    {
        $this->assertEquals(BookingStatus::PENDING_TICKET, BookingStatus::fromString('PENDING_TICKET'));
        $this->assertEquals(BookingStatus::TICKET_PURCHASED, BookingStatus::fromString('TICKET_PURCHASED'));
    }

    /**
     * Test fromString with null returns PENDING_TICKET.
     */
    public function test_from_string_with_null_returns_pending(): void
    {
        $this->assertEquals(BookingStatus::PENDING_TICKET, BookingStatus::fromString(null));
    }

    /**
     * Test fromString with invalid value returns PENDING_TICKET.
     */
    public function test_from_string_with_invalid_value_returns_pending(): void
    {
        $this->assertEquals(BookingStatus::PENDING_TICKET, BookingStatus::fromString('INVALID_STATUS'));
        $this->assertEquals(BookingStatus::PENDING_TICKET, BookingStatus::fromString(''));
    }
}
