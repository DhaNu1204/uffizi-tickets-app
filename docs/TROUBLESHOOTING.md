# Troubleshooting Guide - Uffizi Ticket App

This document covers common issues and their solutions for the Uffizi Ticket App.

## Common Issues

### 1. "The --limit option does not exist" error
**Cause**: Old `SyncBokunBookings.php` on server

**Fix**: Upload updated file from `backend/app/Console/Commands/SyncBokunBookings.php`

---

### 2. Auto-sync failing
**Diagnosis**:
```bash
tail -50 storage/logs/laravel.log
```

**Note**: The `autoSync()` method calls `bokun:sync --limit=50`

---

### 3. GetYourGuide bookings missing participant names
**Cause**: GYG bookings store participants in different locations than direct bookings

**Details**:
- The `extractParticipants()` method checks 5 different data structures
- Use debug command to inspect:
```bash
php artisan booking:debug GYG6H8LKF93A
```

---

### 4. Permission denied on laravel.log
**Fix**:
```bash
chmod 666 storage/logs/laravel.log
chmod 775 storage/logs/
```

---

### 5. Class "PDO" not found (Web PHP issue)
**Cause**: CLI PHP has PDO but Web PHP may not

**Fix**:
1. Check in Hostinger: PHP Configuration > Extensions > Enable PDO
2. Verify with test file:
```php
<?php echo class_exists('PDO') ? 'Yes' : 'No'; ?>
```

---

### 6. CORS errors in local development
**Cause**: Frontend port mismatch with CORS config

**Fix**:
1. Check which port the frontend is using (may be 5174/5175 if 5173 is busy)
2. Add the port to `backend/config/cors.php` allowed_origins:
```php
'allowed_origins' => [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost:5175',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5174',
    'http://127.0.0.1:5175',
    'https://uffizi.deetech.cc',
],
```
3. Clear cache: `php artisan config:clear`
4. Restart backend server

---

### 7. Customer email/phone not showing in modal
**Cause**: Data may not be populated for older bookings

**Fix**:
```bash
php artisan bokun:sync --full
```

**Note**: Airbnb bookings won't have contact info (Airbnb doesn't share it)

---

### 8. Audio guide badge not showing
**Cause**: After migration, existing bookings need backfill

**Fix**:
```bash
php artisan bookings:backfill-audio-guide --limit=1000
```

**Note**: Only applies to Timed Entry Tickets (product 961802). New bookings automatically detect audio guide during sync.

---

### 9. New product bookings not syncing
**Cause**: Product ID not in environment variable

**Fix**:
1. Check if product ID is in `UFFIZI_PRODUCT_IDS` in production `.env`
2. Clear config cache after updating:
```bash
php artisan config:clear
```
3. Run sync:
```bash
php artisan bokun:sync --limit=100
```

---

## Checking Server Logs

### Laravel Log
```bash
tail -100 storage/logs/laravel.log
```

### Check PHP Extensions (Web Context)
```bash
# Create test file
echo '<?php print_r(get_loaded_extensions()); ?>' > public/test.php

# Test via curl
curl https://uffizi.deetech.cc/backend/public/test.php

# Clean up (important!)
rm public/test.php
```

---

## Debug Tools (Development Only)

When `APP_DEBUG=true`, these endpoints are available:

| Endpoint | Purpose |
|----------|---------|
| `GET /api/bookings/test-bokun` | Test Bokun API connection |
| `GET /api/bookings/debug/{confirmationCode}` | Debug booking structure |
| `GET /api/bookings/raw/{confirmationCode}` | Raw Bokun API response |

**Web debug page** (DELETE AFTER USE):
- `backend/public/debug-booking.php` - Visual booking structure inspector

---

## Database Queries via SSH

When web API is unavailable, use SSH to query database directly:

```bash
# Connect to server
ssh -p 65002 u803853690@82.25.82.111

# Navigate to backend
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend

# Use artisan tinker for database queries
/opt/alt/php82/usr/bin/php artisan tinker
```

### Example Tinker Queries
```php
// Get all bookings with ticket references
App\Models\Booking::whereNotNull('reference_number')
    ->get(['bokun_booking_id', 'customer_name', 'reference_number', 'tour_date']);

// Get bookings for a specific date
App\Models\Booking::whereDate('tour_date', '2026-01-02')->get();

// Get bookings with status TICKET_PURCHASED
App\Models\Booking::where('status', 'TICKET_PURCHASED')->get();

// Count total bookings
App\Models\Booking::count();

// Check cancelled bookings
App\Models\Booking::onlyTrashed()->count();
App\Models\Booking::onlyTrashed()
    ->get(['bokun_booking_id', 'customer_name', 'cancelled_at']);

// Check audio guide bookings
App\Models\Booking::where('has_audio_guide', true)->count();
```

---

## Previous Issues (Resolved)

These issues have been fixed but are documented for reference:

| Issue | Resolution |
|-------|------------|
| PDO Extension Issue | Enabled PDO in Hostinger PHP configuration |
| Guide Name Not Saving | Updated Booking model `$fillable` array |
| Customer Email/Phone Missing | Updated BokunService and ran full sync |
| Product 1135055 Not Syncing | Added to `UFFIZI_PRODUCT_IDS` in production `.env` |
| Audio Guide Not Displaying | Ran backfill command after migration |
