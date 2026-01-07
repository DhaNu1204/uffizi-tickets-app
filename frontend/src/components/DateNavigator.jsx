import { useMemo } from 'react';
import PropTypes from 'prop-types';
import './DateNavigator.css';

// Florence timezone for all date calculations
const FLORENCE_TIMEZONE = 'Europe/Rome';

// Get current date in Florence timezone
const getFlorenceToday = () => {
  const now = new Date();
  // Get Florence date string (YYYY-MM-DD)
  const florenceDate = now.toLocaleDateString('en-CA', { timeZone: FLORENCE_TIMEZONE });
  // Parse it back to a Date object (at midnight local time)
  const [year, month, day] = florenceDate.split('-').map(Number);
  return new Date(year, month - 1, day);
};

// Format date for comparison (YYYY-MM-DD) - uses local date, not UTC
const formatDateKey = (date) => {
  // Use en-CA locale which gives YYYY-MM-DD format
  return date.toLocaleDateString('en-CA');
};

// Check if date is today (Florence timezone)
const isToday = (date) => {
  const florenceToday = getFlorenceToday();
  return formatDateKey(date) === formatDateKey(florenceToday);
};

// Check if date is tomorrow (Florence timezone)
const isTomorrow = (date) => {
  const florenceToday = getFlorenceToday();
  const tomorrow = new Date(florenceToday);
  tomorrow.setDate(tomorrow.getDate() + 1);
  return formatDateKey(date) === formatDateKey(tomorrow);
};

// Get label for selected date
const getDateLabel = (date) => {
  if (isToday(date)) return 'Today';
  if (isTomorrow(date)) return 'Tomorrow';
  return date.toLocaleDateString('en-GB', { weekday: 'long' });
};

// Format date for display
const formatDateDisplay = (date) => {
  return date.toLocaleDateString('en-GB', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
};

const DateNavigator = ({
  selectedDate,
  onDateChange,
  onPrevDay,
  onNextDay,
  onToday,
  datesWithBookings,
  calendarOpen,
  onCalendarToggle,
}) => {
  // Generate calendar days for current month view
  const calendarDays = useMemo(() => {
    const year = selectedDate.getFullYear();
    const month = selectedDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startPadding = firstDay.getDay(); // 0 = Sunday

    const days = [];

    // Previous month padding
    for (let i = startPadding - 1; i >= 0; i--) {
      const d = new Date(year, month, -i);
      days.push({ date: d, isCurrentMonth: false });
    }

    // Current month days
    for (let i = 1; i <= lastDay.getDate(); i++) {
      const d = new Date(year, month, i);
      days.push({ date: d, isCurrentMonth: true });
    }

    // Next month padding (fill to 42 days = 6 rows)
    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
      const d = new Date(year, month + 1, i);
      days.push({ date: d, isCurrentMonth: false });
    }

    return days;
  }, [selectedDate]);

  // Navigate calendar month
  const goToPreviousMonth = () => {
    const newDate = new Date(selectedDate);
    newDate.setMonth(newDate.getMonth() - 1);
    onDateChange(newDate);
  };

  const goToNextMonth = () => {
    const newDate = new Date(selectedDate);
    newDate.setMonth(newDate.getMonth() + 1);
    onDateChange(newDate);
  };

  const handleDateSelect = (date) => {
    onDateChange(date);
    onCalendarToggle(false);
  };

  return (
    <div className="date-navigator">
      <div className="date-nav-controls">
        <button className="nav-btn" onClick={onPrevDay} title="Previous Day">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <polyline points="15 18 9 12 15 6" />
          </svg>
        </button>

        <div className="date-display" onClick={() => onCalendarToggle(!calendarOpen)}>
          <span className={`date-label ${isToday(selectedDate) ? 'today' : ''}`}>
            {getDateLabel(selectedDate)}
          </span>
          <span className="date-full">{formatDateDisplay(selectedDate)}</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="calendar-icon">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
          </svg>
        </div>

        <button className="nav-btn" onClick={onNextDay} title="Next Day">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
      </div>

      {!isToday(selectedDate) && (
        <button className="today-btn" onClick={onToday}>
          Go to Today
        </button>
      )}

      {/* Calendar Dropdown */}
      {calendarOpen && (
        <div className="calendar-dropdown">
          <div className="calendar-header">
            <button onClick={goToPreviousMonth} className="cal-nav-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="15 18 9 12 15 6" />
              </svg>
            </button>
            <span className="cal-month-year">
              {selectedDate.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' })}
            </span>
            <button onClick={goToNextMonth} className="cal-nav-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="9 18 15 12 9 6" />
              </svg>
            </button>
          </div>
          <div className="calendar-weekdays">
            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
              <span key={day} className="weekday">{day}</span>
            ))}
          </div>
          <div className="calendar-days">
            {calendarDays.map(({ date, isCurrentMonth }, idx) => {
              const dateKey = formatDateKey(date);
              const hasBookings = datesWithBookings[dateKey];
              const isSelected = formatDateKey(date) === formatDateKey(selectedDate);
              const isTodayDate = isToday(date);

              return (
                <button
                  key={idx}
                  className={`cal-day ${!isCurrentMonth ? 'other-month' : ''} ${isSelected ? 'selected' : ''} ${isTodayDate ? 'today' : ''} ${hasBookings ? 'has-bookings' : ''}`}
                  onClick={() => handleDateSelect(date)}
                >
                  <span className="day-num">{date.getDate()}</span>
                  {hasBookings && (
                    <span className={`booking-dot ${hasBookings.pending > 0 ? 'pending' : 'complete'}`}>
                      {hasBookings.total}
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
};

DateNavigator.propTypes = {
  selectedDate: PropTypes.instanceOf(Date).isRequired,
  onDateChange: PropTypes.func.isRequired,
  onPrevDay: PropTypes.func.isRequired,
  onNextDay: PropTypes.func.isRequired,
  onToday: PropTypes.func.isRequired,
  datesWithBookings: PropTypes.object,
  calendarOpen: PropTypes.bool.isRequired,
  onCalendarToggle: PropTypes.func.isRequired,
};

DateNavigator.defaultProps = {
  datesWithBookings: {},
};

// Export helper functions for use in parent component
export { getFlorenceToday, formatDateKey, isToday, isTomorrow, getDateLabel, formatDateDisplay };

export default DateNavigator;
