/**
 * Timezone Constants
 * Handles Florence/Rome timezone for correct "today" calculations
 */

/**
 * IANA timezone identifier for Florence, Italy
 * @type {string}
 */
export const FLORENCE_TIMEZONE = 'Europe/Rome';

/**
 * Get the current date in Florence timezone
 * Returns a Date object set to midnight local time for today in Florence
 * @returns {Date} Date object representing today in Florence
 */
export const getFlorenceToday = () => {
  const now = new Date();
  // Get the date string in Florence timezone (YYYY-MM-DD format)
  const florenceDate = now.toLocaleDateString('en-CA', { timeZone: FLORENCE_TIMEZONE });
  const [year, month, day] = florenceDate.split('-').map(Number);
  // Return a Date object at midnight local time
  return new Date(year, month - 1, day);
};

/**
 * Format a date for display using Florence timezone
 * @param {Date|string} date - The date to format
 * @param {Intl.DateTimeFormatOptions} options - Formatting options
 * @returns {string} Formatted date string
 */
export const formatFlorenceDate = (date, options = {}) => {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  return dateObj.toLocaleDateString('en-US', {
    timeZone: FLORENCE_TIMEZONE,
    ...options,
  });
};

/**
 * Format a time for display using Florence timezone
 * @param {Date|string} date - The date/time to format
 * @param {Intl.DateTimeFormatOptions} options - Formatting options
 * @returns {string} Formatted time string
 */
export const formatFlorenceTime = (date, options = {}) => {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  return dateObj.toLocaleTimeString('en-US', {
    timeZone: FLORENCE_TIMEZONE,
    hour: '2-digit',
    minute: '2-digit',
    ...options,
  });
};

/**
 * Get a date string in YYYY-MM-DD format for Florence timezone
 * @param {Date} date - The date to convert
 * @returns {string} Date string in YYYY-MM-DD format
 */
export const getFlorenceDateString = (date) => {
  return date.toLocaleDateString('en-CA', { timeZone: FLORENCE_TIMEZONE });
};
