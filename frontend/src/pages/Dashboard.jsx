import { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useToast } from '../context/ToastContext';
import { bookingsAPI } from '../services/api';
import BookingTable from '../components/BookingTable';
import { PRODUCT_TYPES } from '../config/products';
import './Dashboard.css';

const Dashboard = () => {
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const toast = useToast();
  const [groupedBookings, setGroupedBookings] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [error, setError] = useState(null);
  const [menuOpen, setMenuOpen] = useState(false);
  const [calendarOpen, setCalendarOpen] = useState(false);

  // Swipe gesture state
  const touchStartX = useRef(null);
  const touchEndX = useRef(null);
  const swipeContainerRef = useRef(null);

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

  // Selected date for daily view (starts at Florence's today)
  const [selectedDate, setSelectedDate] = useState(() => {
    return getFlorenceToday();
  });

  // Filters
  const [filters, setFilters] = useState({
    status: '',
    search: '',
    product_id: '',
  });

  // Product types for filter - imported from config
  const productTypes = PRODUCT_TYPES;

  // Format date for display
  const formatDateDisplay = (date) => {
    return date.toLocaleDateString('en-GB', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
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

  // Navigate to previous day
  const goToPreviousDay = () => {
    const newDate = new Date(selectedDate);
    newDate.setDate(newDate.getDate() - 1);
    setSelectedDate(newDate);
  };

  // Navigate to next day
  const goToNextDay = () => {
    const newDate = new Date(selectedDate);
    newDate.setDate(newDate.getDate() + 1);
    setSelectedDate(newDate);
  };

  // Go to today (Florence timezone)
  const goToToday = () => {
    setSelectedDate(getFlorenceToday());
  };

  // Swipe gesture handlers for mobile day navigation
  const handleTouchStart = (e) => {
    touchStartX.current = e.touches[0].clientX;
  };

  const handleTouchMove = (e) => {
    touchEndX.current = e.touches[0].clientX;
  };

  const handleTouchEnd = () => {
    if (!touchStartX.current || !touchEndX.current) return;

    const diff = touchStartX.current - touchEndX.current;
    const minSwipeDistance = 50; // Minimum swipe distance in pixels

    if (Math.abs(diff) > minSwipeDistance) {
      if (diff > 0) {
        // Swipe left -> next day
        goToNextDay();
      } else {
        // Swipe right -> previous day
        goToPreviousDay();
      }
    }

    // Reset
    touchStartX.current = null;
    touchEndX.current = null;
  };

  // Filter bookings for selected date
  const selectedDayBookings = useMemo(() => {
    const selectedKey = formatDateKey(selectedDate);
    const dayGroup = groupedBookings.find(g => g.date === selectedKey);
    return dayGroup || null;
  }, [groupedBookings, selectedDate]);

  // Get dates that have bookings (for calendar highlighting)
  const datesWithBookings = useMemo(() => {
    const dates = {};
    groupedBookings.forEach(g => {
      dates[g.date] = {
        total: g.total_bookings,
        pending: g.pending_count,
      };
    });
    return dates;
  }, [groupedBookings]);

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
    setSelectedDate(newDate);
  };

  const goToNextMonth = () => {
    const newDate = new Date(selectedDate);
    newDate.setMonth(newDate.getMonth() + 1);
    setSelectedDate(newDate);
  };

  const fetchBookings = useCallback(async () => {
    setLoading(true);
    try {
      const params = Object.fromEntries(
        Object.entries(filters).filter(([_, v]) => v !== '')
      );
      const response = await bookingsAPI.grouped(params);
      setGroupedBookings(response.data.grouped_bookings || []);
      setError(null);
    } catch (err) {
      console.error('Error fetching bookings:', err);
      setError('Failed to load bookings');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  const fetchStats = useCallback(async () => {
    try {
      const response = await bookingsAPI.stats();
      setStats(response.data);
    } catch (err) {
      console.error('Error fetching stats:', err);
    }
  }, []);

  // Auto-update to new day at midnight Florence time
  useEffect(() => {
    const checkDateChange = () => {
      const florenceToday = getFlorenceToday();
      const florenceTodayKey = formatDateKey(florenceToday);
      const selectedKey = formatDateKey(selectedDate);

      // If the selected date is the previous "today", update to new Florence today
      if (isToday(selectedDate) === false && selectedKey < florenceTodayKey) {
        // Check if user was viewing "today" (which is now yesterday)
        const yesterday = new Date(florenceToday);
        yesterday.setDate(yesterday.getDate() - 1);
        if (formatDateKey(selectedDate) === formatDateKey(yesterday)) {
          // User was viewing "today", auto-advance to new today
          setSelectedDate(florenceToday);
        }
      }
    };

    // Check every minute for date change
    const interval = setInterval(checkDateChange, 60000);
    return () => clearInterval(interval);
  }, [selectedDate]);

  // Auto-sync on dashboard load
  useEffect(() => {
    const syncOnLoad = async () => {
      try {
        const response = await bookingsAPI.autoSync();
        const { pending_participants, sync_triggered } = response.data;

        if (sync_triggered) {
          console.log(`Auto-sync completed. ${pending_participants} bookings still need participant data.`);
        }

        fetchBookings();
        fetchStats();
      } catch (err) {
        console.error('Auto-sync error:', err);
        fetchBookings();
        fetchStats();
      }
    };

    syncOnLoad();
  }, []);

  useEffect(() => {
    fetchBookings();
  }, [fetchBookings]);

  useEffect(() => {
    fetchStats();
    const interval = setInterval(fetchStats, 60000);
    return () => clearInterval(interval);
  }, [fetchStats]);

  const handleSync = async () => {
    setSyncing(true);
    try {
      const response = await bookingsAPI.sync();
      toast.success(response.data.message || 'Sync complete');
      fetchBookings();
      fetchStats();
    } catch (err) {
      console.error('Sync error:', err);
      toast.error('Sync failed: ' + (err.response?.data?.message || err.message));
    } finally {
      setSyncing(false);
    }
  };

  const handleUpdateBooking = async (id, payload) => {
    try {
      await bookingsAPI.update(id, payload);
      toast.success('Booking updated successfully');
      fetchBookings();
      fetchStats();
    } catch (err) {
      console.error('Update error:', err);
      toast.error('Failed to update booking');
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value,
    }));
  };

  const handleLogout = async () => {
    await logout();
  };

  const summary = stats?.summary || {};

  return (
    <div className="dashboard">
      {/* Header */}
      <header className="dashboard-header">
        <div className="header-left">
          <button
            className="menu-toggle"
            onClick={() => setMenuOpen(!menuOpen)}
            aria-label="Toggle menu"
          >
            <span></span>
            <span></span>
            <span></span>
          </button>
          <h1>Uffizi Operations</h1>
        </div>
        <div className="header-right">
          <div className="status-indicator">
            <span className="dot online"></span>
            <span className="status-text">Live</span>
          </div>
          <div className={`user-menu ${menuOpen ? 'open' : ''}`}>
            <button className="user-button" onClick={() => setMenuOpen(!menuOpen)}>
              <span className="user-avatar">{user?.name?.charAt(0) || 'U'}</span>
              <span className="user-name">{user?.name}</span>
            </button>
            {menuOpen && (
              <div className="dropdown-menu">
                <div className="dropdown-header">
                  <strong>{user?.name}</strong>
                  <span>{user?.email}</span>
                </div>
                <button onClick={() => { setMenuOpen(false); navigate('/webhooks'); }} className="dropdown-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                  </svg>
                  Webhook Logs
                </button>
                <button onClick={handleLogout} className="dropdown-item logout">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                  </svg>
                  Sign Out
                </button>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="dashboard-main">
        {error && (
          <div className="error-banner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            {error}
            <button onClick={fetchBookings}>Retry</button>
          </div>
        )}

        {/* Stats Cards */}
        <div className="stats-grid">
          <div className="stat-card urgent">
            <div className="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
              </svg>
            </div>
            <div className="stat-content">
              <h3>Tickets Needed</h3>
              <div className="stat-value">{summary.pending_tickets || 0}</div>
              <p className="stat-desc">Action Required</p>
            </div>
          </div>

          <div className="stat-card success">
            <div className="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
              </svg>
            </div>
            <div className="stat-content">
              <h3>Tickets Ready</h3>
              <div className="stat-value">{summary.purchased_tickets || 0}</div>
              <p className="stat-desc">All Set</p>
            </div>
          </div>

          <div className="stat-card warning">
            <div className="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
            </div>
            <div className="stat-content">
              <h3>Next 7 Days</h3>
              <div className="stat-value">{summary.upcoming_pending_7_days || 0}</div>
              <p className="stat-desc">Pending Tours</p>
            </div>
          </div>

          <div className="stat-card info">
            <div className="stat-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
            </div>
            <div className="stat-content">
              <h3>Total Bookings</h3>
              <div className="stat-value">{summary.total_bookings || 0}</div>
              <p className="stat-desc">In System</p>
            </div>
          </div>
        </div>

        {/* Bookings Section */}
        <section className="bookings-section">
          <div className="section-header">
            <h2>Daily Bookings</h2>
            <div className="header-actions">
              <button
                onClick={handleSync}
                className="btn btn-primary"
                disabled={syncing}
              >
                {syncing ? (
                  <>
                    <span className="spinner"></span>
                    Syncing...
                  </>
                ) : (
                  <>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="23 4 23 10 17 10" />
                      <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                    </svg>
                    Sync Bokun
                  </>
                )}
              </button>
            </div>
          </div>

          {/* Date Navigator */}
          <div className="date-navigator">
            <div className="date-nav-controls">
              <button className="nav-btn" onClick={goToPreviousDay} title="Previous Day">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="15 18 9 12 15 6" />
                </svg>
              </button>

              <div className="date-display" onClick={() => setCalendarOpen(!calendarOpen)}>
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

              <button className="nav-btn" onClick={goToNextDay} title="Next Day">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </button>
            </div>

            {!isToday(selectedDate) && (
              <button className="today-btn" onClick={goToToday}>
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
                        onClick={() => {
                          setSelectedDate(date);
                          setCalendarOpen(false);
                        }}
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

          {/* Day Summary Stats */}
          {selectedDayBookings && (
            <div className="day-summary">
              <div className="summary-stat">
                <strong>{selectedDayBookings.total_bookings}</strong>
                <span>Bookings</span>
              </div>
              <div className="summary-stat">
                <strong>{selectedDayBookings.total_pax}</strong>
                <span>Guests</span>
              </div>
              <div className={`summary-stat ${selectedDayBookings.pending_count > 0 ? 'pending' : 'complete'}`}>
                <strong>{selectedDayBookings.pending_count}</strong>
                <span>Pending</span>
              </div>
              <div className="summary-stat complete">
                <strong>{selectedDayBookings.total_bookings - selectedDayBookings.pending_count}</strong>
                <span>Complete</span>
              </div>
            </div>
          )}

          {/* Filters */}
          <div className="filters-bar">
            <div className="filter-group search-group">
              <div className="search-input-wrapper">
                <svg className="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="11" cy="11" r="8" />
                  <path d="M21 21l-4.35-4.35" />
                </svg>
                <input
                  type="text"
                  placeholder="Search name, booking ID, reference..."
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="filter-input search-input"
                />
                {filters.search && (
                  <button
                    className="search-clear"
                    onClick={() => handleFilterChange('search', '')}
                    type="button"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <line x1="18" y1="6" x2="6" y2="18" />
                      <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                  </button>
                )}
              </div>
            </div>
            <div className="filter-group">
              <select
                value={filters.product_id}
                onChange={(e) => handleFilterChange('product_id', e.target.value)}
                className="filter-select"
              >
                <option value="">All Products</option>
                {productTypes.map(p => (
                  <option key={p.id} value={p.id}>{p.name}</option>
                ))}
              </select>
            </div>
            <div className="filter-group">
              <select
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                className="filter-select"
              >
                <option value="">All Status</option>
                <option value="PENDING_TICKET">Pending Ticket</option>
                <option value="TICKET_PURCHASED">Ticket Purchased</option>
              </select>
            </div>
          </div>

          {/* Swipeable Content Area */}
          <div
            className="swipe-container"
            ref={swipeContainerRef}
            onTouchStart={handleTouchStart}
            onTouchMove={handleTouchMove}
            onTouchEnd={handleTouchEnd}
          >
            {/* Loading State */}
            {loading && (
              <div className="loading-state">
                <span className="spinner large"></span>
                <p>Loading bookings...</p>
              </div>
            )}

            {/* No Bookings for Selected Day */}
            {!loading && !selectedDayBookings && (
              <div className="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                  <line x1="16" y1="2" x2="16" y2="6" />
                  <line x1="8" y1="2" x2="8" y2="6" />
                  <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
                <h3>No bookings for {getDateLabel(selectedDate).toLowerCase()}</h3>
                <p>{formatDateDisplay(selectedDate)}</p>
                <span className="swipe-hint">Swipe left/right to change day</span>
              </div>
            )}

            {/* Bookings for Selected Day */}
            {!loading && selectedDayBookings && (
              <div className={`day-group ${isToday(selectedDate) ? 'today' : ''}`}>
                <BookingTable
                  bookings={selectedDayBookings.bookings}
                  onUpdate={handleUpdateBooking}
                  loading={false}
                  compact={true}
                  productTypes={productTypes}
                />
              </div>
            )}
          </div>
        </section>
      </main>

      {/* Calendar Overlay */}
      {calendarOpen && (
        <div className="calendar-overlay" onClick={() => setCalendarOpen(false)} />
      )}
    </div>
  );
};

export default Dashboard;
