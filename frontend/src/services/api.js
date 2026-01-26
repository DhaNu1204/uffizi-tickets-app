import axios from 'axios';

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

// Handle 401 responses
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
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

  // Get download URL
  download: (attachmentId) => api.get(`/attachments/${attachmentId}/download`),
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

export default api;
