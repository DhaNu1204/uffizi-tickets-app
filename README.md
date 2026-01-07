# Uffizi Ticket Management Dashboard

A web application for managing Uffizi Gallery tour bookings. Integrates with Bokun booking system and tracks ticket purchases.

**Live URL**: https://uffizi.deetech.cc

## Features

### Core Functionality
- **Bokun Integration**: Automatically syncs confirmed bookings with participant names
- **Ticket Tracking**: Track Uffizi ticket purchase status and reference numbers
- **Tickets Sent Tracking**: Mark when tickets are sent to clients (with timestamp)
- **Guide Assignment**: Assign guides to guided tour bookings
- **Notes System**: Add operational notes to bookings
- **Customer Contact**: View customer email and phone (clickable links)

### User Experience
- **Daily View**: One day's bookings per page with swipe navigation
- **Calendar Picker**: Visual calendar showing booking counts for each day
- **Time Slot Grouping**: Bookings organized by tour time
- **Multi-Product Support**: Filter by entry tickets or guided tours
- **Mobile Responsive**: Works on desktop and mobile devices
- **Error Boundaries**: Graceful error handling with recovery options

### Data Management
- **Auto-Sync**: Dashboard automatically syncs on load
- **Cancellation Detection**: Automatically detects and removes cancelled bookings
- **Booking Channel Tracking**: Identifies source (GetYourGuide, Viator, Direct, Airbnb)
- **Florence Timezone**: Uses Europe/Rome timezone for accurate "today" display
- **Real-time Updates**: Webhook support for instant booking notifications

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12.x (PHP 8.2+) |
| Frontend | React 19 + Vite 7 |
| Database | MySQL 8.0 |
| Styling | Custom CSS |
| CI/CD | GitHub Actions |

## Supported Products

| Product | Bokun ID | Type |
|---------|----------|------|
| Timed Entry Tickets | 961802 | Entry |
| Small Group Guided Tour | 961801 | Guided Tour |
| Uffizi, David Tour & Gelato | 962885 | Guided Tour |
| VIP Private Tour | 962886 | Guided Tour |
| Guided Tour + Vasari Corridor | 1130528 | Guided Tour |
| Florence Uffizi Gallery Tour | 1135055 | Guided Tour |

## Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0

### Backend Setup
```bash
cd backend
composer install
cp .env.example .env
# Configure database and Bokun credentials in .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend Setup
```bash
cd frontend
npm install
cp .env.example .env
# Configure API URL in .env
npm run dev
```

### Environment Variables

**Backend (.env)**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=uffizi_tickets
DB_USERNAME=root
DB_PASSWORD=your_password

BOKUN_ACCESS_KEY=your_bokun_access_key
BOKUN_SECRET_KEY=your_bokun_secret_key
BOKUN_BASE_URL=https://api.bokun.io
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055
```

**Frontend (.env)**
```env
VITE_API_URL=http://localhost:8000/api
```

## Project Structure

```
Uffizi-Ticket-App/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Console/Commands/   # Artisan commands (sync, debug)
│   │   ├── Constants/          # ProductConstants
│   │   ├── Enums/              # BookingStatus enum
│   │   ├── Http/Controllers/   # API controllers
│   │   ├── Models/             # Eloquent models
│   │   └── Services/           # BokunService
│   ├── database/migrations/    # Database schema
│   ├── routes/api.php          # API routes
│   └── tests/                  # PHPUnit tests
├── frontend/                   # React SPA
│   ├── src/
│   │   ├── components/         # React components
│   │   ├── constants/          # Shared constants
│   │   ├── context/            # Auth & Toast context
│   │   ├── pages/              # Dashboard, Login
│   │   └── services/           # API client
│   └── dist/                   # Production build
├── .github/workflows/          # CI/CD pipeline
├── deploy/                     # Deployment files
└── docs/                       # Documentation
```

## API Endpoints

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check |
| GET | `/api/health/detailed` | Detailed health check |
| POST | `/api/webhook/bokun` | Bokun webhook receiver |

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | Login |
| POST | `/api/logout` | Logout |

### Bookings (Protected)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bookings` | List all bookings |
| GET | `/api/bookings/grouped` | Bookings grouped by date |
| GET | `/api/bookings/stats` | Dashboard statistics |
| PUT | `/api/bookings/{id}` | Update booking |
| POST | `/api/bookings/sync` | Full sync from Bokun |
| POST | `/api/bookings/auto-sync` | Auto-sync with limit |

## Artisan Commands

```bash
# Sync bookings from Bokun (with limit)
php artisan bokun:sync --limit=50

# Full sync (fetch ALL missing data)
php artisan bokun:sync --full

# Debug booking structure
php artisan booking:debug {confirmationCode}
```

## Testing

```bash
# Run all tests
cd backend
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

### Quick Deploy
```bash
# Build frontend
cd frontend && npm run build

# Upload to server
scp -P 65002 -r dist/* user@server:/path/to/uffizi/

# Run migrations
ssh -p 65002 user@server "cd /path/to/backend && php artisan migrate --force"
```

## Workflow

1. **View bookings** for the day
2. **Purchase tickets** from Uffizi B2B account
3. **Add Ticket** - Enter Uffizi reference number
4. **Add notes** if needed (optional)
5. **Send tickets** to client via WhatsApp/Email
6. **Mark as Sent** - Click "Send" button to track timestamp

## Documentation

| File | Description |
|------|-------------|
| [CLAUDE.md](CLAUDE.md) | AI context and project details |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Production deployment guide |
| [OPTIMIZATION_REPORT.md](OPTIMIZATION_REPORT.md) | Code optimization details |

## License

Private - All rights reserved

## Author

Built with Laravel + React
