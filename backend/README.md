# Uffizi Ticket Dashboard - Backend API

A Laravel-based API for managing Uffizi Museum tour bookings integrated with Bokun booking system.

## Features

- **Bokun API Integration**: Sync bookings from Bokun booking platform
- **Webhook Support**: Real-time booking updates via Bokun webhooks with HMAC verification
- **Authentication**: Sanctum-based API token authentication
- **Booking Management**: Full CRUD operations with filtering, pagination, and search
- **Webhook Logging**: Complete audit trail with retry mechanism for failed webhooks
- **Soft Deletes**: Cancelled bookings are preserved for audit purposes

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Composer

## Installation

1. **Clone and install dependencies:**
```bash
cd backend
composer install
```

2. **Configure environment:**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Set up database:**
```bash
# Update .env with your database credentials
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

4. **Configure Bokun API credentials in `.env`:**
```env
BOKUN_ACCESS_KEY=your_access_key
BOKUN_SECRET_KEY=your_secret_key
BOKUN_BASE_URL=https://api.bokun.io
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528
```

## Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `BOKUN_ACCESS_KEY` | Bokun API access key | Yes |
| `BOKUN_SECRET_KEY` | Bokun API secret key (also used for webhook HMAC) | Yes |
| `BOKUN_BASE_URL` | Bokun API base URL | Yes |
| `UFFIZI_PRODUCT_IDS` | Comma-separated list of Uffizi product IDs to track | Yes |

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | Login and get API token |
| POST | `/api/logout` | Logout (revoke current token) |
| GET | `/api/user` | Get current authenticated user |

#### Login Request
```json
{
  "email": "admin@uffizi-tickets.com",
  "password": "your_password"
}
```

#### Login Response
```json
{
  "user": { "id": 1, "name": "Admin", "email": "admin@uffizi-tickets.com" },
  "token": "1|abc123...",
  "token_type": "Bearer"
}
```

### Bookings (Protected - requires Bearer token)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bookings` | List bookings with filtering |
| GET | `/api/bookings/{id}` | Get single booking |
| POST | `/api/bookings` | Create new booking |
| PUT | `/api/bookings/{id}` | Update booking |
| DELETE | `/api/bookings/{id}` | Soft delete booking |
| GET | `/api/bookings/stats` | Get dashboard statistics |
| POST | `/api/bookings/sync` | Sync from Bokun API |
| POST | `/api/bookings/import` | Import historical bookings |

#### List Bookings Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `PENDING_TICKET`, `TICKET_PURCHASED` |
| `product_id` | string | Filter by Bokun product ID |
| `date_from` | date | Filter bookings from date (YYYY-MM-DD) |
| `date_to` | date | Filter bookings until date (YYYY-MM-DD) |
| `search` | string | Search customer name |
| `per_page` | int | Items per page (default: 20, max: 100) |
| `page` | int | Page number |
| `sort_by` | string | Sort field: `tour_date`, `created_at`, `customer_name`, `status`, `pax` |
| `sort_dir` | string | Sort direction: `asc`, `desc` |

#### Example Request
```bash
curl -X GET "http://localhost/api/bookings?status=PENDING_TICKET&date_from=2025-01-01&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Create Booking Request
```json
{
  "bokun_booking_id": "ABC-123456",
  "bokun_product_id": "961802",
  "product_name": "Uffizi Gallery Tour",
  "customer_name": "John Smith",
  "tour_date": "2025-02-15",
  "pax": 4,
  "status": "PENDING_TICKET"
}
```

#### Update Booking Request
```json
{
  "status": "TICKET_PURCHASED",
  "reference_number": "UFF-789012"
}
```

#### Import Historical Bookings
```json
{
  "date_from": "2024-01-01",
  "date_to": "2024-12-31"
}
```

### Webhook Admin (Protected)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/webhooks` | List webhook logs |
| GET | `/api/webhooks/{id}` | Get webhook details |
| GET | `/api/webhooks/stats` | Get webhook statistics |
| POST | `/api/webhooks/{id}/retry` | Retry single webhook |
| POST | `/api/webhooks/retry-all` | Retry all failed webhooks |
| DELETE | `/api/webhooks/cleanup` | Delete old webhook logs |

#### Webhook Log Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter: `pending`, `processed`, `failed` |
| `event_type` | string | Filter by event type |
| `confirmation_code` | string | Search by confirmation code |
| `date_from` | date | Filter from date |
| `date_to` | date | Filter until date |

#### Cleanup Request
```json
{
  "days": 30,
  "status": "processed"
}
```

### Webhook Endpoint (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/webhook/bokun` | Receive Bokun webhooks |

Configure this URL in Bokun dashboard for booking notifications.

## Artisan Commands

```bash
# Import historical bookings
php artisan bookings:import --from=2024-01-01 --to=2024-12-31

# Dry run (preview without importing)
php artisan bookings:import --from=2024-01-01 --to=2024-12-31 --dry-run

# Retry failed webhooks
php artisan webhooks:retry

# Retry with custom max attempts
php artisan webhooks:retry --max-retries=5

# Run synchronously (not queued)
php artisan webhooks:retry --sync
```

## Webhook HMAC Verification

Bokun webhooks are verified using HMAC-SHA256. The signature is calculated over all `X-Bokun-*` headers (excluding `X-Bokun-HMAC`) sorted alphabetically.

To disable verification (development only), remove `BOKUN_SECRET_KEY` from `.env`.

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=BookingControllerTest

# Run with coverage
php artisan test --coverage
```

## Database Schema

### bookings
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| bokun_booking_id | string | Unique Bokun confirmation code |
| bokun_product_id | string | Bokun product ID |
| product_name | string | Product/tour name |
| customer_name | string | Customer full name |
| tour_date | datetime | Tour/visit date |
| pax | int | Number of participants |
| status | string | `PENDING_TICKET` or `TICKET_PURCHASED` |
| reference_number | string | Uffizi ticket reference (nullable) |
| deleted_at | datetime | Soft delete timestamp |
| timestamps | | created_at, updated_at |

### webhook_logs
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| event_type | string | Webhook event type |
| confirmation_code | string | Booking confirmation code |
| payload | json | Full webhook payload |
| headers | json | Request headers |
| status | enum | `pending`, `processed`, `failed` |
| error_message | text | Error details if failed |
| retry_count | int | Number of retry attempts |
| processed_at | datetime | When successfully processed |
| timestamps | | created_at, updated_at |

## Security

- API endpoints protected with Laravel Sanctum
- Webhook HMAC-SHA256 signature verification
- Credentials stored in environment variables
- Debug routes disabled in production
- Soft deletes preserve audit trail

## Default Admin User

After running the seeder:
- Email: `admin@uffizi-tickets.com`
- Password: `admin123`

**Important:** Change this password immediately in production!

## License

Proprietary - All rights reserved.
