/**
 * Guided Tour Constants
 * Defines product IDs for different tour types
 */

/**
 * Product IDs for guided tours (require guide assignment)
 * @type {string[]}
 */
export const GUIDED_TOUR_IDS = [
  '961801',   // Small Group Guided Tour
  '962885',   // Uffizi, David Tour & Gelato with Art Historian
  '962886',   // VIP Private Tour
  '1130528',  // Guided Tour + Vasari
  '1135055',  // Florence Uffizi Gallery Tour with Palazzo Vecchio Entry
];

/**
 * Product ID for timed entry tickets (no guide needed)
 * @type {string}
 */
export const TIMED_ENTRY_ID = '961802';

/**
 * Check if a product is a guided tour (requires guide assignment)
 * @param {string|number} productId - The Bokun product ID
 * @returns {boolean} True if the product is a guided tour
 */
export const isGuidedTour = (productId) => GUIDED_TOUR_IDS.includes(String(productId));

/**
 * Check if a product is any type of tour (not timed entry)
 * @param {string|number} productId - The Bokun product ID
 * @returns {boolean} True if the product is a tour (guided or otherwise)
 */
export const isTour = (productId) => String(productId) !== TIMED_ENTRY_ID;

/**
 * Check if a product is timed entry tickets
 * @param {string|number} productId - The Bokun product ID
 * @returns {boolean} True if the product is timed entry
 */
export const isTimedEntry = (productId) => String(productId) === TIMED_ENTRY_ID;
