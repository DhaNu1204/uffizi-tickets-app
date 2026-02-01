<?php
/**
 * Test Script: WhatsApp Media Template with PDF Attachment
 *
 * Tests the new approved media template that supports PDF via variable {{5}}
 *
 * Template: ticket_pdf_delivery_en
 * SID: HX50c5e100ce4cff2beaa057009519b8b3
 * Variables:
 *   {{1}} = Customer name
 *   {{2}} = Entry datetime
 *   {{3}} = Online guide URL
 *   {{4}} = Know before you go URL
 *   {{5}} = PDF attachment URL
 *
 * Run: php tests/test_media_template.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Twilio\Rest\Client;

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     TESTING WHATSAPP MEDIA TEMPLATE WITH PDF ATTACHMENT          ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// Twilio config
$accountSid = config('services.twilio.account_sid');
$authToken = config('services.twilio.auth_token');
$whatsappFrom = config('services.twilio.whatsapp_from');

if (empty($accountSid) || empty($authToken)) {
    die("❌ ERROR: Twilio credentials not configured in .env\n");
}

$client = new Client($accountSid, $authToken);

// Test recipient
$to = '+393272491282';

// New Media Template SID (approved with PDF support)
$contentSid = 'HX50c5e100ce4cff2beaa057009519b8b3';

echo "Configuration:\n";
echo "  From: whatsapp:{$whatsappFrom}\n";
echo "  To:   whatsapp:{$to}\n";
echo "  Template SID: {$contentSid}\n\n";

// Get a real PDF attachment and generate S3 URL
echo "Loading PDF attachment...\n";
$attachment = \App\Models\MessageAttachment::latest()->first();

if (!$attachment) {
    die("❌ No attachments found. Please upload a PDF first.\n");
}

echo "  Attachment ID: {$attachment->id}\n";
echo "  Filename: {$attachment->original_name}\n";
echo "  Disk: {$attachment->disk}\n";
echo "  Size: " . number_format($attachment->size / 1024, 2) . " KB\n";

// Generate pre-signed URL (60 minute expiry)
$pdfUrl = $attachment->getTemporaryUrl(60);

if (!$pdfUrl) {
    die("❌ Could not generate PDF URL. Check S3 configuration.\n");
}

echo "  URL Generated: ✅\n";
echo "  URL Preview: " . substr($pdfUrl, 0, 80) . "...\n\n";

// Variables for template (5 variables)
$variables = [
    '1' => 'Test Customer',
    '2' => 'February 5, 2026 at 10:00 AM',
    '3' => 'https://uffizi.florencewithlocals.com',
    '4' => 'https://florencewithlocals.com/uffizi-know-before-you-go',
    '5' => $pdfUrl,
];

echo "Template Variables:\n";
echo "  {{1}} Name: {$variables['1']}\n";
echo "  {{2}} DateTime: {$variables['2']}\n";
echo "  {{3}} Guide URL: {$variables['3']}\n";
echo "  {{4}} Info URL: {$variables['4']}\n";
echo "  {{5}} PDF URL: [S3 Pre-signed URL]\n\n";

echo "═══════════════════════════════════════════════════════════════════\n";
echo "SENDING MESSAGE...\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

try {
    $message = $client->messages->create(
        "whatsapp:{$to}",
        [
            'from' => "whatsapp:{$whatsappFrom}",
            'contentSid' => $contentSid,
            'contentVariables' => json_encode($variables),
        ]
    );

    echo "✅ SUCCESS!\n\n";
    echo "  Message SID: {$message->sid}\n";
    echo "  Status: {$message->status}\n";
    echo "  Date Created: {$message->dateCreated->format('Y-m-d H:i:s')}\n";
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 Check your WhatsApp for the message with PDF attachment!   ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";

} catch (\Twilio\Exceptions\RestException $e) {
    echo "❌ TWILIO API ERROR!\n\n";
    echo "  Error Code: {$e->getCode()}\n";
    echo "  Status: {$e->getStatusCode()}\n";
    echo "  Message: {$e->getMessage()}\n";

    // Check for common errors
    if ($e->getCode() == 63016) {
        echo "\n  ⚠️  Template may not be approved yet or has wrong format.\n";
    } elseif ($e->getCode() == 63024) {
        echo "\n  ⚠️  Template parameter mismatch. Check variable count.\n";
    } elseif (strpos($e->getMessage(), 'media') !== false) {
        echo "\n  ⚠️  Media URL issue. Check if PDF URL is accessible.\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR!\n\n";
    echo "  Type: " . get_class($e) . "\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Code: {$e->getCode()}\n";
}

echo "\n";
