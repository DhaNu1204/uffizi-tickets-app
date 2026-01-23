# Claude Code Context - Uffizi Ticket App

## Project Overview
A ticket management dashboard for Uffizi Gallery tours. Syncs bookings from Bokun API and tracks Uffizi ticket purchases.

**Live URL**: https://uffizi.deetech.cc
**GitHub**: https://github.com/DhaNu1204/uffizi-tickets-app

## Tech Stack
- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: React 19 + Vite
- **Database**: MySQL
- **API Integration**: Bokun booking system
- **Error Tracking**: Sentry (frontend)
- **CI/CD**: GitHub Actions

## Project Structure
```
/Uffizi-Ticket-App
├── backend/           # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── BookingController.php    # Main CRUD + sync
│   │   │   ├── AuthController.php       # Login/logout
│   │   │   └── WebhookController.php    # Webhook management
│   │   ├── Models/
│   │   │   └── Booking.php              # Booking model
│   │   └── Services/
│   │       └── BokunService.php         # Bokun API integration
│   ├── routes/api.php                   # API routes
│   └── database/migrations/             # DB schema
├── frontend/          # React SPA
│   ├── src/
│   │   ├── components/
│   │   │   └── BookingTable.jsx         # Main booking table
│   │   ├── pages/
│   │   │   └── Dashboard.jsx            # Dashboard page
│   │   └── services/
│   │       └── api.js                   # API client
│   └── dist/                            # Production build
└── deploy/            # Deployment files
```

## Key Features
1. **Bokun Sync**: Fetches confirmed bookings for Uffizi products
2. **Ticket Tracking**: Mark bookings as TICKET_PURCHASED with reference numbers
3. **Tickets Sent Tracking**: Track when tickets are sent to clients (with timestamp)
4. **Notes**: Add notes to individual bookings
5. **Product Filter**: Filter by ticket type (Entry, Group Tour, VIP, etc.)
6. **PAX Details**: Shows passenger breakdown (2x Adult, 1x Child)
7. **Webhooks**: Real-time booking updates from Bokun
8. **Daily Pagination**: View one day's bookings per page with date navigation
9. **Calendar Picker**: Visual calendar with booking count indicators for each day
10. **Time Slot Grouping**: Bookings grouped by tour time within each day
11. **Participant Names**: Shows individual participant names from Bokun
12. **Auto-Sync**: Automatically syncs on dashboard load
13. **Cancellation Detection**: Detects cancelled bookings during sync
14. **Florence Timezone**: Uses Europe/Rome timezone for "today" calculation
15. **Guide Assignment**: Assign guides to guided tour bookings
16. **Booking Channel**: Tracks booking source (GetYourGuide, Viator, Direct, etc.)
17. **Customer Contact**: Stores customer email and phone from bookings
18. **Audio Guide Tracking**: Detects and displays audio guide bookings for Timed Entry tickets
19. **Click-to-Copy References**: Click ticket reference numbers to copy to clipboard

## Bokun Product IDs
- `961802`: Timed Entry Tickets
- `961801`: Small Group Guided Tour
- `962885`: Uffizi, David Tour & Gelato with Art Historian
- `962886`: VIP Private Tour
- `1130528`: Guided Tour + Vasari
- `1135055`: Florence Uffizi Gallery Tour with Palazzo Vecchio Entry

## Database Schema (bookings table)
| Column | Type | Description |
|--------|------|-------------|
| bokun_booking_id | varchar | Unique Bokun ID |
| bokun_product_id | varchar | Product type |
| booking_channel | varchar | Source (GetYourGuide, Viator, Direct, etc.) |
| product_name | varchar | Tour/ticket name |
| customer_name | varchar | Customer name |
| customer_email | varchar | Customer email address |
| customer_phone | varchar | Customer phone number |
| tour_date | datetime | Tour date/time (Florence local stored as UTC) |
| pax | int | Total passengers |
| pax_details | json | Breakdown by type (e.g., {"Adult": 2, "Child": 1}) |
| participants | json | Individual participant names with types |
| status | varchar | PENDING_TICKET or TICKET_PURCHASED |
| reference_number | varchar | Uffizi confirmation code |
| notes | text | Operator notes |
| guide_name | varchar | Assigned guide for guided tours |
| cancelled_at | timestamp | When booking was cancelled (null if active) |
| tickets_sent_at | timestamp | When tickets were sent to client (null if not sent) |
| has_audio_guide | boolean | Whether booking includes audio guide (Timed Entry only) |
| audio_guide_sent_at | timestamp | When audio guide link was sent to client |

## API Endpoints
- `POST /api/login` - Authentication
- `GET /api/bookings/grouped` - Bookings grouped by date with time slots
- `GET /api/bookings/stats` - Dashboard statistics
- `PUT /api/bookings/{id}` - Update booking (reference, notes)
- `POST /api/bookings/sync` - Full sync from Bokun
- `POST /api/bookings/auto-sync` - Auto-sync (fetches participant names for pending bookings)
- `POST /api/webhook/bokun` - Webhook receiver

## Environment Variables
```env
# Backend (.env)
BOKUN_ACCESS_KEY=xxx
BOKUN_SECRET_KEY=xxx
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055

# Frontend (.env.production)
VITE_API_URL=https://uffizi.deetech.cc/api
```

## Production Deployment (Hostinger)

### Server Details
- **URL**: https://uffizi.deetech.cc
- **Host**: Hostinger Shared Hosting
- **SSH**: `ssh -p 65002 u803853690@82.25.82.111`
- **PHP Path**: `/opt/alt/php82/usr/bin/php`

### Directory Structure
```
/home/u803853690/domains/deetech.cc/public_html/uffizi/
├── index.html          # Frontend entry
├── assets/             # Frontend JS/CSS
├── .htaccess           # Routing rules
└── backend/            # Laravel API
    ├── .env
    ├── storage/
    └── ...
```

### Database
- **Host**: localhost
- **Database**: u803853690_uffizi_tickets
- **Username**: u803853690_uffizi

### SSH Commands
```bash
# Navigate to backend
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend

# Run artisan commands (must use PHP 8.2)
/opt/alt/php82/usr/bin/php artisan migrate
/opt/alt/php82/usr/bin/php artisan config:clear
/opt/alt/php82/usr/bin/php artisan cache:clear

# Create user via tinker
/opt/alt/php82/usr/bin/php artisan tinker
```

### Important Configuration Notes
- **Authentication**: Uses Bearer token auth (NOT cookie-based)
- **bootstrap/app.php**: Do NOT use `statefulApi()` middleware (causes CSRF issues with token auth)
- **Exception Handling**: API routes return JSON 401 for unauthenticated requests

## Common Tasks
- **Sync bookings**: Click "Sync Bokun" button (auto-syncs on page load)
- **Add ticket reference**: Click "Add Ticket" → Enter Uffizi code → Status changes to "Purchased"
- **Mark tickets sent**: After purchasing, click "Send" button → Changes to green "Sent" with timestamp
- **Mark audio guide sent**: For audio guide bookings, click "Audio Sent" button after sending the link
- **Copy reference number**: Click on any ticket reference number to copy to clipboard (shows "Copied!" confirmation)
- **Filter by product**: Use dropdown in filters bar
- **Navigate dates**: Use prev/next arrows or click date to open calendar
- **View future bookings**: Open calendar to see booking counts per day
- **Go to today**: Click "Go to Today" button when viewing other dates

## Workflow
1. View bookings for the day
2. Purchase tickets from Uffizi B2B account
3. Click "Add Ticket" → Enter Uffizi reference number
4. Add notes if needed
5. Send tickets to client via WhatsApp/Email
6. Click "Send" button to mark as sent (tracks timestamp)
7. For audio guide bookings (purple badge): Send audio guide link, then click "Audio Sent"

### Audio Guide Workflow
1. Look for purple **"Audio Guide"** badge on Timed Entry bookings
2. After purchasing ticket, send the audio guide access link to client
3. Click "Audio Sent" button to track that link was sent
4. Button changes to solid purple with timestamp

## Time Zone Note
- **Backend**: Bokun stores Florence local time as UTC in the database
- **Frontend**: Uses `Europe/Rome` timezone for "today" calculation
- **Date Display**: Times shown as-is (UTC) to match Bokun dashboard
- **Auto-Update**: Dashboard auto-updates at midnight Florence time

## Direct Database Access via SSH

When web API is unavailable, use SSH to query database directly:

```bash
# Connect to server
ssh -p 65002 u803853690@82.25.82.111

# Navigate to backend
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend

# Use artisan tinker for database queries
/opt/alt/php82/usr/bin/php artisan tinker

# Example queries in tinker:
# Get all bookings with ticket references
App\Models\Booking::whereNotNull('reference_number')->get(['bokun_booking_id', 'customer_name', 'reference_number', 'tour_date']);

# Get bookings for a specific date
App\Models\Booking::whereDate('tour_date', '2026-01-02')->get();

# Get bookings with status TICKET_PURCHASED
App\Models\Booking::where('status', 'TICKET_PURCHASED')->get();

# Count total bookings
App\Models\Booking::count();
```

## Artisan Commands

```bash
# Sync bookings from Bokun (with participant fetch limit)
/opt/alt/php82/usr/bin/php artisan bokun:sync --limit=50

# Full sync (fetch ALL missing participants)
/opt/alt/php82/usr/bin/php artisan bokun:sync --full

# Backfill audio guide info for existing Timed Entry bookings
/opt/alt/php82/usr/bin/php artisan bookings:backfill-audio-guide --limit=100

# Backfill all (no limit)
/opt/alt/php82/usr/bin/php artisan bookings:backfill-audio-guide --limit=1000

# Dry run (preview without making changes)
/opt/alt/php82/usr/bin/php artisan bookings:backfill-audio-guide --dry-run

# Debug booking structure (for troubleshooting participant extraction)
/opt/alt/php82/usr/bin/php artisan booking:debug GYG6H8LKF93A

# Clear all caches
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

## Debug Tools (Development Only)

When APP_DEBUG=true, these endpoints are available:
- `GET /api/bookings/test-bokun` - Test Bokun API connection
- `GET /api/bookings/debug/{confirmationCode}` - Debug booking structure
- `GET /api/bookings/raw/{confirmationCode}` - Raw Bokun API response

Web debug page (DELETE AFTER USE):
- `backend/public/debug-booking.php` - Visual booking structure inspector

## Cancellation Handling

Cancelled bookings are automatically detected and removed from the dashboard.

### How It Works
1. **During Sync** (`bokun:sync`):
   - Compares DB bookings with Bokun API results
   - If a booking is in DB but NOT in API, checks Bokun status
   - If status = `CANCELLED`, sets `cancelled_at` and soft-deletes

2. **Via Webhook** (real-time):
   - Bokun sends cancellation webhook to `/api/webhook/bokun`
   - System immediately soft-deletes the booking

3. **Query Filtering**:
   - All API queries automatically exclude soft-deleted bookings (Laravel SoftDeletes)
   - Cancelled bookings never appear in the dashboard

### Check Cancelled Bookings
```bash
# Via tinker
php artisan tinker
App\Models\Booking::onlyTrashed()->count();  # Count soft-deleted
App\Models\Booking::onlyTrashed()->get(['bokun_booking_id', 'customer_name', 'cancelled_at']);
```

## Audio Guide Feature

Audio guide tracking is available for **Timed Entry Tickets** (product ID `961802`) only.

### How It Works

1. **Detection**: During sync, the system checks for rate code `TG2` or rate ID `2263305` which indicates audio guide inclusion
2. **Display**: Bookings with audio guides show a purple **"Audio Guide"** badge with pulsing animation
3. **Tracking**: After purchasing tickets, click "Audio Sent" button to mark when the audio guide link was sent

### Audio Guide Rate Identifiers
- **Rate ID**: `2263305`
- **Rate Code**: `TG2`
- **Product**: Timed Entry Tickets (`961802`) only

### Database Fields
| Column | Type | Description |
|--------|------|-------------|
| has_audio_guide | boolean | True if booking includes audio guide |
| audio_guide_sent_at | timestamp | When audio guide link was sent |

### Backfill Existing Bookings

After deploying the audio guide feature, run the backfill command to update existing bookings:

```bash
# Check how many need backfill
/opt/alt/php82/usr/bin/php artisan tinker --execute="echo App\Models\Booking::where('bokun_product_id', '961802')->count();"

# Run backfill (processes bookings and calls Bokun API)
/opt/alt/php82/usr/bin/php artisan bookings:backfill-audio-guide --limit=500

# Preview without making changes
/opt/alt/php82/usr/bin/php artisan bookings:backfill-audio-guide --dry-run --limit=50
```

### Check Audio Guide Bookings
```bash
# Via MySQL
mysql -u u803853690_uffizi -p u803853690_uffizi_tickets -e "SELECT COUNT(*) as total, SUM(has_audio_guide) as with_audio FROM bookings WHERE bokun_product_id = '961802';"

# Via tinker
php artisan tinker
App\Models\Booking::where('has_audio_guide', true)->count();
App\Models\Booking::where('has_audio_guide', true)->where('tour_date', '>=', now())->get(['bokun_booking_id', 'tour_date']);
```

### Frontend Display
- **Badge**: Purple gradient with pulsing animation (visible in booking row)
- **Button**: "Audio Sent" button appears after ticket is purchased
- **States**:
  - Not sent: Purple outline button
  - Sent: Solid purple button with timestamp on hover

## Update Ticket Modal

The "Update Ticket" popup displays:
- Customer name
- **Customer email** (clickable mailto link)
- **Customer phone** (clickable tel link)
- Tour name and date
- PAX details and participant names
- Ticket reference input
- Guide assignment (for guided tours)
- Notes field

**Note**: Email/phone data is fetched during sync. Run `bokun:sync --full` to populate missing contact info.

## Troubleshooting

### Common Issues

**1. "The --limit option does not exist" error**
- Cause: Old `SyncBokunBookings.php` on server
- Fix: Upload updated file from `backend/app/Console/Commands/SyncBokunBookings.php`

**2. Auto-sync failing**
- Check: `tail -50 storage/logs/laravel.log`
- The `autoSync()` method calls `bokun:sync --limit=50`

**3. GetYourGuide bookings missing participant names**
- GYG bookings store participants in different locations than direct bookings
- The `extractParticipants()` method checks 5 different data structures
- Use debug command to inspect: `php artisan booking:debug GYG6H8LKF93A`

**4. Permission denied on laravel.log**
```bash
chmod 666 storage/logs/laravel.log
chmod 775 storage/logs/
```

**5. Class "PDO" not found (Web PHP issue)**
- CLI PHP has PDO but Web PHP may not
- Check in Hostinger: PHP Configuration → Extensions → Enable PDO
- Verify: Create test.php with `<?php echo class_exists('PDO') ? 'Yes' : 'No'; ?>`

**6. CORS errors in local development**
- Check which port the frontend is using (may be 5174/5175 if 5173 is busy)
- Add the port to `backend/config/cors.php` allowed_origins
- Clear cache: `php artisan config:clear`
- Restart backend server

**7. Customer email/phone not showing in modal**
- Data may not be populated for older bookings
- Run: `php artisan bokun:sync --full` to fetch all contact info
- Airbnb bookings won't have contact info (Airbnb doesn't share it)

**8. Audio guide badge not showing**
- After migration, existing bookings need backfill
- Run: `php artisan bookings:backfill-audio-guide --limit=1000`
- Only applies to Timed Entry Tickets (product 961802)
- New bookings automatically detect audio guide during sync

**9. New product bookings not syncing**
- Check if product ID is in `UFFIZI_PRODUCT_IDS` in production `.env`
- Clear config cache after updating: `php artisan config:clear`
- Run sync: `php artisan bokun:sync --limit=100`

### Checking Server Logs
```bash
# Laravel log
tail -100 storage/logs/laravel.log

# Check PHP extensions in web context
echo '<?php print_r(get_loaded_extensions()); ?>' > public/test.php
curl https://uffizi.deetech.cc/backend/public/test.php
rm public/test.php
```

## Files Modified in Recent Updates

### Backend Changes (Jan 2026)
- `routes/api.php` - Added rate limiting, removed debug routes
- `app/Http/Controllers/BookingController.php` - Added caching, improved sync, cancellation handling, audio guide toggle
- `app/Services/BokunService.php` - Enhanced participant extraction, customer contact extraction, `extractHasAudioGuide()` method
- `app/Console/Commands/SyncBokunBookings.php` - Added --limit option, cancellation detection, audio guide detection during sync
- `app/Console/Commands/BackfillAudioGuide.php` - NEW command for backfilling audio guide info
- `app/Console/Commands/DebugBookingStructure.php` - NEW debug command
- `app/Models/Booking.php` - Added `has_audio_guide`, `audio_guide_sent_at` fields
- `config/cors.php` - Updated CORS allowed origins for local development (ports 5173-5175)
- `database/migrations/2026_01_01_000001_add_composite_index_to_bookings.php` - NEW index
- `database/migrations/2026_01_04_*` - Added booking_channel, guide_name, customer_email, customer_phone
- `database/migrations/2026_01_23_201052_add_audio_guide_to_bookings_table.php` - NEW audio guide columns

### Frontend Changes
- `src/main.jsx` - Sentry initialization and ErrorBoundary wrapper
- `src/App.jsx` - Added lazy loading
- `src/config/products.js` - NEW product configuration file
- `src/pages/Dashboard.jsx` - Uses product config
- `src/components/BookingTable.jsx` - Audio guide badge & button, click-to-copy reference numbers, customer email/phone display
- `src/components/BookingTable.css` - Purple audio guide badge with pulsing animation, click-to-copy styles
- `.env.production` - Added `VITE_SENTRY_DSN`
- `.env.example` - Added Sentry placeholder
- `package.json` - Added `@sentry/react` dependency

### Latest Deployment (Jan 23, 2026)
- Built frontend: `index-Da--Kl2H.js` (with Sentry), `index-DHlzghEP.css`
- Added Sentry error tracking to frontend
- Added product ID `1135055` to production `.env`
- Ran audio guide backfill (42 bookings with audio guide detected)
- Database backup: `backup_20260123.sql`
- All caches cleared on production
- GYG API researched (not implemented - documented for future)

## Rate Limiting (Production)
- Login: 5 requests per minute
- API: 60 requests per minute
- Sync/Import: 2 requests per minute

---

## Local Development Setup

### Prerequisites
- PHP 8.2+
- MySQL 8.0 (installed at `C:/Program Files/MySQL/MySQL Server 8.0/`)
- Node.js 18+
- Composer

### Local Database
- **Host**: 127.0.0.1
- **Port**: 3306
- **Database**: uffizi_tickets
- **Username**: root
- **Password**: `RL94_#BbiLhuy789xF`

### Quick Start
```bash
# 1. Start MySQL (should be running as Windows service)

# 2. Start Laravel backend
cd D:/Uffizi-Ticket-App/backend
php artisan serve --host=127.0.0.1 --port=8000

# 3. Start React frontend (new terminal)
cd D:/Uffizi-Ticket-App/frontend
npm run dev -- --port 5173 --host 127.0.0.1
```

### Local URLs
| Service | URL |
|---------|-----|
| Frontend | http://127.0.0.1:5173 |
| Backend API | http://127.0.0.1:8000/api |

### Environment Files
**Backend** (`backend/.env`):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uffizi_tickets
DB_USERNAME=root
DB_PASSWORD="RL94_#BbiLhuy789xF"
```

**Frontend** (`frontend/.env`):
```env
VITE_API_URL=http://localhost:8000/api
```

### CORS Configuration (Local Development)
The backend CORS config (`backend/config/cors.php`) must include your frontend port:
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

**If you get CORS errors:**
1. Check which port the frontend is running on (Vite may use 5174/5175 if 5173 is busy)
2. Add that port to `allowed_origins` in `backend/config/cors.php`
3. Clear config cache: `php artisan config:clear`
4. Restart the backend server

### Clone Database from Production
```bash
# 1. Export from Hostinger via SSH
ssh -p 65002 u803853690@82.25.82.111
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend
mysqldump -u u803853690_uffizi -p u803853690_uffizi_tickets > backup.sql

# 2. Download to local
scp -P 65002 u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/backend/backup.sql ./

# 3. Clean the file (remove first 2 lines if MariaDB warnings present)
tail -n +3 backup.sql > backup_clean.sql

# 4. Import to local MySQL
"C:/Program Files/MySQL/MySQL Server 8.0/bin/mysql.exe" -u root -p"RL94_#BbiLhuy789xF" uffizi_tickets < backup_clean.sql
```

### MySQL Commands (Local)
```bash
# Connect to local MySQL
"C:/Program Files/MySQL/MySQL Server 8.0/bin/mysql.exe" -u root -p"RL94_#BbiLhuy789xF" uffizi_tickets

# Quick queries
"C:/Program Files/MySQL/MySQL Server 8.0/bin/mysql.exe" -u root -p"RL94_#BbiLhuy789xF" uffizi_tickets -e "SELECT COUNT(*) FROM bookings;"
```

---

## Backup Files

| File | Description |
|------|-------------|
| `D:/Uffizi-Ticket-App/uffizi_backup.sql` | Raw database export from Hostinger |
| `D:/Uffizi-Ticket-App/uffizi_backup_clean.sql` | Cleaned SQL (MariaDB warnings removed) |
| `D:/Uffizi-Ticket-App/all_bookings_backup.json` | Complete booking data in JSON format |

---

## GitHub Repository

**URL**: https://github.com/DhaNu1204/uffizi-tickets-app

### CI/CD Pipeline (GitHub Actions)

The repository has automated CI/CD via `.github/workflows/ci.yml`:

| Job | Description |
|-----|-------------|
| `backend-tests` | PHPUnit tests on PHP 8.2 & 8.3 with MySQL |
| `backend-lint` | PHP syntax check |
| `frontend-build` | npm install, lint, build React app |
| `security-check` | Composer & NPM security audits |

**Triggers**: Push/PR to `main`, `master`, `develop` branches

### Git Workflow

```bash
# Make changes locally
cd D:/Uffizi-Ticket-App

# Commit and push
git add .
git commit -m "Your message"
git push origin master:main

# CI runs automatically - check status at:
# https://github.com/DhaNu1204/uffizi-tickets-app/actions
```

---

## Production Status

**Status**: OPERATIONAL (Last deployed: Jan 23, 2026)

The production server at https://uffizi.deetech.cc is fully functional.

### Current Stats
- **Database**: 609+ bookings
- **Timed Entry Bookings**: 522
- **With Audio Guide**: 42 (8%)
- **Frontend Build**: `index-Da--Kl2H.js` (with Sentry)
- **Error Tracking**: Sentry enabled

### Previous Issues (Resolved)
- **PDO Extension Issue**: Resolved by enabling PDO in Hostinger PHP configuration
- **Guide Name Not Saving**: Fixed by updating Booking model `$fillable` array
- **Customer Email/Phone Missing**: Fixed by updating BokunService and running full sync
- **Product 1135055 Not Syncing**: Fixed by adding to `UFFIZI_PRODUCT_IDS` in production `.env`
- **Audio Guide Not Displaying**: Fixed by running backfill command after migration

### Pending / Future Work
- **GYG Direct Integration**: Researched but not implemented (see GYG API section below)
- **Participant Names for GYG Bookings**: Would require full GYG integration

---

## Deployment Procedure

### Quick Deploy (from Windows)

```bash
# 1. Build frontend
cd D:/Uffizi-Ticket-App/frontend
npm run build

# 2. Backup production database
ssh -p 65002 u803853690@82.25.82.111 "cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && mysqldump -u u803853690_uffizi -p[PASSWORD] u803853690_uffizi_tickets > backup_$(date +%Y%m%d_%H%M%S).sql"

# 3. Upload backend changes (example: cors.php)
scp -P 65002 D:/Uffizi-Ticket-App/backend/config/cors.php u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/backend/config/

# 4. Upload frontend build
scp -P 65002 D:/Uffizi-Ticket-App/frontend/dist/index.html u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/
scp -P 65002 -r D:/Uffizi-Ticket-App/frontend/dist/assets/* u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/assets/

# 5. Run migrations and clear cache
ssh -p 65002 u803853690@82.25.82.111 "cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && /opt/alt/php82/usr/bin/php artisan migrate --force && /opt/alt/php82/usr/bin/php artisan optimize:clear"

# 6. Verify deployment
ssh -p 65002 u803853690@82.25.82.111 "curl -s -o /dev/null -w '%{http_code}' https://uffizi.deetech.cc/"
```

### Deployment Checklist

| Step | Command/Action |
|------|----------------|
| Build frontend | `npm run build` |
| Backup database | `mysqldump` via SSH |
| Upload backend | `scp -P 65002` changed PHP files |
| Upload frontend | `scp -P 65002` dist/index.html + assets |
| Run migrations | `php artisan migrate --force` |
| Clear caches | `php artisan optimize:clear` |
| Verify | Test API and frontend load |

### Rollback Procedure

If deployment fails:
```bash
# 1. Restore database from backup
ssh -p 65002 u803853690@82.25.82.111
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend
mysql -u u803853690_uffizi -p u803853690_uffizi_tickets < backup_YYYYMMDD_HHMMSS.sql

# 2. Clear caches
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

---

## Sentry Error Tracking

Frontend errors are tracked using Sentry for production monitoring.

### Configuration
- **Package**: `@sentry/react`
- **DSN**: Stored in `frontend/.env.production` as `VITE_SENTRY_DSN`
- **Environment**: Auto-detects `development` / `production`
- **Enabled**: Only in production builds (`import.meta.env.PROD`)

### Features
| Feature | Setting |
|---------|---------|
| Error Boundary | Wraps entire app with fallback UI |
| Performance Monitoring | 10% transaction sampling |
| Session Replay | 100% of error sessions recorded |
| PII Collection | Enabled (`sendDefaultPii: true`) |

### Files
- `frontend/src/main.jsx` - Sentry initialization and ErrorBoundary
- `frontend/.env.production` - Contains `VITE_SENTRY_DSN`
- `frontend/.env.example` - Template with placeholder

### Testing Sentry
To verify Sentry is working, open browser console on production and run:
```javascript
Sentry.captureMessage("Test message from Uffizi app");
```

### Sentry Dashboard
Access at: https://sentry.io (login required)

---

## GetYourGuide API Integration (Future Development)

**Status**: RESEARCHED - Not implemented (Jan 2026)

### Background
GetYourGuide (GYG) bookings come through Bokun but are missing participant names. Direct GYG integration was researched to supplement this data.

### Key Finding
**The GYG Supplier API does NOT have an endpoint to FETCH booking details.**

The API is push-based:
- GYG pushes bookings TO your system (via `/1/book/`)
- You push availability TO GYG (via `/1/notify-availability-update`)

### GYG API Endpoints (Available)
| Endpoint | Direction | Purpose |
|----------|-----------|---------|
| `/1/notify-availability-update` | You → GYG | Push availability changes |
| `/1/deals` | You → GYG | Manage promotional deals |
| `/1/redeem-ticket` | You → GYG | Mark ticket as used |
| `/1/redeem-booking` | You → GYG | Mark booking as used |
| `/1/suppliers` | You → GYG | Register new suppliers |
| `/1/products/{id}/activate` | You → GYG | Reactivate deactivated products |

### GYG API Endpoints (NOT Available)
- ❌ `/1/bookings` - Does not exist
- ❌ `/1/orders` - Does not exist
- ❌ `/1/booking/{reference}` - Does not exist

### To Receive GYG Bookings Directly
Would require implementing these supplier-side endpoints:

| Endpoint | Purpose | Required |
|----------|---------|----------|
| `/1/get-availabilities/` | GYG queries your availability | Yes |
| `/1/reserve/` | Hold spots temporarily (60 min) | Yes |
| `/1/cancel-reservation/` | Release held spots | Yes |
| `/1/book/` | Confirm booking (includes traveler names!) | Yes |
| `/1/cancel-booking/` | Handle cancellations | Yes |

### GYG Credentials (Florence with Locals)

**Integrator Portal**: https://integrator.getyourguide.com

**Your API (GYG calls you)**:
- Username: `Cristian`
- Password: `DhanUk@458098`
- Host: `supplier-api.getyourguide.com`
- Path: `/1/`

**GYG API (You call GYG)**:
- Username: `FlorencewithLocals`
- Password: `7fdb535c0d3b73be01565517fea54122`
- Base URL: `https://supplier-api.getyourguide.com`
- Sandbox URL: `https://supplier-api.getyourguide.com/sandbox`

### GYG Testing Status
Tests started but not completed (Dec 26, 2024):
- Time point for Individuals - Started
- Time period for Individuals - Started
- Time point for Groups - Not started
- Time period for Groups - Not started

### Documentation Location
All GYG API documentation saved in: `GYGAPI/` folder (gitignored)
- `GYGAPI_Documents01.txt` - Overview documentation
- `GYG_endpoints.txt` - GYG-side endpoints
- `Suppler_ApiEndpoins.txt` - Supplier-side endpoints
- `supplier-api-gyg-endpoints (1).yaml` - OpenAPI spec (GYG endpoints)
- `supplier-api-supplier-endpoints (1).yaml` - OpenAPI spec (Supplier endpoints)
- Various screenshots from Integrator Portal

### Future Implementation Path
1. Implement all 5 required endpoints in Laravel
2. Pass GYG self-testing in Integrator Portal
3. Set up production configuration
4. Have GYG products mapped to your system
5. Receive bookings with full traveler details

### Alternative Solutions
1. **Contact GYG Support**: Email `supplier-api@getyourguide.com` to ask about webhook-only integration
2. **Bokun Webhooks**: Check if Bokun can provide more detailed GYG data via webhooks
3. **Manual Process**: Continue using Bokun; manually check GYG Supplier Portal for participant names when needed

### Why Not Implemented
- Requires significant development (5 endpoints + testing)
- Need to manage availability sync between systems
- Current Bokun integration works for most needs
- GYG participant names are "nice to have" not critical

---

## Git Tags / Restore Points

| Tag | Commit | Description |
|-----|--------|-------------|
| `v1.0-pre-gyg` | `edf5b07` | Before GYG API research (Jan 23, 2026) |

### Using Restore Points
```bash
# View all tags
git tag -l

# Checkout a specific tag
git checkout v1.0-pre-gyg

# Return to latest
git checkout master
```
