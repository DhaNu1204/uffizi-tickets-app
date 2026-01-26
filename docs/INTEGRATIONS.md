# Third-Party Integrations - Uffizi Ticket App

## Overview

The application integrates with several external services:
- **Bokun** - Booking management system
- **Twilio** - WhatsApp and SMS messaging
- **AWS S3** - File storage for PDF attachments
- **SMTP** - Email delivery

## Bokun API

**Service File**: `backend/app/Services/BokunService.php`

### Configuration

Environment variables:
```env
BOKUN_ACCESS_KEY=xxx
BOKUN_SECRET_KEY=xxx
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055
```

### Functionality

| Method | Purpose |
|--------|---------|
| `syncBookings()` | Full sync of all bookings from Bokun |
| `getBookingDetails()` | Fetch single booking details |
| `extractParticipants()` | Extract participant names from booking data |
| `extractCustomerContact()` | Extract email/phone from booking |
| `detectAudioGuide()` | Check if booking includes audio guide |

### API Endpoints Used

- `GET /vendor.json/booking/confirmed-customer-bookings` - List confirmed bookings
- `GET /vendor.json/booking/{id}` - Single booking details
- `GET /vendor.json/product/{id}` - Product information

### Webhook

- **Endpoint**: `POST /api/webhook/bokun`
- **Controller**: `WebhookController.php`
- **Events**: Booking creation, update, cancellation

### Cancellation Handling

1. **During Sync**: Compares DB with API, soft-deletes cancelled bookings
2. **Via Webhook**: Real-time soft-deletion on cancellation event
3. **Query Filtering**: Laravel SoftDeletes automatically excludes cancelled bookings

---

## Twilio (WhatsApp + SMS)

**Service Files**:
- `backend/app/Services/WhatsAppService.php`
- `backend/app/Services/SmsService.php`

### Configuration

Environment variables:
```env
TWILIO_ACCOUNT_SID=ACxxxx
TWILIO_AUTH_TOKEN=xxxx
TWILIO_WHATSAPP_FROM=+14155238886
TWILIO_SMS_FROM=+1234567890
TWILIO_STATUS_CALLBACK_URL=https://uffizi.deetech.cc/api/webhooks/twilio/status
```

### WhatsApp Service

| Method | Purpose |
|--------|---------|
| `sendMessage()` | Send WhatsApp message with optional media |
| `checkAvailability()` | Check if phone can receive WhatsApp |
| `sendWithAttachment()` | Send PDF attachment via WhatsApp |

### SMS Service

| Method | Purpose |
|--------|---------|
| `sendMessage()` | Send SMS message |
| `sendNotification()` | Send "check your email" notification |

### Status Webhook

- **Endpoint**: `POST /api/webhooks/twilio/status`
- **Events**: queued, sent, delivered, read, failed
- **Updates**: Message status in database

### Channel Detection Flow

```
Phone available?
  |
  YES --> Check WhatsApp availability
  |         |
  |        YES --> WhatsApp only (PDF + message)
  |         |
  |        NO --> Email + SMS notification
  |
  NO --> Email only
```

---

## AWS S3 (File Storage)

**Service File**: `backend/app/Http/Controllers/AttachmentController.php`

### Configuration

Environment variables:
```env
AWS_ACCESS_KEY_ID=xxxx
AWS_SECRET_ACCESS_KEY=xxxx
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=uffizi-attachments
```

### Laravel Config

File: `backend/config/filesystems.php`

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
],
```

### Functionality

| Method | Purpose |
|--------|---------|
| `store()` | Upload PDF to S3 |
| `delete()` | Remove PDF from S3 |
| `getTemporaryUrl()` | Generate pre-signed URL (1 hour expiry) |

### Security

- Files stored with private ACL
- Public access via pre-signed URLs only
- URLs expire after 1 hour
- See `docs/AWS-SECURITY.md` for security guidelines

---

## Email (Laravel Mail)

**Service File**: `backend/app/Services/EmailService.php`

### Configuration

Environment variables:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=xxxx
MAIL_PASSWORD=xxxx
MAIL_FROM_ADDRESS=tickets@florencewithlocals.com
MAIL_FROM_NAME="Florence with Locals"
```

### Functionality

| Method | Purpose |
|--------|---------|
| `sendTicket()` | Send ticket email with PDF attachment |
| `sendWithTemplate()` | Send using message template |

### Email Templates

Location: `backend/resources/views/emails/`

| Template | Purpose |
|----------|---------|
| `ticket.blade.php` | Ticket delivery email |
| `ticket-with-audio.blade.php` | Ticket + audio guide instructions |

---

## Messaging Service (Orchestrator)

**Service File**: `backend/app/Services/MessagingService.php`

This service orchestrates all messaging channels and implements the channel detection logic.

### Main Method

```php
public function sendTicket(Booking $booking, array $options): array
```

### Options

| Option | Type | Description |
|--------|------|-------------|
| `channel` | string | Force specific channel (auto if omitted) |
| `template_id` | int | Message template to use |
| `custom_message` | string | Custom message content |
| `attachment_id` | int | PDF attachment ID |

### Return Value

```php
[
    'success' => true,
    'channel' => 'whatsapp',
    'message_id' => 123,
    'external_id' => 'SM...',
]
```

---

## Environment Variable Summary

### Required for Production

```env
# Bokun
BOKUN_ACCESS_KEY=
BOKUN_SECRET_KEY=
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055

# Twilio
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_WHATSAPP_FROM=
TWILIO_SMS_FROM=
TWILIO_STATUS_CALLBACK_URL=

# AWS S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# Email
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
```

---

## Testing Integrations

### Bokun
```bash
# Test connection
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/bookings/test-bokun

# Debug booking
php artisan booking:debug BOOKING_CODE
```

### Twilio
```bash
# Send test WhatsApp (via tinker)
php artisan tinker
>>> app(App\Services\WhatsAppService::class)->sendMessage('+1234567890', 'Test message');
```

### S3
```bash
# Test upload (via tinker)
php artisan tinker
>>> Storage::disk('s3')->put('test.txt', 'Hello World');
>>> Storage::disk('s3')->url('test.txt');
```

### Email
```bash
# Send test email (via tinker)
php artisan tinker
>>> Mail::raw('Test email', fn($m) => $m->to('test@example.com')->subject('Test'));
```
