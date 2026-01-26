<?php

namespace App\Http\Controllers;

use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    protected TwilioService $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
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
