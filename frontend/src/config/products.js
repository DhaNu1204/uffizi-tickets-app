/**
 * Uffizi Product Configuration
 * Centralized product definitions for the dashboard.
 * Update this file when adding new products or changing product details.
 */

export const PRODUCT_TYPES = [
  { id: '961802', name: 'Timed Entry Tickets', short: 'Tickets' },
  { id: '961801', name: 'Small Group Guided Tour', short: 'Group Tour' },
  { id: '962885', name: 'Uffizi, David Tour & Gelato with Art Historian', short: 'Gelato Tour' },
  { id: '962886', name: 'VIP Private Tour', short: 'VIP Tour' },
  { id: '1130528', name: 'Guided Tour + Vasari', short: 'Vasari Tour' },
  { id: '1135055', name: 'Florence Uffizi Gallery Tour with Palazzo Vecchio Entry', short: 'Palazzo Vecchio' },
];

/**
 * Get product by ID
 * @param {string} productId - The Bokun product ID
 * @returns {object|undefined} The product object or undefined if not found
 */
export const getProductById = (productId) => {
  return PRODUCT_TYPES.find(p => p.id === productId);
};

/**
 * Get short name for a product
 * @param {string} productId - The Bokun product ID
 * @returns {string} The short name or 'Unknown' if not found
 */
export const getProductShortName = (productId) => {
  const product = getProductById(productId);
  return product ? product.short : 'Unknown';
};

/**
 * Get all product IDs as an array
 * @returns {string[]} Array of product IDs
 */
export const getProductIds = () => {
  return PRODUCT_TYPES.map(p => p.id);
};

export default PRODUCT_TYPES;
