# Uffizi Ticket App - Upgrade Implementation Plan

## STATUS: PRODUCTION ISSUE - LOCAL DEV ACTIVE

All code changes implemented. Production experiencing PDO extension issue (Jan 2026).
**Use local development environment until resolved.**

### Current State
- **Local Dev**: ✅ Working (http://127.0.0.1:5173)
- **Production**: ❌ 500 Error (PDO not found in web PHP context)

---

## SAFETY NOTICE - LIVE PRODUCTION ENVIRONMENT
**This is a live project deployed on Hostinger. All changes:**
- Do NOT delete or modify production database data
- Are backwards compatible
- Migration only ADDS an index (safe operation)
- Frontend changes are UI-only

---

## Changes Summary

### Phase 1: High Priority Security Fixes - COMPLETED

| Task | Status | Details |
|------|--------|---------|
| Remove `/setup-db` debug route | Done | Removed from `routes/api.php` |
| Add rate limiting | Done | Login: 5/min, API: 60/min, Sync: 2/min |
| Remove `nul` artifact files | Done | Deleted from root and backend/ |

### Phase 2: Medium Priority Performance - COMPLETED

| Task | Status | Details |
|------|--------|---------|
| Add composite DB index | Done | New migration created |
| Implement stats caching | Done | 60-second cache with auto-invalidation |
| Add React lazy loading | Done | Dashboard & WebhookLogs lazy loaded |

### Phase 3: Low Priority Code Quality - COMPLETED

| Task | Status | Details |
|------|--------|---------|
| Clean up debug PHP scripts | Done | 8 files deleted |
| Move products to config | Done | New `config/products.js` created |

### New Product Added - COMPLETED

| Task | Status | Details |
|------|--------|---------|
| Add Palazzo Vecchio Tour (1135055) | Done | New product added to system |
| Update Gelato Tour name (962885) | Done | Name corrected in config |
| Update production .env | Required | Add 1135055 to UFFIZI_PRODUCT_IDS |

---

## Files Changed

### Backend Files Modified:
```
backend/routes/api.php                                    - Security fixes
backend/app/Http/Controllers/BookingController.php       - Caching added
backend/database/migrations/2026_01_01_000001_*.php      - NEW: Index migration
```

### Backend Files Deleted:
```
backend/nul
backend/check_pax_details.php
backend/check_pax_details2.php
backend/check_pax_details3.php
backend/check_times.php
backend/check_all_bookings.php
backend/check_pagination.php
backend/check_timezone.php
backend/check_all_products.php
```

### Frontend Files Modified:
```
frontend/src/App.jsx                     - Lazy loading added
frontend/src/pages/Dashboard.jsx         - Uses config import
frontend/src/config/products.js          - NEW: Product configuration
```

### Root Files Deleted:
```
nul
```

---

## Deployment Instructions

### Step 1: Build Frontend Locally

```bash
cd frontend
npm install
npm run build
```

### Step 2: Upload Files to Hostinger

Upload these directories/files via SFTP or SSH:
```
backend/routes/api.php
backend/app/Http/Controllers/BookingController.php
backend/database/migrations/2026_01_01_000001_add_composite_index_to_bookings.php
frontend/dist/*  (to public_html/uffizi/)
```

### Step 3: SSH to Server and Run Migration

```bash
# Connect to Hostinger
ssh -p 65002 u803853690@82.25.82.111

# Navigate to backend
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend

# IMPORTANT: Update .env with new product ID
# Edit backend/.env and update UFFIZI_PRODUCT_IDS to:
# UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055

# Run the new migration (SAFE - only adds index)
/opt/alt/php82/usr/bin/php artisan migrate --force

# Clear caches
/opt/alt/php82/usr/bin/php artisan config:clear
/opt/alt/php82/usr/bin/php artisan cache:clear
/opt/alt/php82/usr/bin/php artisan route:clear
```

### Step 4: Verify Deployment

1. Open https://uffizi.deetech.cc in browser
2. Login and verify dashboard loads
3. Check that stats display correctly
4. Try syncing bookings (should work with rate limiting)
5. Check browser console for any errors

---

## Rollback Plan (If Needed)

### Rollback API Routes:
Keep a backup of the original `routes/api.php` before uploading.

### Rollback Database Index:
```bash
/opt/alt/php82/usr/bin/php artisan migrate:rollback --step=1
```

### Rollback Frontend:
Keep a backup of the previous `frontend/dist` folder.

---

## What Changed Technically

### 1. Rate Limiting (routes/api.php)
```php
// Login: 5 attempts per minute (brute force protection)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// API: 60 requests per minute
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // ...all protected routes
});

// Sync: 2 requests per minute (prevents API abuse)
Route::middleware('throttle:2,1')->group(function () {
    Route::post('/bookings/sync', ...);
});
```

### 2. Stats Caching (BookingController.php)
```php
// Stats are cached for 60 seconds
$stats = Cache::remember($cacheKey, 60, function () {
    // ... expensive queries
});

// Cache is cleared when bookings are updated
$this->clearStatsCache();
```

### 3. Lazy Loading (App.jsx)
```jsx
// Components loaded on demand
const Dashboard = lazy(() => import('./pages/Dashboard'));
const WebhookLogs = lazy(() => import('./pages/WebhookLogs'));

// With loading fallback
<Suspense fallback={<PageLoader />}>
  <Dashboard />
</Suspense>
```

### 4. Database Index Migration
```php
// Composite index for faster grouped queries
$table->index(['tour_date', 'status'], 'bookings_tour_date_status_index');
```

---

## Expected Improvements

| Metric | Before | After |
|--------|--------|-------|
| Security vulnerabilities | 2 | 0 |
| Stats query time | ~200ms | ~50ms (cached) |
| Initial JS bundle | 100% | ~70% (lazy loaded) |
| Debug files in production | 10 | 0 |

---

## Contact

For issues with deployment, check:
- Laravel logs: `backend/storage/logs/laravel.log`
- Browser console for frontend errors
- Server error logs in Hostinger panel

---

## Production Issue Log (Jan 2026)

### Issue: PDO Extension Not Found in Web Context
**Date Discovered**: January 2, 2026
**Status**: Pending Investigation

**Timeline**:
1. Production was working normally
2. Started getting 500 errors on login
3. Investigation revealed "Class PDO not found" error
4. CLI PHP has PDO loaded, but Web PHP does not
5. Set up local development as workaround

**Evidence**:
```
ERROR 500: Class "PDO" not found
Location: vendor/laravel/framework/src/Illuminate/Database/...
```

**What Works**:
- SSH access to server ✅
- CLI PHP commands (`php artisan`) ✅
- Local development environment ✅

**What Doesn't Work**:
- Web API requests ❌
- Frontend login ❌

**Next Steps**:
1. Check Hostinger PHP configuration in hPanel
2. Verify `.htaccess` PHP handler settings
3. Check if any recent Hostinger updates affected PHP extensions
4. Contact Hostinger support if needed

**Local Development Active**:
- Frontend: http://127.0.0.1:5173
- Backend: http://127.0.0.1:8000/api
- Database: 413 bookings cloned from production
