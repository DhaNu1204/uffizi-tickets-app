/**
 * Booking Status Constants
 * Defines the possible status values for bookings
 */

/**
 * Booking status values used throughout the application
 * @enum {string}
 */
export const BOOKING_STATUS = {
  /** Booking confirmed, but Uffizi ticket not yet purchased */
  PENDING: 'PENDING_TICKET',
  /** Uffizi ticket has been purchased */
  PURCHASED: 'TICKET_PURCHASED',
};

/**
 * Human-readable labels for booking statuses
 * @type {Object.<string, string>}
 */
export const STATUS_LABELS = {
  [BOOKING_STATUS.PENDING]: 'Pending',
  [BOOKING_STATUS.PURCHASED]: 'Purchased',
};

/**
 * CSS class name suffixes for status styling
 * @type {Object.<string, string>}
 */
export const STATUS_COLORS = {
  [BOOKING_STATUS.PENDING]: 'pending',
  [BOOKING_STATUS.PURCHASED]: 'purchased',
};

/**
 * Check if a booking status indicates ticket is pending
 * @param {string} status - The booking status
 * @returns {boolean} True if status is pending
 */
export const isPending = (status) => status === BOOKING_STATUS.PENDING;

/**
 * Check if a booking status indicates ticket is purchased
 * @param {string} status - The booking status
 * @returns {boolean} True if status is purchased
 */
export const isPurchased = (status) => status === BOOKING_STATUS.PURCHASED;
