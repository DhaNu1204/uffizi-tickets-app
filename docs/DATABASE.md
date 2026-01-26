# Database Schema - Uffizi Ticket App

## Overview

The application uses MySQL with the following main tables:
- `bookings` - Tour bookings synced from Bokun
- `conversations` - WhatsApp/SMS conversation threads
- `messages` - Sent/received messages (WhatsApp, SMS, Email)
- `message_templates` - Multi-language message templates
- `message_attachments` - PDF ticket attachments
- `users` - Admin users (Laravel Sanctum auth)

## Tables

### bookings

Main table storing tour bookings synced from Bokun API.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| bokun_booking_id | varchar | Unique Bokun ID |
| bokun_product_id | varchar | Product type (see Product IDs below) |
| booking_channel | varchar | Source (GetYourGuide, Viator, Direct, etc.) |
| product_name | varchar | Tour/ticket name |
| customer_name | varchar | Customer name |
| customer_email | varchar | Customer email address |
| customer_phone | varchar | Customer phone number |
| tour_date | datetime | Tour date/time (Florence local stored as UTC) |
| pax | int | Total passengers |
| pax_details | json | Breakdown by type (e.g., `{"Adult": 2, "Child": 1}`) |
| participants | json | Individual participant names with types |
| status | varchar | PENDING_TICKET or TICKET_PURCHASED |
| reference_number | varchar | Uffizi confirmation code |
| notes | text | Operator notes |
| guide_name | varchar | Assigned guide for guided tours |
| cancelled_at | timestamp | When booking was cancelled (null if active) |
| tickets_sent_at | timestamp | When tickets were sent to client |
| has_audio_guide | boolean | Whether booking includes audio guide (Timed Entry only) |
| audio_guide_sent_at | timestamp | When audio guide link was sent |
| audio_guide_username | varchar | POP Guide username |
| audio_guide_password | varchar | POP Guide password |
| audio_guide_url | varchar | POP Guide access URL |
| wizard_started_at | timestamp | When ticket wizard was opened |
| wizard_last_step | tinyint | Last wizard step reached (1-6) |
| wizard_abandoned_at | timestamp | When wizard was closed without completing |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Last update time |
| deleted_at | timestamp | Soft delete (for cancelled bookings) |

**Indexes**:
- `bokun_booking_id` (unique)
- `tour_date`
- `status`
- `bokun_product_id`

**Wizard Status Logic**:
- `tickets_sent_at` set = completed
- `wizard_abandoned_at` set = abandoned (needs attention)
- `wizard_started_at` set, no abandon = in progress
- Nothing set = not started

---

### conversations

WhatsApp/SMS conversation threads with customers.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| phone_number | varchar(20) | E.164 format phone number |
| channel | enum | whatsapp or sms |
| booking_id | bigint | Foreign key to bookings (nullable) |
| status | enum | active or archived |
| last_message_at | timestamp | When last message was sent/received |
| unread_count | int | Number of unread incoming messages |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Last update time |

**Indexes**:
- `phone_number`, `channel` (unique composite)
- `status`
- `last_message_at`
- `unread_count`

**WhatsApp 24-Hour Window**:
- After customer sends a message, business can reply freely for 24 hours
- After 24 hours, must use pre-approved templates
- `last_message_at` of inbound messages determines window expiry

---

### messages

Records of all sent/received messages for ticket delivery, manual sends, and conversations.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| booking_id | bigint | Foreign key to bookings (nullable for manual sends) |
| conversation_id | bigint | Foreign key to conversations (nullable) |
| channel | varchar | whatsapp, sms, or email |
| direction | enum | outbound (sent by us) or inbound (from customer) |
| external_id | varchar | Twilio SID or mail ID |
| recipient | varchar | Phone number or email address |
| sender_name | varchar | Customer name (for inbound, from WhatsApp profile) |
| subject | varchar | Email subject (null for WhatsApp/SMS) |
| content | text | Message content |
| template_id | bigint | Foreign key to message_templates (nullable) |
| template_variables | json | Variables used in template |
| status | varchar | pending, queued, sent, delivered, read, failed |
| error_message | text | Error details if failed |
| retry_count | int | Number of retry attempts |
| queued_at | timestamp | When added to queue |
| sent_at | timestamp | When sent to provider |
| delivered_at | timestamp | When delivered to recipient |
| read_at | timestamp | When read (WhatsApp only) |
| failed_at | timestamp | When failed |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Last update time |

**Indexes**:
- `booking_id` (foreign key, nullable - allows null for manual messages)
- `conversation_id` (foreign key, nullable)
- `channel`
- `direction`
- `status`
- `external_id`
- `conversation_id`, `created_at` (composite for conversation messages)

**Message Types**:
- Manual messages: `booking_id = null`, `conversation_id = null`
- Wizard messages: `booking_id = X`, may have `conversation_id`
- Conversation replies: `conversation_id = X`, may have `booking_id`
- Inbound messages: `direction = 'inbound'`, always have `conversation_id`

---

### message_templates

Multi-language message templates for ticket sending.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | Template display name |
| slug | varchar | URL-safe identifier |
| channel | varchar | email, whatsapp, or sms |
| language | varchar | Language code (en, it, es, de, fr, ja, el, tr, ko, pt) |
| template_type | varchar | ticket_only or ticket_with_audio |
| subject | varchar | Email subject (with variables) |
| content | text | Message body (with variables) |
| is_default | boolean | Default template for language |
| is_active | boolean | Whether template is usable |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Last update time |

**Template Variables**:
| Variable | Description | Example |
|----------|-------------|---------|
| `{customer_name}` | Customer full name | John Smith |
| `{tour_date}` | Tour date | January 25, 2026 |
| `{tour_time}` | Tour time | 10:30 AM |
| `{reference_number}` | Uffizi ticket reference | ABC123456 |
| `{pax}` | Number of guests | 4 |
| `{audio_guide_url}` | POP Guide access URL | https://pg.unlockmy.app/... |
| `{audio_guide_username}` | POP Guide username | TKE-000392 |
| `{audio_guide_password}` | POP Guide password | 52628 |

**Indexes**:
- `slug` (unique)
- `channel`, `language`, `template_type` (composite)
- `is_active`

---

### message_attachments

PDF ticket attachments for messages.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| message_id | bigint | Foreign key to messages (nullable) |
| booking_id | bigint | Foreign key to bookings |
| original_name | varchar | Original filename |
| stored_name | varchar | UUID-based stored name |
| disk | varchar | Storage disk (local or s3) |
| path | varchar | File path |
| mime_type | varchar | application/pdf |
| size | int | File size in bytes |
| public_url | varchar | Temporary public URL |
| expires_at | timestamp | When public URL expires |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Last update time |

**Indexes**:
- `message_id` (foreign key)
- `booking_id` (foreign key)

---

## Product IDs (Bokun)

| Product ID | Name |
|------------|------|
| `961802` | Timed Entry Tickets |
| `961801` | Small Group Guided Tour |
| `962885` | Uffizi, David Tour & Gelato with Art Historian |
| `962886` | VIP Private Tour |
| `1130528` | Guided Tour + Vasari |
| `1135055` | Florence Uffizi Gallery Tour with Palazzo Vecchio Entry |

---

## Audio Guide Rate Identifiers

For Timed Entry Tickets (961802) only:
- **Rate ID**: `2263305`
- **Rate Code**: `TG2`

---

## Migrations

Migration files are located at `backend/database/migrations/`:

| Migration | Purpose |
|-----------|---------|
| `2026_01_25_100000_add_audio_guide_credentials_to_bookings_table.php` | Audio guide fields |
| `2026_01_25_100001_create_message_templates_table.php` | Message templates |
| `2026_01_25_100002_create_messages_table.php` | Sent messages |
| `2026_01_25_100003_create_message_attachments_table.php` | PDF attachments |
| `2026_01_25_200001_add_template_type_to_message_templates.php` | Template types |
| `2026_01_26_100000_add_wizard_progress_to_bookings_table.php` | Wizard progress tracking |
| `2026_01_26_130000_make_booking_id_nullable_in_messages_table.php` | Allow manual sends |
| `2026_01_27_000001_create_conversations_table.php` | Conversation threads |
| `2026_01_27_000002_add_conversation_fields_to_messages.php` | Add direction, conversation_id to messages |

---

## Time Zone Note

- **Backend**: Bokun stores Florence local time as UTC in the database
- **Frontend**: Uses `Europe/Rome` timezone for "today" calculation
- **Date Display**: Times shown as-is (UTC) to match Bokun dashboard
- **Auto-Update**: Dashboard auto-updates at midnight Florence time
