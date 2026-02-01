<?php
/**
 * Test Script: WhatsApp Content Template + Media Options
 *
 * IMPORTANT FINDING FROM TWILIO DOCS:
 * "When using ContentSid, Body and MediaUrl should be excluded.
 *  They are not required and are both superseded by the ContentSid."
 *
 * This means contentSid + mediaUrl CANNOT be combined dynamically!
 *
 * SOLUTION: Create Content Templates with MEDIA HEADER that accepts
 * a variable URL for the document/PDF.
 *
 * This script tests:
 * 1. Content Template alone (known to work)
 * 2. Content Template + mediaUrl (expected to FAIL or ignore mediaUrl)
 * 3. Body + mediaUrl (only works within 24-hour session window)
 *
 * Run: php tests/test_whatsapp_with_pdf.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Twilio\Rest\Client;

$accountSid = config('services.twilio.account_sid');
$authToken = config('services.twilio.auth_token');
$whatsappFrom = config('services.twilio.whatsapp_from');

if (empty($accountSid) || empty($authToken)) {
    echo "❌ ERROR: Twilio credentials not configured in .env\n";
    exit(1);
}

$client = new Client($accountSid, $authToken);

// Test phone number - CHANGE THIS to your test number
$to = '+393272491282';

// Content Template SID (English ticket_only from config/whatsapp_templates.php)
$contentSid = 'HX903d5ba5ab918c0a41f0a0613054adc9';

// Variables for template
$variables = [
    '1' => 'Test Customer',
    '2' => 'February 1, 2026 at 10:00 AM',
    '3' => 'https://uffizi.florencewithlocals.com',
    '4' => 'https://florencewithlocals.com/uffizi-know-before-you-go',
];

// Test PDF URL (publicly accessible PDF for testing)
$pdfUrl = 'https://www.w3.org/WAI/WCAG21/Techniques/pdf/img/table-word.pdf';

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     TWILIO WHATSAPP: contentSid + mediaUrl COMPATIBILITY TEST    ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "Configuration:\n";
echo "  From: whatsapp:{$whatsappFrom}\n";
echo "  To:   whatsapp:{$to}\n";
echo "  Content SID: {$contentSid}\n";
echo "  PDF URL: {$pdfUrl}\n\n";

// ============================================================================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "TEST 1: Content Template WITHOUT media (baseline - should work)\n";
echo "═══════════════════════════════════════════════════════════════════\n";

try {
    $message1 = $client->messages->create(
        "whatsapp:{$to}",
        [
            'from' => "whatsapp:{$whatsappFrom}",
            'contentSid' => $contentSid,
            'contentVariables' => json_encode($variables),
        ]
    );
    echo "✅ SUCCESS\n";
    echo "   SID: {$message1->sid}\n";
    echo "   Status: {$message1->status}\n\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   Code: {$e->getCode()}\n\n";
}

sleep(2); // Avoid rate limiting

// ============================================================================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "TEST 2: Content Template WITH mediaUrl (THE KEY TEST!)\n";
echo "═══════════════════════════════════════════════════════════════════\n";

try {
    $message2 = $client->messages->create(
        "whatsapp:{$to}",
        [
            'from' => "whatsapp:{$whatsappFrom}",
            'contentSid' => $contentSid,
            'contentVariables' => json_encode($variables),
            'mediaUrl' => [$pdfUrl],
        ]
    );
    echo "✅ SUCCESS - contentSid + mediaUrl WORKS!\n";
    echo "   SID: {$message2->sid}\n";
    echo "   Status: {$message2->status}\n";
    echo "\n";
    echo "   ╔════════════════════════════════════════════════════════╗\n";
    echo "   ║  🎉 GREAT NEWS! We CAN send PDFs with Content Templates! ║\n";
    echo "   ╚════════════════════════════════════════════════════════╝\n\n";
} catch (\Twilio\Exceptions\RestException $e) {
    echo "❌ FAILED\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   Code: {$e->getCode()}\n";
    echo "   Status: {$e->getStatusCode()}\n";

    // Check for specific error codes
    if (strpos($e->getMessage(), 'mediaUrl') !== false ||
        strpos($e->getMessage(), 'content') !== false) {
        echo "\n   ⚠️  This confirms contentSid and mediaUrl are INCOMPATIBLE.\n";
        echo "   ⚠️  We need to use a Content Template with media header instead.\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   Code: {$e->getCode()}\n\n";
}

sleep(2);

// ============================================================================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "TEST 3: Body + mediaUrl (free-form with PDF)\n";
echo "        Note: Only works within 24-hour session window\n";
echo "═══════════════════════════════════════════════════════════════════\n";

try {
    $message3 = $client->messages->create(
        "whatsapp:{$to}",
        [
            'from' => "whatsapp:{$whatsappFrom}",
            'body' => "🎫 Test message with PDF attachment\n\nThis is a test to verify PDF delivery via WhatsApp.",
            'mediaUrl' => [$pdfUrl],
        ]
    );
    echo "✅ SUCCESS\n";
    echo "   SID: {$message3->sid}\n";
    echo "   Status: {$message3->status}\n";
    echo "   Note: This only works within 24-hour session window.\n\n";
} catch (\Twilio\Exceptions\RestException $e) {
    echo "❌ FAILED\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   Code: {$e->getCode()}\n";

    if ($e->getCode() == 63016 || strpos($e->getMessage(), 'session') !== false) {
        echo "   ⚠️  Expected failure - outside 24-hour session window.\n";
        echo "   ⚠️  Customer needs to message first to open session.\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ FAILED\n";
    echo "   Error: {$e->getMessage()}\n\n";
}

// ============================================================================
echo "═══════════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Check your WhatsApp for the test messages.\n";
echo "\n";
echo "If TEST 2 succeeded:\n";
echo "  → Update TwilioService.php to include mediaUrl with contentSid\n";
echo "\n";
echo "If TEST 2 failed:\n";
echo "  → Need to create Content Template with media/document header\n";
echo "  → Or continue using email backup for PDFs\n";
echo "\n";
