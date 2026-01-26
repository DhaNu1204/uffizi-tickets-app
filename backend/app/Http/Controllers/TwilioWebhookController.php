<?php

namespace App\Http\Controllers;

use App\Services\IncomingMessageService;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    protected TwilioService $twilioService;
    protected IncomingMessageService $incomingService;

    public function __construct(TwilioService $twilioService, IncomingMessageService $incomingService)
    {
        $this->twilioService = $twilioService;
        $this->incomingService = $incomingService;
    }

    /**
     * Handle Twilio status callback
     * POST /api/webhooks/twilio/status
     */
    public function status(Request $request): Response
    {
        // Log the incoming webhook
        Log::info('Twilio status webhook received', [
            'data' => $request->all(),
        ]);

        // Validate request is from Twilio (optional but recommended)
        if (!$this->validateTwilioRequest($request)) {
            Log::warning('Invalid Twilio webhook signature');
            return response('Unauthorized', 401);
        }

        try {
            $this->twilioService->handleStatusCallback($request->all());

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Failed to process Twilio webhook', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            // Return 200 to prevent Twilio retries
            return response('OK', 200);
        }
    }

    /**
     * Handle incoming WhatsApp/SMS message
     * POST /api/webhooks/twilio/incoming
     */
    public function incoming(Request $request): Response
    {
        // Log the incoming webhook
        Log::info('Twilio incoming webhook received', [
            'from' => $request->input('From'),
            'to' => $request->input('To'),
            'body' => substr($request->input('Body', ''), 0, 100), // Log first 100 chars
        ]);

        // Validate request is from Twilio
        if (!$this->validateTwilioRequest($request)) {
            Log::warning('Invalid Twilio incoming webhook signature');
            return response('Unauthorized', 401);
        }

        try {
            // Process the incoming message
            $message = $this->incomingService->handleIncoming($request->all());

            Log::info('Incoming message processed', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
            ]);

            // Return empty TwiML response (acknowledge receipt)
            return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
                ->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Failed to process incoming message', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            // Return 200 to prevent Twilio retries
            return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
                ->header('Content-Type', 'text/xml');
        }
    }

    /**
     * Validate that request is from Twilio
     */
    protected function validateTwilioRequest(Request $request): bool
    {
        // Skip validation if signature header not present
        $signature = $request->header('X-Twilio-Signature');
        if (!$signature) {
            // In development, allow requests without signature
            if (config('app.debug')) {
                return true;
            }
            return false;
        }

        $authToken = config('services.twilio.auth_token');
        if (!$authToken) {
            return false;
        }

        // Build the validation URL
        $url = $request->fullUrl();

        // Sort the POST parameters alphabetically
        $params = $request->all();
        ksort($params);

        // Concatenate URL and params
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        // Calculate expected signature
        $expectedSignature = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expectedSignature, $signature);
    }
}
