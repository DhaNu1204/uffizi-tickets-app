import axios from 'axios';
import * as Sentry from '@sentry/react';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add token to requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Handle responses and track errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;

    // Handle 401 - redirect to login
    if (status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
      return Promise.reject(error);
    }

    // Track API errors in Sentry (5xx errors and network failures)
    if (status >= 500 || !error.response) {
      Sentry.captureException(error, {
        tags: {
          api_error: true,
          status_code: status || 'network_error',
          endpoint: error.config?.url || 'unknown',
          method: error.config?.method?.toUpperCase() || 'unknown',
        },
        extra: {
          request_url: error.config?.url,
          request_method: error.config?.method,
          response_status: status,
          response_data: error.response?.data,
        },
      });
    }

    // Add breadcrumb for all API errors (helps debugging)
    if (status >= 400) {
      Sentry.addBreadcrumb({
        category: 'api',
        message: `API Error: ${error.config?.method?.toUpperCase()} ${error.config?.url}`,
        level: status >= 500 ? 'error' : 'warning',
        data: {
          status,
          error: error.response?.data?.error || error.message,
        },
      });
    }

    return Promise.reject(error);
  }
);

// Auth endpoints
export const authAPI = {
  login: (email, password) => api.post('/login', { email, password }),
  logout: () => api.post('/logout'),
  getUser: () => api.get('/user'),
};

// Booking endpoints
export const bookingsAPI = {
  list: (params = {}) => api.get('/bookings', { params }),
  grouped: (params = {}) => api.get('/bookings/grouped', { params }),
  get: (id) => api.get(`/bookings/${id}`),
  create: (data) => api.post('/bookings', data),
  update: (id, data) => api.put(`/bookings/${id}`, data),
  delete: (id) => api.delete(`/bookings/${id}`),
  stats: (params = {}) => api.get('/bookings/stats', { params }),
  sync: () => api.post('/bookings/sync'),
  autoSync: () => api.post('/bookings/auto-sync'),
  import: (dateFrom, dateTo) => api.post('/bookings/import', { date_from: dateFrom, date_to: dateTo }),
  // Wizard progress tracking
  updateWizardProgress: (id, step, action) => api.post(`/bookings/${id}/wizard-progress`, { step, action }),
};

// Webhook endpoints
export const webhooksAPI = {
  list: (params = {}) => api.get('/webhooks', { params }),
  get: (id) => api.get(`/webhooks/${id}`),
  stats: () => api.get('/webhooks/stats'),
  retry: (id) => api.post(`/webhooks/${id}/retry`),
  retryAll: (maxRetries = 3) => api.post('/webhooks/retry-all', { max_retries: maxRetries }),
  cleanup: (days, status = null) => api.delete('/webhooks/cleanup', { data: { days, status } }),
};

// Messaging endpoints
export const messagesAPI = {
  // Send ticket to customer
  sendTicket: (bookingId, data) => api.post(`/bookings/${bookingId}/send-ticket`, data),

  // Detect which channel will be used
  detectChannel: (bookingId) => api.get(`/bookings/${bookingId}/detect-channel`),

  // Get message history for a booking
  history: (bookingId) => api.get(`/bookings/${bookingId}/messages`),

  // Preview message content
  preview: (data) => api.post('/messages/preview', data),

  // Get available templates
  templates: (params = {}) => api.get('/messages/templates', { params }),

  // Send manual message (without booking)
  sendManual: (data) => {
    const formData = new FormData();
    formData.append('channel', data.channel);
    formData.append('recipient', data.recipient);
    formData.append('message', data.message);
    if (data.subject) formData.append('subject', data.subject);
    if (data.attachment) formData.append('attachment', data.attachment);

    return api.post('/messages/send-manual', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  // Get manual message history
  manualHistory: (limit = 50) => api.get('/messages/manual-history', { params: { limit } }),

  // Sync message statuses from Twilio
  syncStatus: () => api.post('/messages/sync-status'),
};

// Attachment endpoints
export const attachmentsAPI = {
  // Upload attachment (PDF file)
  upload: (bookingId, file, onProgress) => {
    const formData = new FormData();
    formData.append('file', file);

    return api.post(`/bookings/${bookingId}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: onProgress ? (e) => onProgress(Math.round((e.loaded * 100) / e.total)) : undefined,
    });
  },

  // List attachments for a booking
  list: (bookingId) => api.get(`/bookings/${bookingId}/attachments`),

  // Delete an attachment
  delete: (attachmentId) => api.delete(`/attachments/${attachmentId}`),

  // Get download URL (legacy)
  download: (attachmentId) => api.get(`/attachments/${attachmentId}/download`),

  // Get temporary download link (new format)
  getDownloadLink: (attachmentId) => api.get(`/attachments/${attachmentId}/download-link`),
};

// VOX/PopGuide Audio Guide API
export const voxAPI = {
  // Create VOX account for a booking (auto-generates audio guide credentials)
  createAccount: (bookingId) => api.post(`/bookings/${bookingId}/create-vox-account`),

  // Get VOX account status for a booking
  getStatus: (bookingId) => api.get(`/bookings/${bookingId}/vox-status`),

  // Test VOX API connection (admin)
  testConnection: () => api.get('/vox/test'),

  // Get VOX account details by account ID
  getAccount: (accountId) => api.get(`/vox/accounts/${accountId}`),
};

// Template admin endpoints
export const templatesAPI = {
  // Wizard endpoints
  getLanguages: () => api.get('/templates/languages'),
  getByLanguageType: (language, templateType, channel = 'email') =>
    api.get('/templates/by-language-type', { params: { language, template_type: templateType, channel } }),

  // Admin CRUD endpoints
  list: (params = {}) => api.get('/admin/templates', { params }),
  get: (id) => api.get(`/admin/templates/${id}`),
  create: (data) => api.post('/admin/templates', data),
  update: (id, data) => api.put(`/admin/templates/${id}`, data),
  delete: (id) => api.delete(`/admin/templates/${id}`),
  preview: (id, variables = {}) => api.post(`/admin/templates/${id}/preview`, { variables }),
  duplicate: (id, data = {}) => api.post(`/admin/templates/${id}/duplicate`, data),
};

// Conversations endpoints (WhatsApp/SMS inbox)
export const conversationsAPI = {
  // List conversations with optional filters
  list: (params = {}) => api.get('/conversations', { params }),

  // Get single conversation with all messages
  get: (id) => api.get(`/conversations/${id}`),

  // Send reply to a conversation
  reply: (id, message) => api.post(`/conversations/${id}/reply`, { message }),

  // Mark conversation as read
  markRead: (id) => api.put(`/conversations/${id}/read`),

  // Link conversation to a booking
  linkBooking: (id, bookingId) => api.put(`/conversations/${id}/booking`, { booking_id: bookingId }),

  // Archive conversation
  archive: (id) => api.delete(`/conversations/${id}`),

  // Get unread count for badge
  unreadCount: () => api.get('/conversations/unread-count'),
};

// Monitoring endpoints (Delivery tracking)
export const monitoringAPI = {
  // Get delivery statistics
  deliveryStats: (period = '7d') => api.get('/monitoring/delivery-stats', { params: { period } }),

  // Get failed messages list
  failedMessages: (params = {}) => api.get('/monitoring/failed-messages', { params }),

  // Get channel health status
  channelHealth: () => api.get('/monitoring/channel-health'),

  // Get daily summary
  dailySummary: () => api.get('/monitoring/daily-summary'),
};

export default api;
