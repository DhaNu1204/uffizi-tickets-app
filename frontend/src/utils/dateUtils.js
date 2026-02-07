/**
 * Format a date for display
 * @param {Date|string} date
 * @returns {string} Formatted date like "January 25, 2026"
 */
export function formatDate(date) {
  if (!date) return '';
  const d = date instanceof Date ? date : new Date(date);
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    timeZone: 'UTC',
  });
}

/**
 * Format a time for display
 * @param {Date|string} date
 * @returns {string} Formatted time like "10:30 AM"
 */
export function formatTime(date) {
  if (!date) return '';
  const d = date instanceof Date ? date : new Date(date);
  return d.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
    timeZone: 'UTC',
  });
}

/**
 * Format a date for API requests (YYYY-MM-DD)
 * @param {Date|string} date
 * @returns {string} Date in YYYY-MM-DD format
 */
export function formatDateKey(date) {
  if (!date) return '';
  const d = date instanceof Date ? date : new Date(date);
  return d.toISOString().split('T')[0];
}

/**
 * Format a date and time together
 * @param {Date|string} date
 * @returns {string} Formatted date and time like "January 25, 2026 at 10:30 AM"
 */
export function formatDateTime(date) {
  if (!date) return '';
  return `${formatDate(date)} at ${formatTime(date)}`;
}

/**
 * Get relative date description (Today, Tomorrow, etc.)
 * @param {Date|string} date
 * @param {string} timezone - Timezone to use for comparison
 * @returns {string} Relative description or formatted date
 */
export function getRelativeDate(date, timezone = 'Europe/Rome') {
  if (!date) return '';

  const d = date instanceof Date ? date : new Date(date);
  const now = new Date();

  // Get dates in the specified timezone
  const dateStr = d.toLocaleDateString('en-CA', { timeZone: timezone });
  const todayStr = now.toLocaleDateString('en-CA', { timeZone: timezone });

  const tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toLocaleDateString('en-CA', { timeZone: timezone });

  if (dateStr === todayStr) {
    return 'Today';
  } else if (dateStr === tomorrowStr) {
    return 'Tomorrow';
  }

  return formatDate(d);
}
