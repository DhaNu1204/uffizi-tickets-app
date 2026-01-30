# Uffizi Ticket App - Development Guide

> **IMPORTANT**: Claude Code should read this entire file before making any changes to the project.

## Project Overview

The **Uffizi Ticket App** is a production ticket management dashboard for Uffizi Gallery tours. It enables staff to manage bookings, send tickets via WhatsApp/SMS/Email, and handle customer conversations.

**Live URL:** https://uffizi.deetech.cc

| Metric | Value |
|--------|-------|
| Total Codebase | ~16,500 LOC across 76 files |
| Backend | Laravel 12 (~8,000 LOC) |
| Frontend | React 19 (~6,700 LOC) |
| Database | MySQL with 26 migrations |
| API Endpoints | 26 routes |

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND (React 19 + Vite)                   │
│  Dashboard │ TicketWizard │ Conversations │ TemplateAdmin      │
│                           │ Axios + Bearer Token                │
└───────────────────────────┼─────────────────────────────────────┘
                            ▼
┌───────────────────────────────────────────────────────────────────┐
│                    BACKEND (Laravel 12 API)                       │
│  Controllers (11) → Services (6) → Models (7) → MySQL            │
└───────────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        ▼                   ▼                   ▼
   Bokun API           Twilio API           AWS S3
   (Bookings)       (WhatsApp/SMS)          (PDFs)
```

---

## Project Structure

### Backend (Laravel 12)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 11 controllers
│   │   │   ├── BookingController.php      # Main booking CRUD
│   │   │   ├── MessageController.php      # Ticket sending
│   │   │   ├── ConversationController.php # WhatsApp/SMS inbox
│   │   │   ├── ManualMessageController.php
│   │   │   ├── TemplateAdminController.php
│   │   │   ├── AttachmentController.php
│   │   │   ├── TwilioWebhookController.php
│   │   │   └── AuthController.php
│   │   └── Middleware/
│   ├── Models/                 # 7 Eloquent models
│   │   ├── Booking.php         # Central domain model
│   │   ├── Conversation.php    # WhatsApp/SMS threads
│   │   ├── Message.php
│   │   ├── MessageAttachment.php
│   │   ├── MessageTemplate.php
│   │   └── WebhookLog.php
│   └── Services/               # 6 service classes
│       ├── MessagingService.php # Channel orchestration (USE THIS)
│       ├── TwilioService.php    # WhatsApp/SMS
│       ├── EmailService.php
│       ├── BokunService.php
│       └── IncomingMessageService.php
├── database/migrations/        # 26 migrations
├── routes/api.php              # API routes
└── config/services.php         # External service config
```

### Frontend (React 19 + Vite)

```
frontend/
├── src/
│   ├── components/
│   │   ├── BookingTable.jsx       # Main table with inline editing
│   │   ├── TicketWizard/          # 6-step wizard
│   │   │   ├── index.jsx          # Wizard orchestrator
│   │   │   └── steps/             # Step1-Step6 components
│   │   ├── ManualSendModal.jsx
│   │   ├── DateNavigator.jsx
│   │   └── StatsCards.jsx
│   ├── pages/
│   │   ├── Dashboard.jsx          # Main view
│   │   ├── ConversationsPage.jsx  # WhatsApp/SMS inbox
│   │   ├── TemplateAdmin.jsx
│   │   └── Login.jsx
│   ├── context/
│   │   ├── AuthContext.jsx        # Authentication state
│   │   └── ToastContext.jsx       # Notifications
│   ├── services/
│   │   └── api.js                 # Axios HTTP client
│   └── constants/
│       ├── bookingStatus.js
│       └── guidedTours.js
└── vite.config.js
```

---

## Key Domain Concepts

### Booking Types

| Type | Product ID | Can Send Tickets? |
|------|------------|-------------------|
| Timed Entry | `961802` | ✅ Yes - via wizard |
| Guided Tours | `961801`, `962885`, `962886`, `1130528`, `1135055` | ❌ No |

### Message Channel Priority

```
Customer has phone?
├── YES → Check WhatsApp availability
│         ├── Has WhatsApp → Send via WhatsApp only ✓
│         └── No WhatsApp → Has email?
│                          ├── YES → Send Email + SMS notification
│                          └── NO → SMS only (no attachments)
└── NO → Has email?
         ├── YES → Send Email only ✓
         └── NO → Cannot send ✗
```

### Wizard Progress States

| State | Meaning |
|-------|---------|
| `null` | Not started |
| `in_progress` | Currently in wizard (step 1-6) |
| `abandoned` | User left wizard incomplete |
| `completed` | Tickets successfully sent |

### OTA Channels (Limited Contact Info)

- GetYourGuide
- Viator
- TripAdvisor
- Expedia

---

## Critical Business Rules

1. **Only Timed Entry tickets** can be sent through the wizard
2. **Reference number required** before sending tickets
3. **At least one PDF attachment** required for sending
4. **Audio guide credentials** required if booking has audio guide
5. **WhatsApp 24-hour window** - Can only send freely within 24 hours of customer message

---

# LARAVEL BACKEND PATTERNS

## Controller Conventions

### Structure Pattern

```php
<?php

namespace App\Http\Controllers;

use App\Models\YourModel;
use App\Services\YourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YourController extends Controller
{
    protected YourService $yourService;

    // Inject services via constructor
    public function __construct(YourService $yourService)
    {
        $this->yourService = $yourService;
    }

    /**
     * List resources with filtering and pagination.
     * 
     * Query Parameters:
     * - status: Filter by status
     * - search: Search term
     * - per_page: Items per page (default 20, max 100)
     */
    public function index(Request $request): JsonResponse
    {
        $query = YourModel::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $results = $query->paginate($perPage);

        return response()->json($results);
    }

    public function show(int $id): JsonResponse
    {
        $resource = YourModel::findOrFail($id);
        return response()->json($resource);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $resource = YourModel::create($validated);
        return response()->json($resource, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $resource = YourModel::findOrFail($id);
        $resource->update($validated);

        return response()->json($resource);
    }

    public function destroy(int $id): JsonResponse
    {
        $resource = YourModel::findOrFail($id);
        $resource->delete();
        return response()->json(['message' => 'Resource deleted successfully']);
    }
}
```

### Response Format

```php
// Success - single resource
return response()->json($booking);

// Success - with message
return response()->json([
    'success' => true,
    'message' => 'Operation completed',
    'data' => $result,
]);

// Created (201)
return response()->json($resource, 201);

// Validation error (422)
return response()->json([
    'success' => false,
    'error' => 'Specific error message',
], 422);

// Server error (500)
return response()->json([
    'success' => false,
    'error' => 'Failed to process: ' . $e->getMessage(),
], 500);
```

## Service Layer Pattern

**IMPORTANT**: Always use `MessagingService` for sending messages. Never call `TwilioService` or `EmailService` directly from controllers.

```php
<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class YourService
{
    protected OtherService $otherService;

    public function __construct(OtherService $otherService)
    {
        $this->otherService = $otherService;
    }

    /**
     * Process booking with clear documentation.
     */
    public function processBooking(Booking $booking, array $options = []): array
    {
        $result = [
            'success' => false,
            'data' => null,
            'errors' => [],
        ];

        // Validate preconditions
        if (!$booking->reference_number) {
            $result['errors'][] = 'Booking has no reference number';
            return $result;
        }

        try {
            $data = $this->doSomething($booking);
            
            $result['success'] = true;
            $result['data'] = $data;

            Log::info('Booking processed', ['booking_id' => $booking->id]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error('Failed to process booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }
}
```

### MessagingService Usage (Correct Pattern)

```php
// ✅ CORRECT - Use MessagingService from controller
public function sendTicket(Request $request, int $id): JsonResponse
{
    $booking = Booking::findOrFail($id);
    $result = $this->messagingService->sendTicket($booking, $language, $attachmentIds);
    
    if ($result['success']) {
        return response()->json([
            'success' => true,
            'channel_used' => $result['channel_used']
        ]);
    }
    return response()->json(['success' => false, 'errors' => $result['errors']], 422);
}

// ❌ WRONG - Don't call TwilioService directly from controller
public function sendTicket(Request $request, int $id): JsonResponse
{
    $this->twilioService->sendWhatsApp($booking, $template); // DON'T DO THIS
}
```

## Route Conventions

```php
<?php
// routes/api.php

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication)
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'check']);
Route::post('/webhook/bokun', [BookingController::class, 'handleWebhook']);
Route::post('/webhooks/twilio/status', [TwilioWebhookController::class, 'status']);
Route::post('/webhooks/twilio/incoming', [TwilioWebhookController::class, 'incoming']);

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum Authentication)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // Resource CRUD
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

    // Custom actions
    Route::post('/bookings/{id}/send-ticket', [MessageController::class, 'sendTicket']);
    Route::post('/bookings/{id}/wizard-progress', [BookingController::class, 'updateWizardProgress']);

    // Heavy operations - stricter rate limiting
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/bookings/sync', [BookingController::class, 'syncBookings']);
    });

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/templates', [TemplateAdminController::class, 'index']);
        Route::post('/templates', [TemplateAdminController::class, 'store']);
        Route::put('/templates/{id}', [TemplateAdminController::class, 'update']);
        Route::delete('/templates/{id}', [TemplateAdminController::class, 'destroy']);
    });
});
```

## Caching Pattern

```php
use Illuminate\Support\Facades\Cache;

public function stats(Request $request)
{
    $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
    $dateTo = $request->input('date_to', Carbon::now()->endOfMonth()->toDateString());
    $cacheKey = "bookings_stats_{$dateFrom}_{$dateTo}";

    $stats = Cache::remember($cacheKey, 60, function () use ($dateFrom, $dateTo) {
        return $this->calculateStats($dateFrom, $dateTo);
    });

    return response()->json($stats);
}

// Clear cache when data changes
private function clearStatsCache(): void
{
    $dateFrom = Carbon::now()->startOfMonth()->toDateString();
    $dateTo = Carbon::now()->endOfMonth()->toDateString();
    Cache::forget("bookings_stats_{$dateFrom}_{$dateTo}");
}
```

## Logging Standards

```php
// Info: Successful operations
Log::info('Booking synced successfully', [
    'booking_id' => $booking->id,
    'confirmation_code' => $confirmationCode,
]);

// Warning: Non-critical issues
Log::warning('WhatsApp check failed, using fallback', [
    'phone' => $phone,
    'error' => $e->getMessage(),
]);

// Error: Failures
Log::error('Failed to send ticket', [
    'booking_id' => $booking->id,
    'channel' => $channel,
    'error' => $e->getMessage(),
]);
```

## Webhook Handling

```php
public function handleWebhook(Request $request): JsonResponse
{
    $payload = $request->all();
    $headers = $request->headers->all();

    // 1. Log the webhook
    $webhookLog = WebhookLog::create([
        'event_type' => $payload['eventType'] ?? 'unknown',
        'payload' => $payload,
        'headers' => $headers,
        'status' => 'pending',
    ]);

    try {
        // 2. Verify signature
        if (!$this->verifySignature($headers, $payload)) {
            throw new \Exception('Signature verification failed');
        }

        // 3. Process
        $result = $this->processEvent($payload);
        $webhookLog->markAsProcessed();
        
        return response()->json($result, 200);

    } catch (\Exception $e) {
        // Return 200 to prevent retries
        $webhookLog->markAsFailed($e->getMessage());
        return response()->json([
            'message' => 'Webhook received but processing failed',
            'error' => $e->getMessage(),
        ], 200);
    }
}
```

---

# REACT FRONTEND PATTERNS

## State Management

This project uses **React Context API** (no Redux):

| Context | Purpose |
|---------|---------|
| `AuthContext` | User session, login/logout |
| `ToastContext` | Notification queue |

Local state handles component-specific concerns.

## API Service Pattern (api.js)

```javascript
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Auto-inject Bearer token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle auth errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;

// Domain-specific facades
export const bookingsAPI = {
  list: (params) => api.get('/bookings', { params }),
  grouped: (params) => api.get('/bookings/grouped', { params }),
  stats: (params) => api.get('/bookings/stats', { params }),
  get: (id) => api.get(`/bookings/${id}`),
  update: (id, data) => api.put(`/bookings/${id}`, data),
  delete: (id) => api.delete(`/bookings/${id}`),
  sync: () => api.post('/bookings/sync'),
  updateWizardProgress: (id, data) => api.post(`/bookings/${id}/wizard-progress`, data),
};

export const messagesAPI = {
  sendTicket: (bookingId, data) => api.post(`/bookings/${bookingId}/send-ticket`, data),
  detectChannel: (bookingId) => api.get(`/bookings/${bookingId}/detect-channel`),
  preview: (data) => api.post('/messages/preview', data),
  history: (bookingId) => api.get(`/bookings/${bookingId}/messages`),
  templates: (params) => api.get('/messages/templates', { params }),
};

export const attachmentsAPI = {
  upload: (bookingId, formData) => api.post(`/bookings/${bookingId}/attachments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  list: (bookingId) => api.get(`/bookings/${bookingId}/attachments`),
  delete: (id) => api.delete(`/attachments/${id}`),
};

export const conversationsAPI = {
  list: (params) => api.get('/conversations', { params }),
  get: (id) => api.get(`/conversations/${id}`),
  reply: (id, content) => api.post(`/conversations/${id}/reply`, { content }),
  markRead: (id) => api.put(`/conversations/${id}/read`),
  unreadCount: () => api.get('/conversations/unread-count'),
};
```

## Context Patterns

### AuthContext

```jsx
import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { authAPI } from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      authAPI.user()
        .then(res => setUser(res.data))
        .catch(() => localStorage.removeItem('auth_token'))
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const login = useCallback(async (email, password) => {
    const response = await authAPI.login({ email, password });
    const { token, user } = response.data;
    localStorage.setItem('auth_token', token);
    setUser(user);
    return user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authAPI.logout();
    } finally {
      localStorage.removeItem('auth_token');
      setUser(null);
    }
  }, []);

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: !!user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within AuthProvider');
  return context;
}
```

### ToastContext

```jsx
import { createContext, useContext, useState, useCallback } from 'react';

const ToastContext = createContext(null);

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const addToast = useCallback((message, type = 'info', duration = 5000) => {
    const id = Date.now();
    setToasts(prev => [...prev, { id, message, type }]);
    
    if (duration > 0) {
      setTimeout(() => {
        setToasts(prev => prev.filter(t => t.id !== id));
      }, duration);
    }
    return id;
  }, []);

  const removeToast = useCallback((id) => {
    setToasts(prev => prev.filter(t => t.id !== id));
  }, []);

  const success = useCallback((msg) => addToast(msg, 'success'), [addToast]);
  const error = useCallback((msg) => addToast(msg, 'error', 8000), [addToast]);
  const warning = useCallback((msg) => addToast(msg, 'warning'), [addToast]);
  const info = useCallback((msg) => addToast(msg, 'info'), [addToast]);

  return (
    <ToastContext.Provider value={{ toasts, addToast, removeToast, success, error, warning, info }}>
      {children}
    </ToastContext.Provider>
  );
}

export function useToast() {
  const context = useContext(ToastContext);
  if (!context) throw new Error('useToast must be used within ToastProvider');
  return context;
}
```

## Page Component Template

```jsx
import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useToast } from '../context/ToastContext';
import { someAPI } from '../services/api';

export default function MyPage() {
  const { user } = useAuth();
  const { success, error } = useToast();
  
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({ search: '', status: '' });

  useEffect(() => {
    fetchData();
  }, [filters]);

  const fetchData = async () => {
    setLoading(true);
    try {
      const response = await someAPI.list(filters);
      setData(response.data);
    } catch (err) {
      error('Failed to load data');
    } finally {
      setLoading(false);
    }
  };

  const handleAction = async (id) => {
    try {
      await someAPI.action(id);
      success('Action completed');
      fetchData();
    } catch (err) {
      error('Action failed: ' + (err.response?.data?.error || err.message));
    }
  };

  if (loading) return <div className="loading">Loading...</div>;

  return (
    <div className="page-container">
      <div className="filters">
        <input
          type="text"
          placeholder="Search..."
          value={filters.search}
          onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
        />
      </div>
      <div className="content">
        {data.map(item => (
          <div key={item.id} onClick={() => handleAction(item.id)}>
            {item.name}
          </div>
        ))}
      </div>
    </div>
  );
}
```

## Wizard Component Pattern

```jsx
import { useState, useEffect } from 'react';
import { useToast } from '../../context/ToastContext';
import { bookingsAPI } from '../../services/api';

const STEPS = [
  { id: 1, title: 'Booking Details', component: Step1 },
  { id: 2, title: 'Ticket Reference', component: Step2 },
  { id: 3, title: 'Attach Files', component: Step3 },
  { id: 4, title: 'Select Template', component: Step4 },
  { id: 5, title: 'Preview', component: Step5 },
  { id: 6, title: 'Send Status', component: Step6 },
];

export default function TicketWizard({ booking, onClose, onComplete }) {
  const { error } = useToast();
  const [currentStep, setCurrentStep] = useState(1);
  const [wizardData, setWizardData] = useState({
    booking,
    referenceNumber: booking.reference_number || '',
    attachments: [],
    language: 'en',
  });

  // Track wizard progress
  useEffect(() => {
    trackProgress('start');
    return () => {
      if (currentStep < 6) trackProgress('abandon');
    };
  }, []);

  const trackProgress = async (action) => {
    try {
      await bookingsAPI.updateWizardProgress(booking.id, { step: currentStep, action });
    } catch (err) {
      console.error('Failed to track progress:', err);
    }
  };

  const handleNext = () => {
    if (currentStep < STEPS.length) {
      setCurrentStep(prev => prev + 1);
      trackProgress('progress');
    }
  };

  const handleBack = () => {
    if (currentStep > 1) setCurrentStep(prev => prev - 1);
  };

  const handleComplete = () => {
    trackProgress('complete');
    onComplete?.();
    onClose();
  };

  const StepComponent = STEPS[currentStep - 1].component;

  return (
    <div className="wizard-container">
      <div className="wizard-progress">
        {STEPS.map(step => (
          <div key={step.id} className={`step ${currentStep >= step.id ? 'active' : ''}`}>
            {step.title}
          </div>
        ))}
      </div>
      <StepComponent
        wizardData={wizardData}
        updateData={(updates) => setWizardData(prev => ({ ...prev, ...updates }))}
        onNext={handleNext}
        onBack={handleBack}
        onComplete={handleComplete}
      />
    </div>
  );
}
```

## Error Handling

```jsx
const handleApiCall = async () => {
  try {
    const response = await someAPI.action();
    success('Operation completed');
    return response.data;
  } catch (err) {
    const message = err.response?.data?.error 
      || err.response?.data?.message 
      || err.message 
      || 'An error occurred';
    error(message);
    return null;
  }
};
```

## File Upload Pattern

```jsx
const handleFileUpload = async (files) => {
  const formData = new FormData();
  
  for (const file of files) {
    if (file.type !== 'application/pdf') {
      error('Only PDF files are allowed');
      return;
    }
    if (file.size > 10 * 1024 * 1024) {
      error('File size must be less than 10MB');
      return;
    }
    formData.append('files[]', file);
  }

  try {
    setUploading(true);
    const response = await attachmentsAPI.upload(bookingId, formData);
    success(`Uploaded ${files.length} file(s)`);
    return response.data;
  } catch (err) {
    error('Upload failed');
  } finally {
    setUploading(false);
  }
};
```

---

# TWILIO MESSAGING PATTERNS

## Channel Detection Logic

```php
public function hasWhatsApp(string $phoneNumber): bool
{
    $phone = $this->formatPhoneNumber($phoneNumber);

    // Countries where WhatsApp is NOT common
    $nonWhatsAppCountries = ['+86', '+81', '+82', '+7', '+1'];
    foreach ($nonWhatsAppCountries as $prefix) {
        if (str_starts_with($phone, $prefix)) {
            return false;
        }
    }

    // Use Twilio Lookup for other countries
    try {
        $lookup = $this->getClient()->lookups->v2->phoneNumbers($phone)
            ->fetch(['fields' => 'line_type_intelligence']);
        $lineType = $lookup->lineTypeIntelligence['type'] ?? null;
        return in_array($lineType, ['mobile', 'voip'], true);
    } catch (TwilioException $e) {
        return false; // Safe fallback
    }
}
```

## Phone Number Formatting

```php
// Always use E.164 format: +[country code][number]
protected function formatPhoneNumber(string $phone): string
{
    $phone = preg_replace('/[^\d+]/', '', $phone);
    if (!str_starts_with($phone, '+')) {
        $phone = '+' . $phone;
    }
    return $phone;
}
```

## Sending WhatsApp

```php
public function sendWhatsApp(Booking $booking, MessageTemplate $template, array $attachments = []): Message
{
    $phone = $this->formatPhoneNumber($booking->customer_phone);
    $variables = $booking->getTemplateVariables();

    $message = Message::create([
        'booking_id' => $booking->id,
        'channel' => Message::CHANNEL_WHATSAPP,
        'recipient' => $phone,
        'content' => $template->render($variables),
        'status' => Message::STATUS_PENDING,
    ]);

    try {
        $message->markQueued();

        $mediaUrls = [];
        foreach ($attachments as $attachment) {
            $url = $attachment->getTemporaryUrl();
            if ($url) $mediaUrls[] = $url;
        }

        $options = [
            'from' => "whatsapp:{$this->whatsappFrom}",
            'body' => $message->content,
        ];
        if (!empty($mediaUrls)) $options['mediaUrl'] = $mediaUrls;
        if (config('services.twilio.status_callback_url')) {
            $options['statusCallback'] = config('services.twilio.status_callback_url');
        }

        $twilioMessage = $this->getClient()->messages->create("whatsapp:{$phone}", $options);
        $message->markSent($twilioMessage->sid);

        return $message;

    } catch (TwilioException $e) {
        $message->markFailed($e->getMessage());
        throw $e;
    }
}
```

## Status Callback Handling

```php
public function handleStatusCallback(array $data): void
{
    $sid = $data['MessageSid'] ?? null;
    $status = $data['MessageStatus'] ?? null;

    if (!$sid || !$status) return;

    $message = Message::where('external_id', $sid)->first();
    if (!$message) return;

    switch (strtolower($status)) {
        case 'delivered':
            $message->markDelivered();
            break;
        case 'read':
            $message->markRead();
            break;
        case 'failed':
        case 'undelivered':
            $errorCode = $data['ErrorCode'] ?? 'Unknown';
            $message->markFailed("Error {$errorCode}");
            break;
    }
}
```

## Template Variables

```php
// Available in Booking::getTemplateVariables()
[
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'tour_date' => 'January 25, 2026',
    'tour_time' => '10:00 AM',
    'product_name' => 'Uffizi Gallery Timed Entry',
    'pax' => '2',
    'reference_number' => 'ABC123',
    'audio_guide_url' => 'https://...',
    'audio_guide_username' => 'user123',
    'audio_guide_password' => 'pass456',
]

// Template example:
// "Hello {{customer_name}}, your tickets for {{product_name}} on {{tour_date}} at {{tour_time}} are attached."
```

---

# DATABASE CONVENTIONS

## Migration Naming

```
YYYY_MM_DD_HHMMSS_<action>_<table>_table.php

Examples:
2026_01_25_100001_create_message_templates_table.php
2026_01_26_100000_add_wizard_progress_to_bookings_table.php
```

## Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('your_table', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('set null');
            
            // Fields
            $table->string('name', 255);
            $table->string('status', 50)->default('pending');
            $table->text('content');
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index(['booking_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('your_table');
    }
};
```

## Model Pattern

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YourModel extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'status', 'metadata'];

    protected $appends = ['computed_field'];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sent_at' => 'datetime',
    ];

    // Constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';

    // Relationships
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Accessors
    protected function getComputedFieldAttribute(): string
    {
        return $this->name . ' - ' . $this->status;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Status transitions
    public function markSent(?string $externalId = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'external_id' => $externalId,
            'sent_at' => now(),
        ]);
    }
}
```

## JSON Column Queries

```php
// Search in JSON array
$bookings = Booking::whereRaw(
    "JSON_SEARCH(participants, 'one', ?, NULL, '$[*].name') IS NOT NULL",
    ['%' . $searchTerm . '%']
)->get();

// Check JSON contains
$bookings = Booking::whereJsonContains('pax_details', ['type' => 'Adult'])->get();

// Get JSON length
$bookings = Booking::whereRaw('JSON_LENGTH(participants) > ?', [0])->get();
```

---

# ENVIRONMENT VARIABLES

```env
# App
APP_NAME="Uffizi Ticket App"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://uffizi.deetech.cc

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uffizi
DB_USERNAME=
DB_PASSWORD=

# Twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=
TWILIO_WHATSAPP_FROM=+14155238886
TWILIO_SMS_FROM=+15005550006
TWILIO_STATUS_CALLBACK_URL=https://your-domain.com/api/webhooks/twilio/status

# Bokun
BOKUN_ACCESS_KEY=
BOKUN_SECRET_KEY=
BOKUN_BASE_URL=https://api.bokun.io

# AWS S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=

# Email
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=tickets@uffizi.deetech.cc
MAIL_FROM_NAME="Uffizi Tickets"

# Frontend
VITE_API_URL=https://uffizi.deetech.cc/api
```

---

# COMMON GOTCHAS

1. **WhatsApp 24-hour window** - Can only send templated messages outside window
2. **Phone number formatting** - Always use E.164 format (+1234567890)
3. **Timezone** - All dates in Europe/Rome timezone
4. **Soft deletes** - Bookings use soft deletes for audit trail
5. **Cache invalidation** - Clear stats cache after booking updates
6. **OTA bookings** - May have limited/no contact information
7. **Media URLs** - Must be publicly accessible for WhatsApp (use pre-signed S3 URLs)

---

# QUICK REFERENCE

## Adding a New API Endpoint

1. Create/update controller in `app/Http/Controllers/`
2. Add route in `routes/api.php` (protected group)
3. Create service method if business logic needed
4. Update `frontend/src/services/api.js`

## Adding a New React Page

1. Create page in `src/pages/`
2. Add route in `App.jsx`
3. Use `AuthContext` for protected routes
4. Use `ToastContext` for notifications

## Sending Messages

1. Always use `MessagingService` for orchestration
2. Never call `TwilioService` or `EmailService` directly from controllers
3. Check channel availability with `detectChannel()`
4. Handle all three channels: WhatsApp, Email, SMS
