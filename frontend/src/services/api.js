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

export default api;
