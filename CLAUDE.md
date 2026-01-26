# Claude Code Context - Uffizi Ticket App

## Project Overview
A ticket management dashboard for Uffizi Gallery tours. Syncs bookings from Bokun API and tracks Uffizi ticket purchases.

**Live URL**: https://uffizi.deetech.cc | **GitHub**: https://github.com/DhaNu1204/uffizi-tickets-app

## Tech Stack
- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: React 19 + Vite
- **Database**: MySQL
- **Integrations**: Bokun API, Twilio (WhatsApp/SMS), AWS S3, Laravel Mail
- **Monitoring**: Sentry (frontend), GitHub Actions CI/CD

## Project Structure
```
/Uffizi-Ticket-App
├── backend/                    # Laravel API
│   ├── app/Http/Controllers/   # BookingController, MessageController, ConversationController
│   ├── app/Models/             # Booking, Message, Conversation, MessageTemplate
│   ├── app/Services/           # BokunService, TwilioService, IncomingMessageService
│   ├── routes/api.php          # API routes
│   └── database/migrations/    # DB schema
├── frontend/                   # React SPA
│   ├── src/components/         # BookingTable, TicketWizard/, ManualSendModal
│   ├── src/pages/              # Dashboard, TemplateAdmin, ConversationsPage
│   └── src/services/api.js     # API client
├── docs/                       # Extended documentation
│   ├── TROUBLESHOOTING.md      # Common issues and fixes
│   ├── DATABASE.md             # Detailed schema
│   ├── INTEGRATIONS.md         # Third-party APIs
│   ├── GYG-API.md              # GetYourGuide research
│   └── AWS-SECURITY.md         # S3 security guidelines
└── deploy/                     # Deployment files
```

## Key Features
- Bokun sync with auto-sync on dashboard load
- Ticket tracking (PENDING_TICKET / TICKET_PURCHASED)
- 6-step Ticket Sending Wizard (Timed Entry only)
- Multi-channel messaging (WhatsApp, SMS, Email) with auto-detection
- **Conversations** - View and reply to customer WhatsApp/SMS replies
- **Manual Send** - Send messages to any phone/email without a booking
- 10-language templates with phone-based auto-detection
- Audio guide tracking and PDF attachments
- Daily pagination with calendar picker
- Guide assignment and notes
- Cancellation detection with soft-delete

## Bokun Product IDs
| ID | Product |
|----|---------|
| 961802 | Timed Entry Tickets |
| 961801 | Small Group Guided Tour |
| 962885 | Uffizi, David Tour & Gelato |
| 962886 | VIP Private Tour |
| 1130528 | Guided Tour + Vasari |
| 1135055 | Florence Uffizi + Palazzo Vecchio |

## Database Tables
| Table | Purpose |
|-------|---------|
| bookings | Tour bookings from Bokun |
| conversations | WhatsApp/SMS conversation threads |
| messages | Sent/received messages (direction: outbound/inbound) |
| message_templates | Multi-language templates |
| message_attachments | PDF attachments |
| users | Admin authentication |

See `docs/DATABASE.md` for detailed schema.

## API Endpoints

### Core
| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | /api/login | Authentication |
| GET | /api/bookings/grouped | Bookings by date |
| PUT | /api/bookings/{id} | Update booking |
| POST | /api/bookings/sync | Full Bokun sync |
| POST | /api/bookings/auto-sync | Auto-sync |

### Messaging
| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | /api/bookings/{id}/send-ticket | Send ticket |
| GET | /api/bookings/{id}/detect-channel | Check WhatsApp |
| POST | /api/messages/preview | Preview message |
| GET | /api/templates/languages | Supported languages |
| POST | /api/messages/send-manual | Send manual message (no booking) |
| GET | /api/messages/manual-history | Get manual message history |

### Conversations (WhatsApp/SMS Inbox)
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | /api/conversations | List conversations |
| GET | /api/conversations/{id} | Get conversation with messages |
| POST | /api/conversations/{id}/reply | Send reply |
| PUT | /api/conversations/{id}/read | Mark as read |
| DELETE | /api/conversations/{id} | Archive conversation |
| POST | /api/webhooks/twilio/incoming | Incoming webhook (public) |

### Attachments & Templates
| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | /api/bookings/{id}/attachments | Upload PDF |
| DELETE | /api/attachments/{id} | Remove PDF |
| GET | /api/admin/templates | List templates |
| PUT | /api/admin/templates/{id} | Update template |

## Environment Variables
```env
# Backend essentials
BOKUN_ACCESS_KEY=xxx
BOKUN_SECRET_KEY=xxx
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055

# Twilio
TWILIO_ACCOUNT_SID=xxx
TWILIO_AUTH_TOKEN=xxx
TWILIO_WHATSAPP_FROM=+14155238886
TWILIO_SMS_FROM=+1234567890

# AWS S3
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=uffizi-attachments

# Email
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=tickets@florencewithlocals.com
```

See `backend/.env.example` for full list.

## Production Server (Hostinger)
- **URL**: https://uffizi.deetech.cc
- **SSH**: `ssh -p 65002 u803853690@82.25.82.111`
- **PHP**: `/opt/alt/php82/usr/bin/php`
- **Path**: `/home/u803853690/domains/deetech.cc/public_html/uffizi/`

### Key Commands
```bash
# Navigate to backend
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend

# Run artisan (must use PHP 8.2)
/opt/alt/php82/usr/bin/php artisan migrate
/opt/alt/php82/usr/bin/php artisan optimize:clear
/opt/alt/php82/usr/bin/php artisan bokun:sync --limit=50
```

### Important Notes
- Uses Bearer token auth (NOT cookie-based)
- Do NOT use `statefulApi()` middleware in bootstrap/app.php
- API returns JSON 401 for unauthenticated requests

## Common Tasks
- **Sync bookings**: Click "Sync Bokun" button (auto-syncs on load)
- **Add ticket**: Click "Add Ticket" > Enter Uffizi code
- **Send ticket (wizard)**: Click "Send Ticket" > 6-step wizard
- **View conversations**: Username dropdown > "Conversations"
- **Manual send**: Username dropdown > "Manual Send" (any phone/email)
- **Mark audio sent**: Click "Audio Sent" for audio guide bookings
- **Copy reference**: Click reference number to copy
- **Manage templates**: Username dropdown > "Message Templates"

## Workflows

### Timed Entry Tickets (Wizard)
1. Purchase tickets from Uffizi B2B
2. Click "Send Ticket" > 6-step wizard:
   - Review details > Enter reference > Upload PDF > Select language > Preview > Send
3. Auto-sends via WhatsApp (if available) or Email+SMS

### Guided Tours (Manual)
1. Purchase tickets from Uffizi B2B
2. Click "Add Ticket" > Enter reference
3. Assign guide, add notes
4. Keep tickets for guide (NOT sent to customers)

### Audio Guide
1. Look for purple "Audio Guide" badge
2. Send audio guide access link to client
3. Click "Audio Sent" to track

### Manual Send (Test/Custom Messages)
1. Click username dropdown > "Manual Send"
2. Select channel: WhatsApp, SMS, or Email
3. Enter recipient (phone with country code or email)
4. Enter message content
5. Optionally attach PDF (WhatsApp/Email only)
6. Click "Send Message"
7. Check History tab for delivery status

**Notes**:
- Phone numbers require 11-15 digits (e.g., +39 333 123 4567)
- Messages logged in database with `booking_id = null`
- Rate limited to 10 messages per minute

### Conversations (Reply to Customers)
1. Click username dropdown > "Conversations"
2. Select a conversation from the left panel
3. View message history (outbound = purple, inbound = gray)
4. Type reply and click Send (or press Enter)
5. Archive completed conversations

**WhatsApp 24-Hour Window**:
- After customer messages, you can reply freely for 24 hours
- After 24 hours, the window expires and customer must message first
- Warning banner shows when window is expiring
- SMS has no time restrictions

**Auto-Linking**:
- Incoming messages auto-match to bookings by phone number
- Can manually link conversation to a booking if needed

## Ticket Sending Wizard

**For Timed Entry Tickets only** (Product 961802)

| Step | Purpose |
|------|---------|
| 1 | Review booking details |
| 2 | Enter Uffizi reference + audio credentials |
| 3 | Upload PDF ticket (required) |
| 4 | Select language (auto-detected) or custom |
| 5 | Preview message, confirm channel |
| 6 | Send and track delivery |

### Supported Languages
en, it, es, de, fr, ja, el, tr, ko, pt (auto-detected from phone country code)

### Channel Detection
- Phone + WhatsApp available > WhatsApp only
- Phone + No WhatsApp > Email + SMS notification
- No phone > Email only

### Template Variables
`{customer_name}`, `{tour_date}`, `{tour_time}`, `{reference_number}`, `{pax}`, `{audio_guide_url}`, `{audio_guide_username}`, `{audio_guide_password}`

## Artisan Commands
```bash
# Sync bookings
php artisan bokun:sync --limit=50
php artisan bokun:sync --full

# Audio guide backfill
php artisan bookings:backfill-audio-guide --limit=500
php artisan bookings:backfill-audio-guide --dry-run

# Debug
php artisan booking:debug BOOKING_CODE

# Cache
php artisan optimize:clear
```

## Local Development

### Quick Start
```bash
# Backend
cd D:/Uffizi-Ticket-App/backend
php artisan serve --host=127.0.0.1 --port=8000

# Frontend (new terminal)
cd D:/Uffizi-Ticket-App/frontend
npm run dev -- --port 5173 --host 127.0.0.1
```

| Service | URL |
|---------|-----|
| Frontend | http://127.0.0.1:5173 |
| Backend | http://127.0.0.1:8000/api |

See `CLAUDE.local.md.example` for machine-specific settings.

## Deployment

### Quick Deploy
```bash
# 1. Build frontend
cd D:/Uffizi-Ticket-App/frontend && npm run build

# 2. Upload files
scp -P 65002 frontend/dist/index.html u803853690@82.25.82.111:/home/.../uffizi/
scp -P 65002 -r frontend/dist/assets/* u803853690@82.25.82.111:/home/.../uffizi/assets/

# 3. Run migrations
ssh -p 65002 u803853690@82.25.82.111 "cd /home/.../uffizi/backend && /opt/alt/php82/usr/bin/php artisan migrate --force && /opt/alt/php82/usr/bin/php artisan optimize:clear"
```

## Rate Limiting
- Login: 5/min
- API: 60/min
- Sync: 10/min
- Manual Send: 10/min

## Git Tags
| Tag | Description |
|-----|-------------|
| v1.0-pre-gyg | Before GYG research (Jan 23, 2026) |
| v2.0-messaging | Messaging system (Jan 25, 2026) |

## Documentation Index
| Document | Content |
|----------|---------|
| `docs/TROUBLESHOOTING.md` | Common issues and fixes |
| `docs/DATABASE.md` | Detailed schema and migrations |
| `docs/INTEGRATIONS.md` | Bokun, Twilio, S3, Email details |
| `docs/GYG-API.md` | GetYourGuide API research |
| `docs/AWS-SECURITY.md` | S3 security guidelines |
| `CLAUDE.local.md.example` | Local dev settings template |

## Recent Changes (Jan 2026)
- **Conversations page** - View and reply to customer WhatsApp/SMS messages
- Ticket Sending Wizard (6 steps)
- Multi-channel messaging (WhatsApp, SMS, Email)
- **Manual Send feature** - Send to any phone/email without booking
- 10-language templates with auto-detection
- Template Admin at /admin/templates
- PDF attachments via S3
- Audio guide tracking
- Sentry error tracking

## Twilio Webhook Configuration
Configure these URLs in Twilio Console:

| Webhook | URL |
|---------|-----|
| WhatsApp Incoming | `https://uffizi.deetech.cc/api/webhooks/twilio/incoming` |
| SMS Incoming | `https://uffizi.deetech.cc/api/webhooks/twilio/incoming` |
| Status Callback | `https://uffizi.deetech.cc/api/webhooks/twilio/status` |

---

*For troubleshooting, see `docs/TROUBLESHOOTING.md`*
