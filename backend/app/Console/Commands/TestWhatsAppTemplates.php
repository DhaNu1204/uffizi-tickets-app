<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;

class TestWhatsAppTemplates extends Command
{
    protected $signature = 'whatsapp:test-templates
                            {--type=ticket_audio_pdf : Template type (ticket_pdf or ticket_audio_pdf)}
                            {--phone= : Phone number to test (E.164 format)}
                            {--language= : Specific language to test (en, it, es, etc.)}';

    protected $description = 'Test WhatsApp Content Templates to verify they work';

    public function handle(): int
    {
        $templateType = $this->option('type');
        $testPhone = $this->option('phone');
        $specificLang = $this->option('language');

        if (!$testPhone) {
            $this->error('Please provide a test phone number with --phone option');
            return 1;
        }

        $templates = config("whatsapp_templates.{$templateType}", []);

        if (empty($templates)) {
            $this->error("No templates found for type: {$templateType}");
            return 1;
        }

        // Filter to specific language if provided
        if ($specificLang) {
            if (!isset($templates[$specificLang])) {
                $this->error("Language '{$specificLang}' not found in {$templateType}");
                return 1;
            }
            $templates = [$specificLang => $templates[$specificLang]];
        }

        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $whatsappFrom = config('services.twilio.whatsapp_from');

        if (!$accountSid || !$authToken) {
            $this->error('Twilio credentials not configured');
            return 1;
        }

        $client = new TwilioClient($accountSid, $authToken);

        $this->info("Testing {$templateType} templates...");
        $this->info("Phone: {$testPhone}");
        $this->newLine();

        $results = [
            'success' => [],
            'failed' => [],
        ];

        // Test variables - same structure as real messages
        $testVariables = [
            '1' => 'Test Customer',
            '2' => 'January 30, 2026 at 10:00 AM',
            '3' => 'https://uffizi.florencewithlocals.com',
            '4' => 'https://uffizi.florencewithlocals.com/know-before-you-go',
            '5' => 'https://uffizi.florencewithlocals.com/sample-ticket.pdf', // Public test URL
        ];

        foreach ($templates as $lang => $contentSid) {
            $this->info("Testing {$lang}: {$contentSid}");

            try {
                $message = $client->messages->create(
                    "whatsapp:{$testPhone}",
                    [
                        'from' => "whatsapp:{$whatsappFrom}",
                        'contentSid' => $contentSid,
                        'contentVariables' => json_encode($testVariables),
                    ]
                );

                $this->info("  ✓ SUCCESS - SID: {$message->sid}");
                $results['success'][] = $lang;

            } catch (TwilioException $e) {
                $errorCode = $e->getCode();
                $errorMsg = $e->getMessage();
                $this->error("  ✗ FAILED - Error {$errorCode}: {$errorMsg}");
                $results['failed'][] = [
                    'lang' => $lang,
                    'sid' => $contentSid,
                    'error_code' => $errorCode,
                    'error_message' => $errorMsg,
                ];
            }

            // Small delay between tests
            usleep(500000); // 0.5 seconds
        }

        $this->newLine();
        $this->info('=== RESULTS SUMMARY ===');
        $this->info('Template Type: ' . $templateType);
        $this->newLine();

        $this->info('✓ WORKING (' . count($results['success']) . '):');
        foreach ($results['success'] as $lang) {
            $this->info("  - {$lang}");
        }

        $this->newLine();
        $this->error('✗ FAILED (' . count($results['failed']) . '):');
        foreach ($results['failed'] as $fail) {
            $this->error("  - {$fail['lang']}: {$fail['sid']}");
            $this->error("    Error {$fail['error_code']}: {$fail['error_message']}");
        }

        return 0;
    }
}
