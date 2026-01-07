import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useToast } from '../context/ToastContext';
import { webhooksAPI } from '../services/api';
import './WebhookLogs.css';

const WebhookLogs = () => {
  const navigate = useNavigate();
  const toast = useToast();
  const [logs, setLogs] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedLog, setSelectedLog] = useState(null);
  const [retrying, setRetrying] = useState(null);

  const [filters, setFilters] = useState({
    status: '',
    event_type: '',
    per_page: 20,
    page: 1,
  });

  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0,
  });

  const fetchLogs = useCallback(async () => {
    setLoading(true);
    try {
      const params = Object.fromEntries(
        Object.entries(filters).filter(([_, v]) => v !== '')
      );
      const response = await webhooksAPI.list(params);
      setLogs(response.data.data || []);
      setPagination({
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      });
      setError(null);
    } catch (err) {
      console.error('Error fetching webhook logs:', err);
      setError('Failed to load webhook logs');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  const fetchStats = useCallback(async () => {
    try {
      const response = await webhooksAPI.stats();
      setStats(response.data);
    } catch (err) {
      console.error('Error fetching stats:', err);
    }
  }, []);

  useEffect(() => {
    fetchLogs();
  }, [fetchLogs]);

  useEffect(() => {
    fetchStats();
  }, [fetchStats]);

  const handleRetry = async (id) => {
    setRetrying(id);
    try {
      await webhooksAPI.retry(id);
      toast.success('Webhook retried successfully');
      fetchLogs();
      fetchStats();
    } catch (err) {
      console.error('Retry failed:', err);
      toast.error('Failed to retry webhook');
    } finally {
      setRetrying(null);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value,
      page: key !== 'page' ? 1 : value,
    }));
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-GB', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusClass = (status) => {
    switch (status) {
      case 'processed': return 'success';
      case 'failed': return 'error';
      case 'pending': return 'warning';
      default: return 'info';
    }
  };

  return (
    <div className="webhook-logs">
      {/* Header */}
      <header className="logs-header">
        <div className="header-left">
          <button className="back-btn" onClick={() => navigate('/')}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="15 18 9 12 15 6" />
            </svg>
          </button>
          <h1>Webhook Logs</h1>
        </div>
        <button onClick={fetchLogs} className="btn btn-secondary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <polyline points="1 4 1 10 7 10" />
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
          </svg>
          Refresh
        </button>
      </header>

      <main className="logs-main">
        {error && (
          <div className="error-banner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            {error}
            <button onClick={fetchLogs}>Retry</button>
          </div>
        )}

        {/* Stats */}
        {stats && (
          <div className="logs-stats">
            <div className="stat-item">
              <span className="stat-value">{stats.total || 0}</span>
              <span className="stat-label">Total</span>
            </div>
            <div className="stat-item success">
              <span className="stat-value">{stats.processed || 0}</span>
              <span className="stat-label">Processed</span>
            </div>
            <div className="stat-item warning">
              <span className="stat-value">{stats.pending || 0}</span>
              <span className="stat-label">Pending</span>
            </div>
            <div className="stat-item error">
              <span className="stat-value">{stats.failed || 0}</span>
              <span className="stat-label">Failed</span>
            </div>
          </div>
        )}

        {/* Filters */}
        <div className="logs-filters">
          <select
            value={filters.status}
            onChange={(e) => handleFilterChange('status', e.target.value)}
            className="filter-select"
          >
            <option value="">All Status</option>
            <option value="processed">Processed</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
          <select
            value={filters.event_type}
            onChange={(e) => handleFilterChange('event_type', e.target.value)}
            className="filter-select"
          >
            <option value="">All Events</option>
            <option value="BOOKING_CONFIRMED">Booking Confirmed</option>
            <option value="BOOKING_CANCELLED">Booking Cancelled</option>
            <option value="BOOKING_MODIFIED">Booking Modified</option>
          </select>
        </div>

        {/* Logs List */}
        <div className="logs-section">
          {loading ? (
            <div className="logs-loading">
              <div className="loading-spinner"></div>
              <p>Loading logs...</p>
            </div>
          ) : logs.length === 0 ? (
            <div className="logs-empty">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="12" y1="18" x2="12" y2="12" />
                <line x1="9" y1="15" x2="15" y2="15" />
              </svg>
              <p>No webhook logs found</p>
            </div>
          ) : (
            <div className="logs-list">
              {logs.map((log) => (
                <div
                  key={log.id}
                  className={`log-item ${getStatusClass(log.status)}`}
                  onClick={() => setSelectedLog(log)}
                >
                  <div className="log-main">
                    <div className="log-event">
                      <span className={`status-dot ${getStatusClass(log.status)}`}></span>
                      <span className="event-type">{log.event_type || 'Unknown'}</span>
                    </div>
                    <div className="log-meta">
                      <span className="log-id">#{log.id}</span>
                      <span className="log-date">{formatDate(log.created_at)}</span>
                    </div>
                  </div>
                  <div className="log-actions">
                    <span className={`status-badge ${getStatusClass(log.status)}`}>
                      {log.status}
                    </span>
                    {log.status === 'failed' && (
                      <button
                        className="retry-btn"
                        onClick={(e) => {
                          e.stopPropagation();
                          handleRetry(log.id);
                        }}
                        disabled={retrying === log.id}
                      >
                        {retrying === log.id ? (
                          <span className="spinner-small"></span>
                        ) : (
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                          </svg>
                        )}
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <div className="logs-pagination">
              <button
                onClick={() => handleFilterChange('page', pagination.current_page - 1)}
                disabled={pagination.current_page === 1}
                className="pagination-btn"
              >
                Previous
              </button>
              <span className="pagination-info">
                Page {pagination.current_page} of {pagination.last_page}
              </span>
              <button
                onClick={() => handleFilterChange('page', pagination.current_page + 1)}
                disabled={pagination.current_page === pagination.last_page}
                className="pagination-btn"
              >
                Next
              </button>
            </div>
          )}
        </div>
      </main>

      {/* Detail Modal */}
      {selectedLog && (
        <div className="modal-overlay" onClick={() => setSelectedLog(null)}>
          <div className="modal log-modal" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={() => setSelectedLog(null)}>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>

            <div className="modal-header">
              <h3>Webhook Log #{selectedLog.id}</h3>
              <span className={`status-badge ${getStatusClass(selectedLog.status)}`}>
                {selectedLog.status}
              </span>
            </div>

            <div className="modal-body">
              <div className="log-detail-row">
                <span className="label">Event Type</span>
                <span className="value">{selectedLog.event_type || 'Unknown'}</span>
              </div>
              <div className="log-detail-row">
                <span className="label">Received</span>
                <span className="value">{new Date(selectedLog.created_at).toLocaleString()}</span>
              </div>
              {selectedLog.processed_at && (
                <div className="log-detail-row">
                  <span className="label">Processed</span>
                  <span className="value">{new Date(selectedLog.processed_at).toLocaleString()}</span>
                </div>
              )}
              {selectedLog.error_message && (
                <div className="log-detail-row error">
                  <span className="label">Error</span>
                  <span className="value">{selectedLog.error_message}</span>
                </div>
              )}
              <div className="log-payload">
                <span className="label">Payload</span>
                <pre>{JSON.stringify(selectedLog.payload, null, 2)}</pre>
              </div>
            </div>

            {selectedLog.status === 'failed' && (
              <div className="modal-actions">
                <button
                  className="btn btn-primary"
                  onClick={() => {
                    handleRetry(selectedLog.id);
                    setSelectedLog(null);
                  }}
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="23 4 23 10 17 10" />
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                  </svg>
                  Retry Webhook
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default WebhookLogs;
