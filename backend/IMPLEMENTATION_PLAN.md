# Uffizi Ticket App - Comprehensive Implementation Plan

## Project Overview
Backend system to manage Uffizi Museum tour bookings, integrating with the Bokun booking management system.

---

## Phase 1: Security & Configuration Fixes (Priority: Critical)

### 1.1 Move API Credentials to Environment Variables
**Files to modify:**
- `.env` - Add Bokun credentials
- `.env.example` - Add template variables
- `app/Services/BokunService.php` - Read from config

**Environment variables to add:**
```env
BOKUN_ACCESS_KEY=2c413c402bd9402092b4a3f5157c899e
BOKUN_SECRET_KEY=89e772acd3324224a42918ac7562474c
BOKUN_BASE_URL=https://api.bokun.io
```

### 1.2 Implement Webhook HMAC Signature Verification
**Based on Bokun Documentation:**
1. Extract all headers starting with `X-Bokun-` (except `X-Bokun-HMAC`)
2. Transform header names to lowercase
3. Sort alphabetically by header name
4. Concatenate: `header1=value1&header2=value2`
5. Generate HMAC-SHA256 hash using API secret key
6. Compare hexdigest with `X-Bokun-HMAC` header

**File to modify:** `app/Http/Controllers/BookingController.php`

### 1.3 Add API Authentication Middleware
- Use Laravel Sanctum for API token authentication
- Protect all booking endpoints
- Keep webhook endpoint public (verified via HMAC)

### 1.4 Remove/Protect Debug Routes
- Remove `routes/debug.php` in production
- Add environment check for debug endpoints

---

## Phase 2: Database Enhancements

### 2.1 Add Missing Database Indexes
**New migration:** `add_indexes_to_bookings_table.php`
```php
$table->index('bokun_product_id');
$table->index('status');
$table->index('tour_date');
$table->index(['status', 'tour_date']);
```

### 2.2 Add Soft Deletes for Audit Trail
**Migration:** `add_soft_deletes_to_bookings_table.php`

### 2.3 Expand Booking Model with Additional Fields
**New fields needed:**
- `customer_email` - For notifications
- `customer_phone` - Contact info
- `booking_status` - Bokun status (CONFIRMED, PENDING, CANCELLED)
- `total_price` - Amount paid
- `currency` - Currency code
- `notes` - Internal notes
- `synced_at` - Last sync timestamp
- `bokun_confirmation_code` - Separate from booking ID

---

## Phase 3: Bokun API Integration Improvements

### 3.1 BokunService Enhancements

#### Historical Booking Import Method
```php
public function getHistoricalBookings($startDate, $endDate, $page = 1)
{
    // POST /booking.json/booking-search
    // Parameters:
    // - bookingRole: 'SELLER'
    // - bookingStatuses: ['CONFIRMED', 'PENDING', 'CANCELLED']
    // - startDateRange: { from, to }
    // - pageSize: 100
    // - page: $page
}
```

#### Fetch Single Booking Details
```php
public function getBookingDetails($confirmationCode)
{
    // GET /booking.json/booking/{confirmationCode}
}
```

#### Fetch Activity/Product Details
```php
public function getActivityDetails($activityId)
{
    // GET /activity.json/{id}
}
```

### 3.2 API Endpoints from Bokun (Reference)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/booking.json/booking-search` | POST | Search bookings with filters |
| `/booking.json/booking/{code}` | GET | Get booking by confirmation code |
| `/booking.json/activity-booking/{id}` | GET | Get activity booking details |
| `/activity.json/{id}` | GET | Get activity/product info |

### 3.3 Rate Limiting Consideration
- Bokun limit: **400 requests/minute** per vendor
- Implement queue-based sync for large imports
- Add delay between batch requests

---

## Phase 4: Historical Booking Import Feature

### 4.1 Create Import Command
**File:** `app/Console/Commands/ImportHistoricalBookings.php`

```php
php artisan bookings:import --from=2024-01-01 --to=2024-12-31
```

**Features:**
- Paginated API calls (100 per page)
- Progress bar in console
- Resume capability (track last imported date)
- Duplicate detection via confirmation code
- Error logging with retry logic

### 4.2 Import Service Class
**File:** `app/Services/BookingImportService.php`

**Methods:**
- `importDateRange($startDate, $endDate)` - Main import logic
- `processBookingBatch($bookings)` - Process array of bookings
- `mapBokunToLocal($bokunBooking)` - Map API response to model
- `handleImportError($error, $booking)` - Error handling

### 4.3 Background Job for Large Imports
**File:** `app/Jobs/ImportBookingsJob.php`
- Queue-based processing
- Chunked imports (by month)
- Email notification on completion

---

## Phase 5: Complete API Endpoints

### 5.1 New Endpoints to Add

| Route | Method | Description |
|-------|--------|-------------|
| `GET /api/bookings` | GET | List with pagination & filters |
| `GET /api/bookings/{id}` | GET | Single booking details |
| `POST /api/bookings` | POST | Manual booking creation |
| `PUT /api/bookings/{id}` | PUT | Update booking |
| `DELETE /api/bookings/{id}` | DELETE | Soft delete booking |
| `POST /api/bookings/sync` | POST | Manual sync from Bokun |
| `POST /api/bookings/import` | POST | Import historical bookings |
| `GET /api/bookings/stats` | GET | Dashboard statistics |

### 5.2 Filtering & Pagination
**Query parameters:**
- `status` - Filter by status
- `product_id` - Filter by Uffizi product
- `date_from` / `date_to` - Date range
- `search` - Customer name search
- `per_page` - Items per page (default 20)
- `page` - Page number

### 5.3 Response Format
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

## Phase 6: Webhook Improvements

### 6.1 Webhook Event Types to Handle
Based on Bokun webhooks:
- `bookings/created` - New booking
- `bookings/updated` - Booking modified
- `bookings/cancelled` - Booking cancelled
- `experiences/availability_update` - Availability changes

### 6.2 Webhook Payload Processing
**Improve date parsing:**
```php
// Handle various Bokun date formats
private function parseBokunDate($dateString)
{
    // "2025-01-15"
    // "2025-01-15T10:00:00"
    // "2025-01-15T10:00:00.000Z"
    return Carbon::parse($dateString);
}
```

### 6.3 Webhook Logging & Retry Queue
- Store raw webhook payloads in `webhook_logs` table
- Queue failed webhook processing for retry
- Alert on repeated failures

---

## Phase 7: Testing

### 7.1 Unit Tests
**Files to create:**
- `tests/Unit/Services/BokunServiceTest.php`
- `tests/Unit/Models/BookingTest.php`

### 7.2 Feature Tests
**Files to create:**
- `tests/Feature/BookingApiTest.php`
- `tests/Feature/WebhookTest.php`
- `tests/Feature/BookingImportTest.php`

### 7.3 Test Cases
- HMAC signature verification (valid/invalid)
- Booking CRUD operations
- Import with various date ranges
- Filter and pagination
- Duplicate handling

---

## Phase 8: Documentation & Deployment

### 8.1 API Documentation
- Create OpenAPI/Swagger specification
- Document all endpoints with examples

### 8.2 Environment Setup Guide
- Required environment variables
- Database setup instructions
- Bokun configuration steps

### 8.3 Deployment Checklist
- [ ] Remove debug routes
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up queue worker
- [ ] Configure webhook URL in Bokun
- [ ] Test HTTPS certificate for webhooks

---

## Implementation Order

### Week 1: Security & Foundation
1. ✅ Phase 1.1: Move credentials to .env
2. ✅ Phase 1.2: Webhook HMAC verification
3. ✅ Phase 2.1: Database indexes
4. ✅ Phase 2.2: Soft deletes

### Week 2: Historical Import
5. ✅ Phase 3.1: BokunService enhancements
6. ✅ Phase 4.1: Import command
7. ✅ Phase 4.2: Import service
8. ✅ Phase 4.3: Background jobs

### Week 3: API Completion
9. ✅ Phase 5.1: New endpoints
10. ✅ Phase 5.2: Filtering & pagination
11. ✅ Phase 6: Webhook improvements

### Week 4: Testing & Documentation
12. ✅ Phase 7: Testing
13. ✅ Phase 8: Documentation

---

## Bokun API Quick Reference

### Authentication Headers
```
X-Bokun-Date: 2025-01-15 10:30:00 (UTC)
X-Bokun-AccessKey: your-access-key
X-Bokun-Signature: base64(HMAC-SHA1(date+accessKey+method+path, secretKey))
Content-Type: application/json;charset=UTF-8
```

### Booking Search Request Body
```json
{
  "bookingRole": "SELLER",
  "bookingStatuses": ["CONFIRMED", "PENDING", "CANCELLED"],
  "startDateRange": {
    "from": "2024-01-01T00:00:00.000Z",
    "to": "2025-12-31T23:59:59.999Z"
  },
  "pageSize": 100,
  "page": 1
}
```

### Uffizi Product IDs
- 961802
- 961801
- 962885
- 962886
- 1130528

---

## Sources & Documentation
- [Bokun Developer Docs](https://bokun.dev/)
- [Bokun API Reference](https://api-docs.bokun.dev/)
- [Bokun REST API v1](https://api-docs.bokun.dev/rest-v1)
- [Webhook Documentation](https://bokun.dev/webhooks/g3YWZ24sADsceKK5vqrMzZ/creating-an-endpoint-for-webhooks/fhyXqzU4KXuLWc7Dc8ioNU)
- [Authentication Guide](https://bokun.dev/booking-api-rest/vU6sCfxwYdJWd1QAcLt12i/configuring-the-platform-for-api-usage-and-authentication/sFiGRpo4detkmrZPcWtQPj)
