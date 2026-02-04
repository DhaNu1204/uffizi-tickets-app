# Uffizi Ticket App - Development Guide

> **IMPORTANT**: Claude Code should read this entire file before making any changes to the project.

## Project Overview

The **Uffizi Ticket App** is a production ticket management dashboard for Uffizi Gallery tours. It enables staff to manage bookings, send tickets via WhatsApp/SMS/Email, handle customer conversations, and manage audio guide access via PopGuide integration.

**Live URL:** https://uffizi.deetech.cc

| Metric | Value |
|--------|-------|
| Total Codebase | ~18,000 LOC across 85+ files |
| Backend | Laravel 12 (~9,000 LOC) |
| Frontend | React 19 (~7,500 LOC) |
| Database | MySQL with 28 migrations |
| API Endpoints | 40+ routes |

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND (React 19 + Vite)                   │
│  Dashboard │ TicketWizard │ Conversations │ MessageHistory     │
│                           │ Axios + Bearer Token                │
└───────────────────────────┼─────────────────────────────────────┘
                            ▼
┌───────────────────────────────────────────────────────────────────┐
│                    BACKEND (Laravel 12 API)                       │
│  Controllers (14) → Services (7) → Models (8) → MySQL            │
└───────────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┬───────────────┐
        ▼                   ▼                   ▼               ▼
   Bokun API           Twilio API           AWS S3         PopGuide API
   (Bookings)       (WhatsApp/SMS)          (PDFs)        (Audio Guides)
```

---

## Project Structure

### Backend (Laravel 12)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 14 controllers
│   │   │   ├── BookingController.php      # Main booking CRUD + sync
│   │   │   ├── MessageController.php      # Ticket sending via wizard
│   │   │   ├── MessageHistoryController.php # All messages view
│   │   │   ├── ConversationController.php # WhatsApp/SMS inbox
│   │   │   ├── ManualMessageController.php # Ad-hoc message sending
│   │   │   ├── TemplateAdminController.php # Template management
│   │   │   ├── AttachmentController.php   # PDF upload/serve
│   │   │   ├── TwilioWebhookController.php # Twilio callbacks
│   │   │   ├── VoxController.php          # PopGuide audio guide API
│   │   │   ├── MonitoringController.php   # Delivery stats & alerts
│   │   │   ├── WebhookController.php      # Bokun webhook admin
│   │   │   ├── HealthController.php       # Health checks
│   │   │   └── AuthController.php         # Login/logout
│   │   └── Middleware/
│   ├── Models/                 # 8 Eloquent models
│   │   ├── Booking.php         # Central domain model
│   │   ├── Conversation.php    # WhatsApp/SMS threads
│   │   ├── Message.php         # Sent messages
│   │   ├── MessageAttachment.php # PDF attachments
│   │   ├── MessageTemplate.php # Email/SMS templates
│   │   ├── WebhookLog.php      # Bokun webhook logs
│   │   └── User.php            # Admin users
│   └── Services/               # 7 service classes
│       ├── MessagingService.php    # Channel orchestration (USE THIS)
│       ├── TwilioService.php       # WhatsApp/SMS via Twilio
│       ├── EmailService.php        # Email via SMTP
│       ├── BokunService.php        # Booking sync from Bokun
│       ├── VoxService.php          # PopGuide audio guide integration
│       └── IncomingMessageService.php # Handle incoming messages
├── config/
│   ├── services.php            # External service config
│   └── whatsapp_templates.php  # WhatsApp Content Template SIDs
├── database/migrations/        # 28 migrations
└── routes/api.php              # API routes
```

### Frontend (React 19 + Vite)

```
frontend/
├── src/
│   ├── components/
│   │   ├── BookingTable.jsx       # Main table with inline editing
│   │   ├── TicketWizard/          # 6-7 step wizard (dynamic)
│   │   │   ├── index.jsx          # Wizard orchestrator
│   │   │   ├── WizardProgress.jsx # Step indicator
│   │   │   ├── WizardNavigation.jsx # Back/Next/Send buttons
│   │   │   └── steps/
│   │   │       ├── Step1BookingDetails.jsx  # Read-only booking info
│   │   │       ├── Step2TicketReference.jsx # Reference number entry
│   │   │       ├── Step3FileAttach.jsx      # PDF upload
│   │   │       ├── Step4AudioGuide.jsx      # PopGuide generation (if audio)
│   │   │       ├── Step4TemplateSelect.jsx  # Language selection
│   │   │       ├── Step5Preview.jsx         # Message preview
│   │   │       └── Step6SendStatus.jsx      # Send result
│   │   ├── ManualSendModal.jsx
│   │   ├── DateNavigator.jsx
│   │   └── StatsCards.jsx
│   ├── pages/
│   │   ├── Dashboard.jsx          # Main booking view
│   │   ├── MessageHistory.jsx     # All sent messages
│   │   ├── ConversationsPage.jsx  # WhatsApp/SMS inbox
│   │   ├── TemplateAdmin.jsx      # Template management
│   │   ├── WebhookLogs.jsx        # Webhook admin
│   │   └── Login.jsx              # Authentication
│   ├── context/
│   │   ├── AuthContext.jsx        # Authentication state
│   │   └── ToastContext.jsx       # Notifications
│   ├── services/
│   │   └── api.js                 # Axios HTTP client + API facades
│   └── constants/
│       ├── bookingStatus.js
│       └── guidedTours.js
└── vite.config.js
```

---

## Key Domain Concepts

### Booking Types

| Type | Product ID | Can Send Tickets? | Has Audio Guide Option? |
|------|------------|-------------------|------------------------|
| Timed Entry | `961802` | Yes - via wizard | Yes (some bookings) |
| Guided Tours | `961801`, `962885`, `962886`, `1130528`, `1135055` | No | No |

### Audio Guide Detection

Bookings with audio guide have `has_audio_guide = true`. The wizard adds an extra step (Step 4: Audio Guide) to generate PopGuide access before sending tickets.

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

### Wizard Steps (Dynamic)

**Regular Bookings (6 steps):**
1. Booking Details (read-only)
2. Ticket Reference (enter reference number)
3. Attach PDF (upload ticket PDF, warns if filename doesn't match reference)
4. Select Language (10 languages: EN, IT, ES, DE, FR, PT, JA, KO, EL, TR)
5. Preview & Confirm (fetches actual template preview from API)
6. Send Status

**Audio Guide Bookings (7 steps):**
1. Booking Details (read-only)
2. Ticket Reference (enter reference number)
3. Attach PDF (upload ticket PDF, warns if filename doesn't match reference)
4. **Audio Guide** (generate PopGuide link) ← Extra step
5. Select Language (10 languages: EN, IT, ES, DE, FR, PT, JA, KO, EL, TR)
6. Preview & Confirm (fetches actual template preview from API)
7. Send Status

### Wizard Progress States

| State | Meaning |
|-------|---------|
| `null` | Not started |
| `in_progress` | Currently in wizard |
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
4. **PopGuide dynamic link required** if booking has audio guide (generated in Step 4)
5. **WhatsApp 24-hour window** - Can only send templated messages outside window
6. **Attachments must belong to booking** - Security check prevents sending wrong PDFs

---

## PopGuide/VOX Audio Guide Integration

### Overview

PopGuide (formerly VOX) provides audio guide services. When a booking includes audio guide, staff must generate a dynamic link before sending tickets.

### Environment Variables

```env
# PopGuide/VOX Audio Guide API (PRODUCTION)
VOX_BASE_URL=https://popguide.herokuapp.com
VOX_API_KEY=your_api_key
VOX_API_SECRET=your_api_secret
VOX_ENVIRONMENT=production
```

### Config (config/services.php)

```php
'vox' => [
    'api_key' => env('VOX_API_KEY'),
    'api_secret' => env('VOX_API_SECRET'),
    'base_url' => env('VOX_BASE_URL', 'https://popguide.herokuapp.com'),
    'environment' => env('VOX_ENVIRONMENT', 'production'),
],
```

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/bookings/{id}/create-vox-account` | POST | Generate PopGuide audio guide link |
| `/api/bookings/{id}/vox-status` | GET | Check if booking has PopGuide link |
| `/api/vox/test` | GET | Test PopGuide API connection |
| `/api/vox/accounts/{accountId}` | GET | Get PopGuide account details |

### VoxService Usage

```php
// In controller
public function createAccount(int $id, VoxService $voxService): JsonResponse
{
    $booking = Booking::findOrFail($id);

    // Check if booking has audio guide
    if (!$booking->hasAudioGuide()) {
        return response()->json(['error' => 'No audio guide'], 422);
    }

    // Create PopGuide account
    $result = $voxService->createAccount($booking);

    if ($result['success']) {
        return response()->json([
            'success' => true,
            'dynamic_link' => $result['dynamic_link'],
            'username' => $result['username'],
            'password' => $result['password'],
        ]);
    }

    return response()->json(['error' => $result['error']], 422);
}
```

### PopGuide API Flow

1. **Authentication**: Get Bearer token via `/partners_api/v3/sessions`
2. **Create Account**: POST to `/partners_api/v3/accounts` with:
   - `name`: Booking ID + Customer name
   - `qty`: Number of guests (PAX)
   - `payment_method`: "contract"
   - `termination_date`: Tour date + 7 days
   - `accesses`: Array with PopMap ID (698 for Uffizi)
3. **Response**: Contains `dynamic_link`, `username`, `password`

### Booking Fields for Audio Guide

| Field | Description |
|-------|-------------|
| `has_audio_guide` | Boolean - does booking include audio guide? |
| `vox_dynamic_link` | Generated PopGuide link (e.g., `https://pg.unlockmy.app/xxx`) |
| `vox_account_id` | PopGuide account ID |
| `audio_guide_username` | Optional: PopGuide username |
| `audio_guide_password` | Optional: PopGuide password |

---

## Message History Page

### Features

- **Auto-refresh**: 10-second interval with toggle (on/off)
- **Date grouping**: Messages grouped by Today, Yesterday, or full date
- **Expandable errors**: Click to show full error message
- **Pagination**: Page numbers, per-page selector (25/50/100)
- **Filtering**: By status, channel, search term
- **Stats cards**: Total, success rate, failed count, by channel

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/messages/all` | GET | Paginated message list |
| `/api/messages/stats` | GET | Message statistics |
| `/api/messages/{id}/details` | GET | Single message details |
| `/api/messages/{id}/retry` | POST | Retry failed message |

### Query Parameters

```
GET /api/messages/all?status=failed&channel=whatsapp&search=john&page=1&per_page=25
```

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
Route::get('/public/attachments/{id}/{signature}', [AttachmentController::class, 'servePublic']);

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

    // Booking CRUD
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/grouped', [BookingController::class, 'groupedByDate']);
    Route::get('/bookings/stats', [BookingController::class, 'stats']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::post('/bookings/{id}/wizard-progress', [BookingController::class, 'updateWizardProgress']);

    // Messaging
    Route::post('/bookings/{id}/send-ticket', [MessageController::class, 'sendTicket']);
    Route::get('/bookings/{id}/detect-channel', [MessageController::class, 'detectChannel']);
    Route::get('/bookings/{id}/messages', [MessageController::class, 'history']);
    Route::post('/messages/preview', [MessageController::class, 'preview']);
    Route::get('/messages/templates', [MessageController::class, 'templates']);

    // Message History (all messages view)
    Route::get('/messages/all', [MessageHistoryController::class, 'index']);
    Route::get('/messages/stats', [MessageHistoryController::class, 'stats']);
    Route::get('/messages/{id}/details', [MessageHistoryController::class, 'show']);
    Route::post('/messages/{id}/retry', [MessageHistoryController::class, 'retry']);

    // Attachments
    Route::post('/bookings/{id}/attachments', [AttachmentController::class, 'store']);
    Route::get('/bookings/{id}/attachments', [AttachmentController::class, 'index']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);

    // VOX/PopGuide Audio Guide
    Route::post('/bookings/{id}/create-vox-account', [VoxController::class, 'createAccount']);
    Route::get('/bookings/{id}/vox-status', [VoxController::class, 'getStatus']);
    Route::get('/vox/test', [VoxController::class, 'testConnection']);

    // Conversations (WhatsApp/SMS inbox)
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations/{id}/reply', [ConversationController::class, 'reply']);
    Route::put('/conversations/{id}/read', [ConversationController::class, 'markRead']);

    // Monitoring
    Route::prefix('monitoring')->group(function () {
        Route::get('/delivery-stats', [MonitoringController::class, 'deliveryStats']);
        Route::get('/failed-messages', [MonitoringController::class, 'failedMessages']);
        Route::get('/channel-health', [MonitoringController::class, 'channelHealth']);
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
  updateWizardProgress: (id, step, action) =>
    api.post(`/bookings/${id}/wizard-progress`, { step, action }),
};

export const messagesAPI = {
  sendTicket: (bookingId, data) => api.post(`/bookings/${bookingId}/send-ticket`, data),
  detectChannel: (bookingId) => api.get(`/bookings/${bookingId}/detect-channel`),
  preview: (data) => api.post('/messages/preview', data),
  history: (bookingId) => api.get(`/bookings/${bookingId}/messages`),
  templates: (params) => api.get('/messages/templates', { params }),
  // Message history (all messages)
  all: (params) => api.get('/messages/all', { params }),
  stats: () => api.get('/messages/stats'),
};

export const attachmentsAPI = {
  upload: (bookingId, formData) => api.post(`/bookings/${bookingId}/attachments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  list: (bookingId) => api.get(`/bookings/${bookingId}/attachments`),
  delete: (id) => api.delete(`/attachments/${id}`),
};

export const voxAPI = {
  createAccount: (bookingId) => api.post(`/bookings/${bookingId}/create-vox-account`),
  getStatus: (bookingId) => api.get(`/bookings/${bookingId}/vox-status`),
  testConnection: () => api.get('/vox/test'),
};

export const conversationsAPI = {
  list: (params) => api.get('/conversations', { params }),
  get: (id) => api.get(`/conversations/${id}`),
  reply: (id, content) => api.post(`/conversations/${id}/reply`, { content }),
  markRead: (id) => api.put(`/conversations/${id}/read`),
  unreadCount: () => api.get('/conversations/unread-count'),
};
```

## Wizard Component Pattern (Dynamic Steps)

```jsx
import { useState, useEffect, useMemo } from 'react';

// Dynamic steps based on booking type
const getSteps = (hasAudioGuide) => {
  const steps = [
    { id: 1, title: 'Booking Details' },
    { id: 2, title: 'Ticket Reference' },
    { id: 3, title: 'Attach PDF' },
  ];

  if (hasAudioGuide) {
    steps.push({ id: 4, title: 'Audio Guide' });
    steps.push({ id: 5, title: 'Select Language' });
    steps.push({ id: 6, title: 'Preview & Confirm' });
    steps.push({ id: 7, title: 'Send Status' });
  } else {
    steps.push({ id: 4, title: 'Select Language' });
    steps.push({ id: 5, title: 'Preview & Confirm' });
    steps.push({ id: 6, title: 'Send Status' });
  }

  return steps;
};

export default function TicketWizard({ booking, onClose, onComplete }) {
  const hasAudioGuide = booking.has_audio_guide;
  const STEPS = useMemo(() => getSteps(hasAudioGuide), [hasAudioGuide]);
  const totalSteps = STEPS.length;

  // Step mappings
  const STEP_LANGUAGE = hasAudioGuide ? 5 : 4;
  const STEP_PREVIEW = hasAudioGuide ? 6 : 5;
  const STEP_SEND = hasAudioGuide ? 7 : 6;
  const STEP_AUDIO = hasAudioGuide ? 4 : null;

  const [currentStep, setCurrentStep] = useState(1);
  const [wizardData, setWizardData] = useState({
    referenceNumber: booking.reference_number || '',
    attachments: [],
    language: 'en',
    voxDynamicLink: booking.vox_dynamic_link || null,
    hasVoxAccount: !!booking.vox_dynamic_link,
  });

  // Prevent accidental closure
  const handleOverlayClick = (e) => {
    if (e.target === e.currentTarget) {
      // Don't close on overlay click - require explicit X button
    }
  };

  const handleClose = () => {
    if (currentStep > 1) {
      const confirmClose = window.confirm(
        'Are you sure you want to close? Your progress will be lost.'
      );
      if (!confirmClose) return;
    }
    onClose();
  };

  // ... rest of wizard logic
}
```

## Auto-Refresh Pattern

```jsx
import { useState, useEffect, useCallback, useRef } from 'react';

export default function MessageHistory() {
  const [messages, setMessages] = useState([]);
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [lastRefresh, setLastRefresh] = useState(new Date());
  const refreshIntervalRef = useRef(null);

  const fetchMessages = useCallback(async (silent = false) => {
    if (!silent) setLoading(true);
    try {
      const response = await api.get('/messages/all', { params });
      setMessages(response.data.data || []);
      setLastRefresh(new Date());
    } catch (err) {
      if (!silent) showError('Failed to load messages');
    } finally {
      if (!silent) setLoading(false);
    }
  }, [params]);

  // Auto-refresh every 10 seconds
  useEffect(() => {
    if (autoRefresh) {
      refreshIntervalRef.current = setInterval(() => {
        fetchMessages(true); // silent refresh
      }, 10000);
    }
    return () => {
      if (refreshIntervalRef.current) {
        clearInterval(refreshIntervalRef.current);
      }
    };
  }, [autoRefresh, fetchMessages]);

  return (
    <div>
      <label>
        <input
          type="checkbox"
          checked={autoRefresh}
          onChange={(e) => setAutoRefresh(e.target.checked)}
        />
        Auto-refresh
      </label>
      <span>Updated: {lastRefresh.toLocaleTimeString()}</span>
    </div>
  );
}
```

---

# TWILIO MESSAGING PATTERNS

## WhatsApp Content Templates

WhatsApp messages use **Twilio Content Templates** with dynamic variables. Templates are configured in `config/whatsapp_templates.php`.

### Template Types

| Type | Description | Use Case |
|------|-------------|----------|
| `ticket_pdf` | Ticket without audio guide | Regular timed entry |
| `ticket_audio_pdf` | Ticket with audio guide | Timed entry + PopGuide |

### Template Variables

```
{{1}} - Customer name
{{2}} - Entry date/time (e.g., "February 1, 2026 at 10:00 AM")
{{3}} - Online guide URL or PopGuide dynamic link
{{4}} - Know before you go URL
{{5}} - PDF attachment URL (MUST be publicly accessible)
```

### CRITICAL: Dynamic Media URL

Templates MUST have `{{5}}` as dynamic media URL in Twilio Console:
```json
// CORRECT - Dynamic URL
"media": ["{{5}}"]

// WRONG - Hardcoded URL (will ignore contentVariables)
"media": ["https://bucket.s3.amazonaws.com/static-file.pdf"]
```

### Template SID Configuration

```php
// config/whatsapp_templates.php
return [
    'ticket_pdf' => [
        'en' => 'HXe99a2433d4e53e42ac5dca877eaa8851',
        'it' => 'HXe06570ac850a2549f46ec292d0276ebe',
        'de' => 'HX98b5b8c42b0e5c0c991d6a24b0bdcffe',
        'fr' => 'HX7d751e70ec0cbfba83b6dbad4dde95aa',
        // ... more languages
    ],
    'ticket_audio_pdf' => [
        'en' => 'HX1234567890abcdef...',
        // ... more languages
    ],
];
```

### Sending WhatsApp with Content Templates

```php
public function sendWhatsApp(Booking $booking, string $language, array $attachments): Message
{
    $phone = $this->formatPhoneNumber($booking->customer_phone);
    $hasAudioGuide = $booking->has_audio_guide;

    // Get PDF URL
    $pdfUrl = $attachments[0]->getTemporaryUrl();

    // Get Content Template SID
    $templateType = $hasAudioGuide ? 'ticket_audio_pdf' : 'ticket_pdf';
    $contentSid = config("whatsapp_templates.{$templateType}.{$language}");

    // Build template variables
    $contentVariables = [
        '1' => $booking->customer_name ?? 'Guest',
        '2' => $booking->tour_date->format('F j, Y') . ' at 10:00 AM',
        '3' => $hasAudioGuide ? $booking->vox_dynamic_link : 'https://guide.url',
        '4' => 'https://know-before-you-go.url',
        '5' => $pdfUrl,
    ];

    // Send via Twilio Content API
    $twilioMessage = $client->messages->create("whatsapp:{$phone}", [
        'from' => "whatsapp:{$this->whatsappFrom}",
        'contentSid' => $contentSid,
        'contentVariables' => json_encode($contentVariables),
    ]);

    return $message;
}
```

### Troubleshooting Error 63021

Error 63021 "Channel invalid content error" usually means:
1. Template SID is invalid or not approved
2. Template has hardcoded media URL instead of `{{5}}`
3. PDF URL is not publicly accessible
4. Variables don't match template placeholders

### Verifying Template Delivery

Twilio returns "sent" immediately but messages can fail later. To verify actual delivery:
1. Wait 15+ seconds after sending
2. Fetch message status from Twilio API
3. Check for "delivered" status, not just "sent"

---

# DATABASE CONVENTIONS

## Key Tables

| Table | Description |
|-------|-------------|
| `bookings` | Main booking data from Bokun |
| `messages` | Sent messages (WhatsApp/Email/SMS) |
| `message_attachments` | PDF files uploaded for bookings |
| `message_templates` | Email/SMS templates by language |
| `conversations` | WhatsApp/SMS conversation threads |
| `webhook_logs` | Bokun webhook history |
| `users` | Admin users |

## Booking Model Key Fields

```php
// Audio guide fields
$booking->has_audio_guide        // Boolean
$booking->vox_dynamic_link       // PopGuide link (e.g., https://pg.unlockmy.app/a1dfe584ef7647d1b)
$booking->vox_account_id         // PopGuide account ID (e.g., 4586207)
$booking->audio_guide_username   // Optional username (e.g., SMY-000410)
$booking->audio_guide_password   // Optional password (e.g., 90491)

// Wizard tracking
$booking->wizard_progress        // in_progress, abandoned, completed
$booking->wizard_step            // Current step number
$booking->wizard_started_at      // When wizard was opened
$booking->wizard_completed_at    // When ticket was sent
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
TWILIO_STATUS_CALLBACK_URL=https://uffizi.deetech.cc/api/webhooks/twilio/status

# Bokun
BOKUN_ACCESS_KEY=
BOKUN_SECRET_KEY=
BOKUN_BASE_URL=https://api.bokun.io

# AWS S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=

# PopGuide/VOX Audio Guide API (PRODUCTION)
VOX_BASE_URL=https://popguide.herokuapp.com
VOX_API_KEY=
VOX_API_SECRET=
VOX_ENVIRONMENT=production

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

# DEPLOYMENT

## Server Details

- **Hosting**: Hostinger shared hosting
- **PHP Version**: 8.2 (`/opt/alt/php82/usr/bin/php`)
- **SSH Port**: 65002
- **Path**: `~/domains/deetech.cc/public_html/uffizi/`

## Deploy Commands

```bash
# Frontend build
cd frontend && npm run build

# Upload to server
scp -P 65002 -r frontend/dist/* user@server:domains/deetech.cc/public_html/uffizi/

# Backend: Clear cache after .env changes
ssh -p 65002 user@server "cd domains/deetech.cc/public_html/uffizi/backend && /opt/alt/php82/usr/bin/php artisan config:clear && /opt/alt/php82/usr/bin/php artisan cache:clear"
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
8. **WhatsApp Content Templates** - Templates MUST use `{{5}}` for dynamic PDF URL
9. **Error 63021** - Usually means template has hardcoded media URL or is not approved
10. **pax_details JSON** - May come as string from API; frontend must parse with `JSON.parse()`
11. **S3 vs Local storage** - Check `config('services.aws.bucket')` to determine disk
12. **PopGuide tokens** - Cached for 23 hours, auto-refreshed
13. **Audio guide validation** - Wizard requires `vox_dynamic_link` (not username/password)
14. **Wizard overlay click** - Does NOT close wizard; must use X button

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

## Audio Guide Flow

1. Check `booking.has_audio_guide`
2. If true, wizard shows Step 4 (Audio Guide)
3. User clicks "Generate Audio Guide" → calls `voxAPI.createAccount()`
4. PopGuide returns `dynamic_link` → stored in `booking.vox_dynamic_link`
5. Ticket message includes PopGuide link in `{{3}}` variable

## Testing PopGuide Integration

```bash
# Test API connection
curl -X GET https://uffizi.deetech.cc/api/vox/test \
  -H "Authorization: Bearer YOUR_TOKEN"

# Check booking VOX status
curl -X GET https://uffizi.deetech.cc/api/bookings/123/vox-status \
  -H "Authorization: Bearer YOUR_TOKEN"

# Generate audio guide
curl -X POST https://uffizi.deetech.cc/api/bookings/123/create-vox-account \
  -H "Authorization: Bearer YOUR_TOKEN"
```
