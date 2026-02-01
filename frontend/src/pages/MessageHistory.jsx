import { useState, useEffect, useCallback } from 'react';
import { useToast } from '../context/ToastContext';
import api from '../services/api';
import './MessageHistory.css';

export default function MessageHistory() {
  const { error: showError } = useToast();
  const [messages, setMessages] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    status: '',
    channel: '',
    search: '',
  });
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0,
  });

  const fetchMessages = useCallback(async (page = 1) => {
    setLoading(true);
    try {
      const params = { ...filters, page, per_page: 20 };
      // Remove empty params
      Object.keys(params).forEach(key => {
        if (!params[key]) delete params[key];
      });
      const response = await api.get('/messages/all', { params });
      setMessages(response.data.data || []);
      setPagination({
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      });
    } catch (err) {
      showError('Failed to load messages');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [filters, showError]);

  const fetchStats = useCallback(async () => {
    try {
      const response = await api.get('/messages/stats');
      setStats(response.data);
    } catch (err) {
      console.error('Failed to load stats:', err);
    }
  }, []);

  useEffect(() => {
    fetchMessages();
    fetchStats();
  }, [fetchMessages, fetchStats]);

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
      whatsapp: String.fromCodePoint(0x1F4F1),
      email: String.fromCodePoint(0x1F4E7),
      sms: String.fromCodePoint(0x1F4AC),
    };
    return icons[channel] || String.fromCodePoint(0x1F4E8);
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  const handleSearch = (e) => {
    e.preventDefault();
    fetchMessages(1);
  };

  return (
    <div className="message-history-page">
      <div className="page-header">
        <h1>Message History</h1>
        <p>View all sent messages and their delivery status</p>
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
        </div>
      )}

      {/* Filters */}
      <form className="filters-section" onSubmit={handleSearch}>
        <input
          type="text"
          placeholder="Search by name, phone, or booking ID..."
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
        <button type="submit" className="btn-search">Search</button>
      </form>

      {/* Messages Table */}
      <div className="messages-table-container">
        {loading ? (
          <div className="loading">Loading messages...</div>
        ) : messages.length === 0 ? (
          <div className="no-messages">No messages found</div>
        ) : (
          <table className="messages-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Channel</th>
                <th>Recipient</th>
                <th>Booking</th>
                <th>Status</th>
                <th>Twilio SID</th>
                <th>Error</th>
              </tr>
            </thead>
            <tbody>
              {messages.map((msg) => (
                <tr key={msg.id} className={msg.status === 'failed' ? 'row-failed' : ''}>
                  <td>{msg.id}</td>
                  <td className="date-cell">{formatDate(msg.created_at)}</td>
                  <td>
                    <span className={`channel-badge channel-${msg.channel}`}>
                      {getChannelIcon(msg.channel)} {msg.channel}
                    </span>
                  </td>
                  <td className="recipient-cell">{msg.recipient}</td>
                  <td>
                    {msg.booking ? (
                      <span title={msg.booking.bokun_booking_id}>
                        {msg.booking.customer_name}
                      </span>
                    ) : (
                      <span className="text-muted">-</span>
                    )}
                  </td>
                  <td>
                    <span className={`badge ${getStatusBadge(msg.status)}`}>
                      {msg.status}
                    </span>
                  </td>
                  <td>
                    {msg.external_id ? (
                      <code className="sid" title={msg.external_id}>
                        {msg.external_id.substring(0, 12)}...
                      </code>
                    ) : (
                      <span className="text-muted">-</span>
                    )}
                  </td>
                  <td>
                    {msg.error_message && (
                      <span className="error-message" title={msg.error_message}>
                        {msg.error_message.length > 40
                          ? msg.error_message.substring(0, 40) + '...'
                          : msg.error_message}
                      </span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Pagination */}
      {pagination.last_page > 1 && (
        <div className="pagination">
          <button
            disabled={pagination.current_page === 1}
            onClick={() => fetchMessages(pagination.current_page - 1)}
          >
            Previous
          </button>
          <span>
            Page {pagination.current_page} of {pagination.last_page} ({pagination.total} total)
          </span>
          <button
            disabled={pagination.current_page === pagination.last_page}
            onClick={() => fetchMessages(pagination.current_page + 1)}
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}
