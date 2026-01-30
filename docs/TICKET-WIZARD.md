# 🎟️ TICKET WIZARD - COMPLETE ANALYSIS REPORT

## PART 1: USER WORKFLOW REPORT

### Overview
The Ticket Wizard is a 6-step guided process for sending Uffizi Gallery tickets to customers. It's specifically designed for **Timed Entry Tickets** (Product ID: 961802).

---

### STEP 1: Review Booking Details

| Aspect | Details |
|--------|---------|
| **Purpose** | Confirm booking information before proceeding |
| **File** | `Step1BookingDetails.jsx` |

**What User Sees:**
- Read-only display of booking information in info cards:
  - Customer name & email
  - Tour date & time
  - Number of guests (PAX)
  - Booking reference
  - Product name
  - Current ticket status
- Audio Guide badge (purple) if booking includes audio guide

**What User Does:**
- Reviews information for accuracy
- Clicks "Next" to proceed

**Validation:** None (read-only step)

---

### STEP 2: Enter Ticket Reference

| Aspect | Details |
|--------|---------|
| **Purpose** | Enter the Uffizi B2B ticket reference code |
| **File** | `Step2TicketReference.jsx` |

**What User Sees:**
- Text input field for reference number
- Format hint: "8 uppercase letters/numbers (e.g., ABC12345)"
- If audio guide booking: Additional fields for username, password, and URL
- Validation errors in red text

**What User Does:**
1. Enters the Uffizi ticket reference code (8 chars, uppercase alphanumeric)
2. If audio guide: Enters audio guide credentials:
   - Username (e.g., TKE-000123)
   - Password (5-digit number)
   - URL (optional, defaults to https://pg.unlockmy.app/)

**Validation:**
- Reference: Required, exactly 8 characters, uppercase letters/numbers only
- Audio username: Required if audio guide, must start with TKE-
- Audio password: Required if audio guide, 5 digits

---

### STEP 3: Upload PDF Ticket

| Aspect | Details |
|--------|---------|
| **Purpose** | Attach the PDF ticket file(s) |
| **File** | `Step3FileAttach.jsx` |

**What User Sees:**
- Drag-and-drop zone with dashed border
- "Choose File" button alternative
- List of uploaded files with delete option
- File size limit warning (10MB max)
- Upload progress indicator

**What User Does:**
1. Drags PDF file onto drop zone OR clicks "Choose File"
2. Waits for upload to S3 to complete
3. Can delete and re-upload if needed
4. Can upload multiple PDFs

**Validation:**
- At least 1 PDF required
- File type: PDF only
- Max size: 10MB per file

---

### STEP 4: Select Language

| Aspect | Details |
|--------|---------|
| **Purpose** | Choose message template language |
| **File** | `Step4TemplateSelect.jsx` |

**What User Sees:**
- Auto-detection notice showing detected country from phone number
- Grid of language cards (10 languages with flags):
  - English, Italian, Spanish, German, French
  - Japanese, Greek, Turkish, Korean, Portuguese
- "Custom" option for writing own message
- "Suggested" badge on auto-detected language
- Template info showing what will be sent
- Tip box about automatic channel selection

**What User Does:**
1. Reviews auto-detected language suggestion
2. Selects preferred language OR
3. Clicks "Custom" to write own message (opens CustomMessageModal)

**Validation:** Language selection required (defaults to English)

**Auto-Detection Logic:**
- Parses phone number country code
- Maps country codes to languages:
  - +39 → Italian, +34 → Spanish, +49 → German, etc.
- 30+ country codes supported

---

### STEP 5: Preview & Confirm

| Aspect | Details |
|--------|---------|
| **Purpose** | Review message and delivery channel before sending |
| **File** | `Step5Preview.jsx` |

**What User Sees:**
- **Delivery Channel section**: Shows detected channel(s) with icons
  - WhatsApp icon (green) if available
  - Email icon (blue) + SMS icon if no WhatsApp
- Attachment count summary
- **Message Preview**:
  - Subject line (for email)
  - Message content with replaced variables
  - Recipient info
- If custom message: Shows custom content instead
- "Ready to send?" confirmation box

**What User Does:**
1. Reviews channel detection result
2. Reviews message preview
3. Clicks "Send Ticket" button

**Validation:** Channel detection must complete successfully

---

### STEP 6: Send Status

| Aspect | Details |
|--------|---------|
| **Purpose** | Show send result and next steps |
| **File** | `Step6SendStatus.jsx` |

**What User Sees:**

**On Success:**
- Green checkmark icon
- "Ticket Sent Successfully!" header
- Delivery details: channel used
- Message status badges (sent/queued/delivered)
- "What's Next?" section with guidance

**On Failure:**
- Red X icon
- "Failed to Send Ticket" header
- Error details list
- Retry instructions

**What User Does:**
- On success: Click "Done" to close wizard
- On failure: Go back and retry, or close and investigate

---

## PART 2: TECHNICAL REPORT

### File Structure

```
frontend/src/components/TicketWizard/
├── index.jsx              # Main orchestrator (334 lines)
├── WizardProgress.jsx     # Step indicator component
├── WizardNavigation.jsx   # Back/Next/Send/Done buttons
├── CustomMessageModal.jsx # Custom message composer
├── CustomMessageModal.css
├── TicketWizard.css       # Main styles
└── steps/
    ├── Step1BookingDetails.jsx
    ├── Step2TicketReference.jsx
    ├── Step3FileAttach.jsx
    ├── Step4TemplateSelect.jsx
    ├── Step5Preview.jsx
    └── Step6SendStatus.jsx
```

### Main Component Props (index.jsx)

```jsx
{
  booking: Object,      // Full booking data from API
  onClose: Function,    // Close wizard callback
  onSuccess: Function   // Called after successful send
}
```

### Wizard State Shape

```javascript
{
  currentStep: 1-6,
  referenceNumber: string,
  audioGuideUsername: string,
  audioGuidePassword: string,
  audioGuideUrl: string,
  attachments: Array<{id, filename, size, url}>,
  language: string,              // 'en', 'it', 'custom', etc.
  customMessage: {subject, content} | null,
  channelInfo: {
    primary: 'whatsapp' | 'email',
    fallback: 'sms' | null,
    description: string,
    whatsapp_available: boolean
  },
  preview: {
    previews: {
      whatsapp: {content, recipient},
      email: {subject, content, recipient},
      sms: {content, recipient}
    }
  },
  sendResult: {success, channel_used, messages, errors},
  detectedLanguage: {language, country, confidence},
  isLoading: boolean,
  isSending: boolean
}
```

### API Calls

| Step | API Endpoint | Purpose |
|------|--------------|---------|
| Open | `PUT /bookings/{id}/wizard-progress` | Track wizard started |
| Each step | `PUT /bookings/{id}/wizard-progress` | Track step progress |
| Step 3 | `POST /bookings/{id}/attachments` | Upload PDF to S3 |
| Step 3 | `DELETE /attachments/{id}` | Remove uploaded file |
| Step 4 | `GET /templates/languages` | Get available languages |
| Step 5 | `GET /bookings/{id}/detect-channel` | Check WhatsApp availability |
| Step 5 | `POST /messages/preview` | Generate message preview |
| Step 6 | `POST /bookings/{id}/send-ticket` | Send the ticket |
| Close | `PUT /bookings/{id}/wizard-progress` | Track wizard abandoned (if incomplete) |

### Data Flow

```
User Opens Wizard
       ↓
[Step 1] Display booking data (read-only)
       ↓
[Step 2] Collect reference + audio credentials → local state
       ↓
[Step 3] Upload PDF → S3 → attachment IDs stored
       ↓
[Step 4] Select language → detect from phone → local state
       ↓
[Step 5] detectChannel API → preview API → display previews
       ↓
[Step 6] sendTicket API → show result → update booking status
       ↓
Close Wizard → onSuccess callback → refresh booking list
```

### Validation Functions

```javascript
// Step 2: Reference validation
const validateStep2 = () => {
  const pattern = /^[A-Z0-9]{8}$/;
  return pattern.test(referenceNumber);
}

// Step 2: Audio guide validation
const validateAudioGuide = () => {
  const usernameValid = audioGuideUsername.startsWith('TKE-');
  const passwordValid = /^\d{5}$/.test(audioGuidePassword);
  return usernameValid && passwordValid;
}

// Step 3: File validation
const validateStep3 = () => attachments.length > 0;
```

### Progress Tracking (Backend)

```php
// Database fields
wizard_started_at: timestamp
wizard_last_step: integer (1-6)
wizard_abandoned_at: timestamp | null
```

---

## PART 3: CURRENT LIMITATIONS & GAPS

### 1. **Timed Entry Only**
- Wizard only available for Product ID 961802
- Other products require manual "Add Ticket" flow
- No way to send guided tour tickets through wizard

### 2. **Single Reference Code**
- Only supports 1 reference per booking
- Can't handle split tickets (multiple references for same booking)
- No bulk reference entry

### 3. **WhatsApp Detection Latency**
- Channel detection happens at Step 5 (late in flow)
- User doesn't know delivery channel until preview step
- No way to force a specific channel

### 4. **No Draft/Save**
- Can't save partial progress and return later
- Abandoning wizard loses all entered data
- Must complete in single session

### 5. **Custom Message Limitations**
- Custom messages skip template validation
- No spell check or formatting tools
- Variables must be typed exactly (no autocomplete)

### 6. **Attachment Constraints**
- Only PDF format supported
- 10MB limit per file
- No image attachments for quick photos of tickets

### 7. **No Retry Mechanism**
- Failed sends require manual back-and-retry
- No automatic retry queue
- No partial success handling (e.g., email sent but SMS failed)

### 8. **Missing Features**
- No message scheduling (send later)
- No read receipts tracking in wizard
- No duplicate send protection (can resend if closed accidentally)
- No undo/cancel after send

### 9. **Audio Guide UX**
- Audio guide fields only appear if `has_audio_guide` flag is true
- No way to add audio guide credentials if flag is missing
- Default URL hardcoded

### 10. **Language Limitations**
- Only 10 languages supported
- Some country codes may map incorrectly
- No way to select "no template" and send PDF only

---

## PART 4: CHANNEL LOGIC SUMMARY

### Channel Detection Flow

```
                    ┌──────────────────────────────┐
                    │ Start: Has phone number?     │
                    └──────────────┬───────────────┘
                                   │
              ┌────────────────────┴────────────────────┐
              │ Yes                                      │ No
              ▼                                          ▼
    ┌─────────────────────┐                   ┌─────────────────────┐
    │ Check WhatsApp via  │                   │ Email only          │
    │ Twilio Lookup API   │                   │ (if email exists)   │
    └──────────┬──────────┘                   └─────────────────────┘
               │
    ┌──────────┴──────────────┐
    │ WhatsApp registered?    │
    └──────────┬──────────────┘
               │
    ┌──────────┴──────────┐
    │ Yes                  │ No
    ▼                      ▼
┌──────────────┐   ┌─────────────────────────┐
│ WhatsApp     │   │ Email + SMS notification│
│ (primary)    │   │ (primary + fallback)    │
│ PDF attached │   │ PDF in email, SMS alert │
└──────────────┘   └─────────────────────────┘
```

### Channel Decision Matrix

| Has Phone | Has Email | WhatsApp? | Primary | Fallback |
|-----------|-----------|-----------|---------|----------|
| Yes | Yes | Yes | WhatsApp | None |
| Yes | Yes | No | Email | SMS |
| Yes | No | Yes | WhatsApp | None |
| Yes | No | No | SMS | None |
| No | Yes | N/A | Email | None |
| No | No | N/A | ❌ Error | ❌ |

### Channel Characteristics

| Channel | PDF Support | Character Limit | Cost | Delivery Speed |
|---------|-------------|-----------------|------|----------------|
| WhatsApp | ✅ Yes | 4096 chars | ~$0.005 | Instant |
| Email | ✅ Yes | Unlimited | Free | 1-5 min |
| SMS | ❌ No | 1600 chars | ~$0.007 | Instant |

### API Implementation

```javascript
// Channel detection endpoint
GET /api/bookings/{id}/detect-channel

// Response
{
  "primary": "whatsapp",
  "fallback": null,
  "whatsapp_available": true,
  "description": "WhatsApp is available. Ticket will be sent via WhatsApp."
}

// Or for email+SMS:
{
  "primary": "email",
  "fallback": "sms",
  "whatsapp_available": false,
  "description": "WhatsApp not available. Ticket will be sent via Email with SMS notification."
}
```

### Template Selection by Channel

| Language | WhatsApp Template | Email Template | SMS Template |
|----------|-------------------|----------------|--------------|
| English | ticket_whatsapp_en | ticket_email_en | ticket_sms_en |
| Italian | ticket_whatsapp_it | ticket_email_it | ticket_sms_it |
| Custom | User content | User content | N/A (truncated) |

### Send Flow

```
Step 6: User clicks "Send Ticket"
              ↓
┌─────────────────────────────────┐
│ POST /api/bookings/{id}/send-   │
│ ticket                          │
│ Body: {                         │
│   reference_number,             │
│   language,                     │
│   attachment_ids,               │
│   audio_guide_username,         │
│   audio_guide_password,         │
│   custom_message (if any)       │
│ }                               │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│ Backend determines channel      │
│ based on previous detection     │
└──────────────┬──────────────────┘
               ↓
     ┌─────────┴─────────┐
     ↓                   ↓
[WhatsApp]          [Email + SMS]
     ↓                   ↓
TwilioService      MailService +
.sendWhatsApp()    TwilioService
                   .sendSMS()
     ↓                   ↓
     └─────────┬─────────┘
               ↓
┌─────────────────────────────────┐
│ Update booking:                 │
│ - ticket_status = SENT          │
│ - reference_number = value      │
│ - ticket_sent_at = now          │
│ - delivery_channel = channel    │
└─────────────────────────────────┘
               ↓
┌─────────────────────────────────┐
│ Return response:                │
│ {                               │
│   success: true,                │
│   channel_used: "whatsapp",     │
│   messages: [{status, channel}] │
│ }                               │
└─────────────────────────────────┘
```

---

## SUMMARY

The Ticket Wizard is a well-structured 6-step process optimized for Timed Entry ticket delivery. Its strengths include:
- Phone-based language auto-detection
- Intelligent channel selection (WhatsApp preferred)
- Progress tracking for abandoned wizards
- Custom message support

Areas for improvement include:
- Support for other product types
- Draft saving capability
- Earlier channel detection
- Retry automation

---

*Last updated: January 30, 2026*
