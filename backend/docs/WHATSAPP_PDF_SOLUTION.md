# WhatsApp PDF Attachment Solution

## The Problem

PDF attachments are not arriving with WhatsApp messages.

## Root Cause (Confirmed by Twilio Documentation)

> "When using ContentSid, Body and MediaUrl should be excluded. They are not required and are both superseded by the ContentSid."

**You CANNOT combine `contentSid` + `mediaUrl` dynamically.** The current code's assumption was correct.

## The Solution

Create **Content Templates with a Document/Media Header** that accepts a variable URL.

---

## Step-by-Step Implementation

### Step 1: Create New Content Templates in Twilio Console

Go to: **Twilio Console → Messaging → Content Template Builder**

Create a new template with:
- **Type**: `whatsapp/document` (or `twilio/media`)
- **Document URL**: Use a variable like `{{5}}` for the PDF URL
- **Body**: Your existing message text with variables

Example template structure:
```json
{
  "friendly_name": "uffizi_ticket_with_pdf_en",
  "language": "en",
  "types": {
    "twilio/media": {
      "body": "Hello {{1}}! 🎫\n\nYour Uffizi Gallery tickets for {{2}} are attached.\n\n📱 Online Guide: {{3}}\n📖 Know Before You Go: {{4}}",
      "media": ["{{5}}"]
    }
  }
}
```

### Step 2: Wait for Meta Approval

WhatsApp templates with media require Meta approval (24-48 hours).

### Step 3: Update whatsapp_templates.php

Add the new template SIDs:

```php
// config/whatsapp_templates.php

'ticket_with_pdf' => [
    'en' => 'HXxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // New template with PDF support
    'it' => 'HXxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    // ... other languages
],
```

### Step 4: Update TwilioService.php

Modify `buildTemplateVariables()` to include the PDF URL:

```php
public function buildTemplateVariables(Booking $booking, bool $hasAudioGuide, ?string $pdfUrl = null): array
{
    $entryDatetime = $booking->tour_date
        ? $booking->tour_date->format('F j, Y') . ' at ' . ($booking->tour_time ?? '10:00 AM')
        : 'Your scheduled time';

    $onlineGuideUrl = config('whatsapp_templates.urls.online_guide');
    $knowBeforeYouGoUrl = config('whatsapp_templates.urls.know_before_you_go');

    $variables = [
        '1' => $booking->customer_name ?? 'Guest',
        '2' => $entryDatetime,
        '3' => $hasAudioGuide
            ? ($booking->vox_dynamic_link ?? $booking->audio_guide_url ?? $onlineGuideUrl)
            : $onlineGuideUrl,
        '4' => $knowBeforeYouGoUrl,
    ];

    // Add PDF URL as variable 5 if provided
    if ($pdfUrl) {
        $variables['5'] = $pdfUrl;
    }

    return $variables;
}
```

### Step 5: Update sendWhatsApp() Method

Pass the PDF URL to the template:

```php
public function sendWhatsApp(
    Booking $booking,
    MessageTemplate $template,
    array $attachments = []
): Message {
    // ... existing code ...

    // Get first attachment URL for template
    $pdfUrl = null;
    if (!empty($attachments)) {
        $firstAttachment = reset($attachments);
        if ($firstAttachment instanceof MessageAttachment) {
            $pdfUrl = $firstAttachment->getTemporaryUrl(60); // 60 min expiry
        }
    }

    // Use PDF template if we have a PDF, otherwise use text-only template
    $templateType = $pdfUrl ? 'ticket_with_pdf' : ($hasAudioGuide ? 'ticket_with_audio' : 'ticket_only');
    $contentSid = $this->getWhatsAppTemplateSid($template->language, $templateType);

    // Build variables including PDF URL
    $contentVariables = $this->buildTemplateVariables($booking, $hasAudioGuide, $pdfUrl);

    // ... send via Twilio ...
}
```

---

## Alternative: Use Media Content Type

If you want to send just the PDF without template text, use `twilio/media`:

```php
// Create a media-only content template
$mediaTemplate = [
    'friendly_name' => 'uffizi_ticket_pdf_only',
    'types' => [
        'twilio/media' => [
            'media' => ['{{1}}'],  // PDF URL variable
            'body' => 'Your Uffizi Gallery tickets are attached.'
        }
    ]
];
```

---

## Current Workaround (Email Backup)

Until the new templates are approved, the system uses:

1. **WhatsApp**: Sends text message only (via Content Template)
2. **Email**: Sends PDF attachment as backup

This is handled in `MessagingService.php`:

```php
// WhatsApp Content Templates don't support dynamic media attachments
// Send PDF via email as backup if customer has email
if ($hasEmail && $attachments->count() > 0) {
    // Send email with PDF...
}
```

---

## Testing

Run the test script to verify behavior:

```bash
cd backend
php tests/test_whatsapp_with_pdf.php
```

---

## References

- [Twilio Content API](https://www.twilio.com/docs/content)
- [twilio/media content type](https://www.twilio.com/docs/content/twilio-media)
- [WhatsApp Media Messages](https://www.twilio.com/docs/whatsapp/guidance-whatsapp-media-messages)
- [Content Template Builder](https://www.twilio.com/docs/content/send-templates-created-with-the-content-template-builder)

---

## Summary

| Approach | Works? | Notes |
|----------|--------|-------|
| `contentSid` alone | ✅ | Text message only |
| `contentSid` + `mediaUrl` | ❌ | MediaUrl is ignored |
| `body` + `mediaUrl` | ✅* | Only within 24-hour session |
| Content Template with media header | ✅ | Requires new template approval |
| Email backup | ✅ | Current workaround |

**Recommendation**: Create new Content Templates with `twilio/media` type that include a variable for the PDF URL.
