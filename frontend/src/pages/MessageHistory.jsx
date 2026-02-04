import { useState, useEffect, useCallback, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useToast } from '../context/ToastContext';
import api, { messagesAPI } from '../services/api';
import './MessageHistory.css';

export default function MessageHistory() {
  const navigate = useNavigate();
  const { error: showError, success: showSuccess } = useToast();
  const [messages, setMessages] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [lastRefresh, setLastRefresh] = useState(new Date());
  const [expandedError, setExpandedError] = useState(null);
  const [retryingIds, setRetryingIds] = useState(new Set());
  const refreshIntervalRef = useRef(null);

  const [filters, setFilters] = useState({
    status: '',
    channel: '',
    search: '',
  });
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 25,
  });

  const fetchMessages = useCallback(async (page = 1, silent = false) => {
    if (!silent) setLoading(true);
    try {
      const params = { ...filters, page, per_page: pagination.per_page };
      Object.keys(params).forEach(key => {
        if (!params[key]) delete params[key];
      });
      const response = await api.get('/messages/all', { params });
      setMessages(response.data.data || []);
      setPagination(prev => ({
        ...prev,
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      }));
      setLastRefresh(new Date());
    } catch (err) {
      if (!silent) showError('Failed to load messages');
      console.error(err);
    } finally {
      if (!silent) setLoading(false);
    }
  }, [filters, pagination.per_page, showError]);

  const fetchStats = useCallback(async () => {
    try {
      const response = await api.get('/messages/stats');
      setStats(response.data);
    } catch (err) {
      console.error('Failed to load stats:', err);
    }
  }, []);

  // Initial load
  useEffect(() => {
    fetchMessages();
    fetchStats();
  }, []);

  // Auto-refresh every 10 seconds
  useEffect(() => {
    if (autoRefresh) {
      refreshIntervalRef.current = setInterval(() => {
        fetchMessages(pagination.current_page, true);
        fetchStats();
      }, 10000);
    }
    return () => {
      if (refreshIntervalRef.current) {
        clearInterval(refreshIntervalRef.current);
      }
    };
  }, [autoRefresh, pagination.current_page, fetchMessages, fetchStats]);

  const getStatusBadge = (status) => {
    const badges = {
      pending: 'badge-warning',
      queued: 'badge-info',
      sent: 'badge-primary',
      delivered: 'badge-success',
      read: 'badge-success',
      failed: 'badge-danger',
      undelivered: 'badge-danger',
    };
    return badges[status] || 'badge-secondary';
  };

  const getChannelIcon = (channel) => {
    const icons = {
      whatsapp: '💬',
      email: '✉️',
      sms: '📱',
    };
    return icons[channel] || '📨';
  };

  const getChannelLabel = (channel) => {
    const labels = {
      whatsapp: 'WhatsApp',
      email: 'Email',
      sms: 'SMS',
    };
    return labels[channel] || channel;
  };

  const formatDateGroup = (dateString) => {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) return 'Today';
    if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';

    return date.toLocaleDateString('en-GB', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    });
  };

  // Group messages by date
  const groupedMessages = messages.reduce((groups, msg) => {
    const dateKey = msg.created_at ? new Date(msg.created_at).toDateString() : 'Unknown';
    if (!groups[dateKey]) {
      groups[dateKey] = {
        label: formatDateGroup(msg.created_at),
        messages: [],
      };
    }
    groups[dateKey].messages.push(msg);
    return groups;
  }, {});

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  const handleSearch = (e) => {
    e.preventDefault();
    fetchMessages(1);
  };

  const handlePageChange = (page) => {
    fetchMessages(page);
  };

  const openErrorModal = (msgId) => {
    setExpandedError(msgId);
  };

  const closeErrorModal = () => {
    setExpandedError(null);
  };

  // Get the error message content
  const getErrorMessage = () => {
    if (!expandedError) return null;
    const msg = messages.find(m => m.id === expandedError);
    return msg?.error_message || 'No error details available';
  };

  // Handle retry for a failed message
  const handleRetry = async (messageId) => {
    setRetryingIds(prev => new Set([...prev, messageId]));
    try {
      const response = await messagesAPI.retry(messageId);
      if (response.data.success) {
        showSuccess('Message retry initiated successfully');
        // Refresh to see updated status
        fetchMessages(pagination.current_page, true);
        fetchStats();
      }
    } catch (err) {
      const errorMsg = err.response?.data?.error || 'Failed to retry message';
      showError(errorMsg);
      console.error('Retry failed:', err);
    } finally {
      setRetryingIds(prev => {
        const newSet = new Set(prev);
        newSet.delete(messageId);
        return newSet;
      });
    }
  };

  // Check if message can be retried
  const canRetry = (msg) => {
    return msg.status === 'failed' && (msg.retry_count || 0) < 3;
  };

  // Generate page numbers for pagination
  const getPageNumbers = () => {
    const pages = [];
    const { current_page, last_page } = pagination;
    const delta = 2;

    for (let i = Math.max(1, current_page - delta); i <= Math.min(last_page, current_page + delta); i++) {
      pages.push(i);
    }

    if (pages[0] > 1) {
      if (pages[0] > 2) pages.unshift('...');
      pages.unshift(1);
    }
    if (pages[pages.length - 1] < last_page) {
      if (pages[pages.length - 1] < last_page - 1) pages.push('...');
      pages.push(last_page);
    }

    return pages;
  };

  return (
    <div className="message-history-page">
      {/* Error Modal */}
      {expandedError && (
        <>
          <div className="error-backdrop" onClick={closeErrorModal} />
          <div className="error-modal">
            <div className="error-modal-header">
              <div className="error-modal-title">
                <span>⚠️</span>
                <span>Error Details</span>
              </div>
              <button className="error-modal-close" onClick={closeErrorModal}>
                ✕
              </button>
            </div>
            <div className="error-modal-body">
              {getErrorMessage()}
            </div>
          </div>
        </>
      )}

      <div className="page-header">
        <div className="page-header-top">
          <button className="btn-back" onClick={() => navigate('/')}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="15 18 9 12 15 6" />
            </svg>
            Back to Dashboard
          </button>
          <div className="auto-refresh-toggle">
            <label className="toggle-label">
              <input
                type="checkbox"
                checked={autoRefresh}
                onChange={(e) => setAutoRefresh(e.target.checked)}
              />
              <span className="toggle-slider"></span>
              Auto-refresh
            </label>
            <span className="last-refresh">
              Updated: {lastRefresh.toLocaleTimeString()}
            </span>
          </div>
        </div>
        <h1 className="page-title">Message History</h1>
        <p className="page-subtitle">Monitor all sent messages and their delivery status across channels</p>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="stats-cards">
          <div className="stat-card">
            <div className="stat-value">{stats.total}</div>
            <div className="stat-label">Total Messages</div>
          </div>
          <div className="stat-card success">
            <div className="stat-value">{stats.success_rate || 0}%</div>
            <div className="stat-label">Success Rate</div>
          </div>
          <div className="stat-card danger">
            <div className="stat-value">{stats.failed}</div>
            <div className="stat-label">Failed</div>
          </div>
          <div className="stat-card whatsapp">
            <div className="stat-value">{stats.by_channel?.whatsapp || 0}</div>
            <div className="stat-label">WhatsApp</div>
          </div>
          <div className="stat-card email">
            <div className="stat-value">{stats.by_channel?.email || 0}</div>
            <div className="stat-label">Email</div>
          </div>
          {stats.by_channel?.sms > 0 && (
            <div className="stat-card sms">
              <div className="stat-value">{stats.by_channel?.sms || 0}</div>
              <div className="stat-label">SMS</div>
            </div>
          )}
        </div>
      )}

      {/* Filters */}
      <form className="filters-section" onSubmit={handleSearch}>
        <input
          type="text"
          placeholder="Search by name, phone, email, or booking ID..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
          className="search-input"
        />
        <select
          value={filters.status}
          onChange={(e) => handleFilterChange('status', e.target.value)}
        >
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="queued">Queued</option>
          <option value="sent">Sent</option>
          <option value="delivered">Delivered</option>
          <option value="read">Read</option>
          <option value="failed">Failed</option>
        </select>
        <select
          value={filters.channel}
          onChange={(e) => handleFilterChange('channel', e.target.value)}
        >
          <option value="">All Channels</option>
          <option value="whatsapp">WhatsApp</option>
          <option value="email">Email</option>
          <option value="sms">SMS</option>
        </select>
        <select
          value={pagination.per_page}
          onChange={(e) => setPagination(prev => ({ ...prev, per_page: parseInt(e.target.value) }))}
        >
          <option value="25">25 per page</option>
          <option value="50">50 per page</option>
          <option value="100">100 per page</option>
        </select>
        <button type="submit" className="btn-search">Search</button>
      </form>

      {/* Content */}
      <div className="content-container">
        {loading ? (
          <div className="loading-state">
            <div className="loading-spinner"></div>
            <div className="loading-text">Loading messages...</div>
          </div>
        ) : messages.length === 0 ? (
          <div className="empty-state">
            <div className="empty-icon">📭</div>
            <div className="empty-text">No messages found</div>
          </div>
        ) : (
          <>
            {Object.entries(groupedMessages).map(([dateKey, group]) => (
              <div key={dateKey} className="date-group">
                <div className="date-group-header">
                  <span className="date-label">{group.label}</span>
                  <span className="date-count">{group.messages.length} message{group.messages.length !== 1 ? 's' : ''}</span>
                </div>
                <div className="messages-table-container">
                  <table className="messages-table">
                    <thead>
                      <tr>
                        <th className="col-time">Time</th>
                        <th className="col-channel">Channel</th>
                        <th className="col-recipient">Recipient</th>
                        <th className="col-booking">Booking</th>
                        <th className="col-status">Status</th>
                        <th className="col-error">Details</th>
                        <th className="col-actions">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {group.messages.map((msg) => (
                        <tr key={msg.id} className={msg.status === 'failed' || msg.status === 'undelivered' ? 'row-failed' : ''}>
                          <td className="col-time">
                            <span className="time-cell">
                              {new Date(msg.created_at).toLocaleTimeString('en-GB', {
                                hour: '2-digit',
                                minute: '2-digit',
                              })}
                            </span>
                          </td>
                          <td className="col-channel">
                            <span className={`channel-badge channel-${msg.channel}`}>
                              <span className="channel-icon">{getChannelIcon(msg.channel)}</span>
                              {getChannelLabel(msg.channel)}
                            </span>
                          </td>
                          <td className="col-recipient">
                            <span className="recipient-cell">{msg.recipient}</span>
                          </td>
                          <td className="col-booking">
                            {msg.booking ? (
                              <div className="booking-cell">
                                {msg.booking.customer_name}
                                <span className="booking-cell-id">#{msg.booking.bokun_booking_id}</span>
                              </div>
                            ) : (
                              <span className="text-muted">—</span>
                            )}
                          </td>
                          <td className="col-status">
                            <span className={`badge ${getStatusBadge(msg.status)}`}>
                              {msg.status}
                            </span>
                          </td>
                          <td className="col-error error-cell">
                            {msg.error_message ? (
                              <button
                                className="error-toggle"
                                onClick={() => openErrorModal(msg.id)}
                                title="Click to view error details"
                              >
                                <span className="error-toggle-icon">⚠️</span>
                                View Error
                              </button>
                            ) : (
                              <span className="text-muted">—</span>
                            )}
                          </td>
                          <td className="col-actions">
                            {canRetry(msg) ? (
                              <button
                                className="btn-retry"
                                onClick={() => handleRetry(msg.id)}
                                disabled={retryingIds.has(msg.id)}
                                title={`Retry (${msg.retry_count || 0}/3 attempts used)`}
                              >
                                {retryingIds.has(msg.id) ? (
                                  <>
                                    <span className="retry-spinner"></span>
                                    Retrying...
                                  </>
                                ) : (
                                  <>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="retry-icon">
                                      <polyline points="23 4 23 10 17 10" />
                                      <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                                    </svg>
                                    Retry
                                  </>
                                )}
                              </button>
                            ) : msg.status === 'failed' ? (
                              <span className="retry-exhausted" title="Maximum retry attempts reached">
                                Max retries
                              </span>
                            ) : (
                              <span className="text-muted">—</span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            ))}
          </>
        )}
      </div>

      {/* Pagination */}
      {pagination.last_page > 1 && (
        <div className="pagination">
          <button
            className="page-btn"
            disabled={pagination.current_page === 1}
            onClick={() => handlePageChange(1)}
            title="First page"
          >
            ««
          </button>
          <button
            className="page-btn"
            disabled={pagination.current_page === 1}
            onClick={() => handlePageChange(pagination.current_page - 1)}
            title="Previous page"
          >
            «
          </button>

          {getPageNumbers().map((page, idx) => (
            page === '...' ? (
              <span key={`ellipsis-${idx}`} className="page-ellipsis">...</span>
            ) : (
              <button
                key={page}
                className={`page-btn ${pagination.current_page === page ? 'active' : ''}`}
                onClick={() => handlePageChange(page)}
              >
                {page}
              </button>
            )
          ))}

          <button
            className="page-btn"
            disabled={pagination.current_page === pagination.last_page}
            onClick={() => handlePageChange(pagination.current_page + 1)}
            title="Next page"
          >
            »
          </button>
          <button
            className="page-btn"
            disabled={pagination.current_page === pagination.last_page}
            onClick={() => handlePageChange(pagination.last_page)}
            title="Last page"
          >
            »»
          </button>

          <span className="page-info">
            {pagination.total} total message{pagination.total !== 1 ? 's' : ''}
          </span>
        </div>
      )}
    </div>
  );
}
