<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Product constants for the Uffizi Ticket App.
 *
 * Centralizes all Bokun product IDs and related product configuration.
 * These IDs correspond to the different tour/ticket types available.
 */
final class ProductConstants
{
    /**
     * Product ID for Timed Entry Tickets (non-guided).
     */
    public const TIMED_ENTRY_ID = '961802';

    /**
     * Product IDs for all guided tour products.
     *
     * - 961801: Small Group Guided Tour
     * - 962885: Uffizi, David Tour & Gelato with Art Historian
     * - 962886: VIP Private Tour
     * - 1130528: Guided Tour + Vasari
     * - 1135055: Florence Uffizi Gallery Tour with Palazzo Vecchio Entry
     */
    public const GUIDED_TOUR_IDS = [
        '961801',   // Small Group Guided Tour
        '962885',   // Uffizi, David Tour & Gelato with Art Historian
        '962886',   // VIP Private Tour
        '1130528',  // Guided Tour + Vasari
        '1135055',  // Florence Uffizi Gallery Tour with Palazzo Vecchio Entry
    ];

    /**
     * Human-readable names for each product ID.
     */
    public const PRODUCT_NAMES = [
        '961802' => 'Timed Entry Tickets',
        '961801' => 'Small Group Guided Tour',
        '962885' => 'Uffizi, David Tour & Gelato with Art Historian',
        '962886' => 'VIP Private Tour',
        '1130528' => 'Guided Tour + Vasari',
        '1135055' => 'Florence Uffizi Gallery Tour with Palazzo Vecchio Entry',
    ];

    /**
     * Short names for each product (for compact display).
     */
    public const PRODUCT_SHORT_NAMES = [
        '961802' => 'Entry',
        '961801' => 'Group Tour',
        '962885' => 'David Tour',
        '962886' => 'VIP Tour',
        '1130528' => 'Vasari Tour',
        '1135055' => 'Palazzo Tour',
    ];

    /**
     * Check if a product ID is a guided tour.
     *
     * @param string|int $productId The product ID to check
     * @return bool True if the product is a guided tour
     */
    public static function isGuidedTour(string|int $productId): bool
    {
        return in_array((string) $productId, self::GUIDED_TOUR_IDS, true);
    }

    /**
     * Check if a product ID is a timed entry ticket.
     *
     * @param string|int $productId The product ID to check
     * @return bool True if the product is a timed entry ticket
     */
    public static function isTimedEntry(string|int $productId): bool
    {
        return (string) $productId === self::TIMED_ENTRY_ID;
    }

    /**
     * Get all product IDs (both guided tours and timed entry).
     *
     * @return array<string> Array of all product ID strings
     */
    public static function getAllProductIds(): array
    {
        return array_merge([self::TIMED_ENTRY_ID], self::GUIDED_TOUR_IDS);
    }

    /**
     * Get the human-readable name for a product ID.
     *
     * @param string|int $productId The product ID
     * @return string|null The product name, or null if not found
     */
    public static function getProductName(string|int $productId): ?string
    {
        return self::PRODUCT_NAMES[(string) $productId] ?? null;
    }

    /**
     * Get the short name for a product ID.
     *
     * @param string|int $productId The product ID
     * @return string|null The short name, or null if not found
     */
    public static function getShortName(string|int $productId): ?string
    {
        return self::PRODUCT_SHORT_NAMES[(string) $productId] ?? null;
    }

    /**
     * Check if a product ID is valid (exists in our system).
     *
     * @param string|int $productId The product ID to validate
     * @return bool True if the product ID is valid
     */
    public static function isValidProduct(string|int $productId): bool
    {
        return array_key_exists((string) $productId, self::PRODUCT_NAMES);
    }

    /**
     * Get product IDs as a comma-separated string (for ENV/config).
     *
     * @return string Comma-separated product IDs
     */
    public static function getProductIdsString(): string
    {
        return implode(',', self::getAllProductIds());
    }
}
