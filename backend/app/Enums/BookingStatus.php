<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Booking status enum for the Uffizi Ticket App.
 *
 * Represents the possible states of a booking's ticket purchase status.
 */
enum BookingStatus: string
{
    /**
     * Ticket has not yet been purchased from Uffizi.
     */
    case PENDING_TICKET = 'PENDING_TICKET';

    /**
     * Ticket has been purchased and reference number assigned.
     */
    case TICKET_PURCHASED = 'TICKET_PURCHASED';

    /**
     * Get a human-readable label for the status.
     *
     * @return string The display label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING_TICKET => 'Pending Ticket',
            self::TICKET_PURCHASED => 'Ticket Purchased',
        };
    }

    /**
     * Get the UI color associated with the status.
     *
     * @return string The color name/code for UI display
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING_TICKET => 'yellow',
            self::TICKET_PURCHASED => 'green',
        };
    }

    /**
     * Get the CSS class for styling this status.
     *
     * @return string The CSS class name
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::PENDING_TICKET => 'status-pending',
            self::TICKET_PURCHASED => 'status-purchased',
        };
    }

    /**
     * Check if this status indicates the ticket is pending.
     *
     * @return bool True if ticket purchase is pending
     */
    public function isPending(): bool
    {
        return $this === self::PENDING_TICKET;
    }

    /**
     * Check if this status indicates the ticket has been purchased.
     *
     * @return bool True if ticket has been purchased
     */
    public function isPurchased(): bool
    {
        return $this === self::TICKET_PURCHASED;
    }

    /**
     * Get all status values as an array.
     *
     * @return array<string> Array of all status string values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all statuses as an associative array (value => label).
     *
     * @return array<string, string> Associative array of value => label pairs
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * Create a BookingStatus from a string value, with fallback to PENDING_TICKET.
     *
     * @param string|null $value The status string value
     * @return self The corresponding BookingStatus enum
     */
    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return self::PENDING_TICKET;
        }

        return self::tryFrom($value) ?? self::PENDING_TICKET;
    }
}
