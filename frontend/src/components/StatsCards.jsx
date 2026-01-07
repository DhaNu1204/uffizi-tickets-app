import PropTypes from 'prop-types';
import './StatsCards.css';

const StatsCards = ({ stats }) => {
  const summary = stats?.summary || {};

  return (
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
  );
};

StatsCards.propTypes = {
  stats: PropTypes.shape({
    summary: PropTypes.shape({
      pending_tickets: PropTypes.number,
      purchased_tickets: PropTypes.number,
      upcoming_pending_7_days: PropTypes.number,
      total_bookings: PropTypes.number,
    }),
  }),
};

StatsCards.defaultProps = {
  stats: null,
};

export default StatsCards;
