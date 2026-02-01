<?php
/**
 * Test Script: Full WhatsApp PDF Flow
 *
 * Tests the complete flow through MessagingService -> TwilioService
 * using the new media templates with PDF support.
 *
 * Run: php tests/test_full_flow.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     TESTING FULL WHATSAPP + PDF FLOW                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// 1. Verify config is loaded
echo "=== Step 1: Verify Config ===\n";
$ticketPdfEn = config('whatsapp_templates.ticket_pdf.en');
$ticketAudioPdfEn = config('whatsapp_templates.ticket_audio_pdf.en');

echo "ticket_pdf.en: " . ($ticketPdfEn ?? 'NOT FOUND') . "\n";
echo "ticket_audio_pdf.en: " . ($ticketAudioPdfEn ?? 'NOT FOUND') . "\n";

if (!$ticketPdfEn || !$ticketAudioPdfEn) {
    die("\n❌ Config not loaded correctly!\n");
}
echo "✅ Config loaded correctly\n\n";

// 2. Get a booking with attachments
echo "=== Step 2: Find Test Booking ===\n";
$attachment = \App\Models\MessageAttachment::latest()->first();

if (!$attachment) {
    die("❌ No attachments found. Please upload a PDF first.\n");
}

$booking = $attachment->booking;
if (!$booking) {
    die("❌ Attachment has no associated booking.\n");
}

echo "Booking ID: {$booking->id}\n";
echo "Customer: {$booking->customer_name}\n";
echo "Phone: {$booking->customer_phone}\n";
echo "Has Audio Guide: " . ($booking->has_audio_guide ? 'Yes' : 'No') . "\n";
echo "Attachment: {$attachment->original_name}\n";
echo "✅ Test booking found\n\n";

// 3. Test TwilioService directly
echo "=== Step 3: Test TwilioService ===\n";
$twilioService = app(\App\Services\TwilioService::class);

// Get template SID
$templateSid = $twilioService->getWhatsAppTemplateSid('en', $booking->has_audio_guide, true);
echo "Template SID: {$templateSid}\n";

// Generate PDF URL
$pdfUrl = $attachment->getTemporaryUrl(60);
echo "PDF URL generated: " . ($pdfUrl ? '✅ Yes' : '❌ No') . "\n";

// Build variables
$variables = $twilioService->buildTemplateVariables($booking, $booking->has_audio_guide, $pdfUrl);
echo "Variables count: " . count($variables) . "\n";
echo "  {{1}}: {$variables['1']}\n";
echo "  {{2}}: {$variables['2']}\n";
echo "  {{3}}: " . substr($variables['3'], 0, 50) . "...\n";
echo "  {{4}}: " . substr($variables['4'], 0, 50) . "...\n";
echo "  {{5}}: " . (isset($variables['5']) ? 'PDF URL set ✅' : 'MISSING ❌') . "\n";
echo "\n";

// 4. Confirm ready to send
echo "=== Step 4: Ready to Send ===\n";
echo "Target Phone: +393401520611\n";
echo "Template: " . ($booking->has_audio_guide ? 'ticket_audio_pdf' : 'ticket_pdf') . "\n";
echo "Language: en\n";
echo "\n";

echo "Do you want to send a test message? (y/n): ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));

if (strtolower($input) !== 'y') {
    echo "Test cancelled.\n";
    exit(0);
}

// 5. Send test message
echo "\n=== Step 5: Sending Message ===\n";

// Override phone for testing
$originalPhone = $booking->customer_phone;
$booking->customer_phone = '+393401520611';

try {
    // Create a template object
    $template = new \App\Models\MessageTemplate([
        'channel' => 'whatsapp',
        'language' => 'en',
        'content' => 'Test message',
    ]);

    $message = $twilioService->sendWhatsApp($booking, $template, [$attachment]);

    echo "✅ SUCCESS!\n";
    echo "Message ID: {$message->id}\n";
    echo "Twilio SID: {$message->external_id}\n";
    echo "Status: {$message->status}\n";
    echo "\nCheck WhatsApp for the message with PDF!\n";

} catch (\Exception $e) {
    echo "❌ FAILED!\n";
    echo "Error: {$e->getMessage()}\n";
} finally {
    // Restore original phone
    $booking->customer_phone = $originalPhone;
}

echo "\n";
