<?php

namespace App\Http\Controllers;

use App\Services\ManualMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ManualMessageController extends Controller
{
    protected ManualMessageService $manualMessageService;

    public function __construct(ManualMessageService $manualMessageService)
    {
        $this->manualMessageService = $manualMessageService;
    }

    /**
     * Send a manual message
     * POST /api/messages/send-manual
     */
    public function send(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'channel' => 'required|in:whatsapp,sms,email',
            'recipient' => 'required|string',
            'message' => 'required|string|min:10',
            'subject' => 'required_if:channel,email|nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
        ], [
            'channel.required' => 'Please select a channel (WhatsApp, SMS, or Email)',
            'channel.in' => 'Invalid channel. Must be whatsapp, sms, or email',
            'recipient.required' => 'Please enter a recipient',
            'message.required' => 'Please enter a message',
            'message.min' => 'Message must be at least 10 characters',
            'subject.required_if' => 'Subject is required for email messages',
            'attachment.mimes' => 'Attachment must be a PDF file',
            'attachment.max' => 'Attachment must be less than 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $channel = $request->input('channel');
        $recipient = $request->input('recipient');
        $message = $request->input('message');
        $subject = $request->input('subject');
        $attachment = $request->file('attachment');

        // Additional validation based on channel
        if (in_array($channel, ['whatsapp', 'sms'])) {
            // Validate phone number format
            $phoneError = $this->getPhoneValidationError($recipient);
            if ($phoneError) {
                return response()->json([
                    'success' => false,
                    'errors' => [$phoneError],
                ], 422);
            }
        } elseif ($channel === 'email') {
            // Validate email format
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Please enter a valid email address'],
                ], 422);
            }
        }

        // SMS doesn't support attachments
        if ($channel === 'sms' && $attachment) {
            return response()->json([
                'success' => false,
                'errors' => ['SMS does not support attachments. Please use WhatsApp or Email for sending files.'],
            ], 422);
        }

        try {
            $sentMessage = null;

            switch ($channel) {
                case 'whatsapp':
                    $sentMessage = $this->manualMessageService->sendWhatsApp($recipient, $message, $attachment);
                    break;

                case 'sms':
                    $sentMessage = $this->manualMessageService->sendSms($recipient, $message);
                    break;

                case 'email':
                    $sentMessage = $this->manualMessageService->sendEmail($recipient, $subject, $message, $attachment);
                    break;
            }

            Log::info('Manual message sent', [
                'channel' => $channel,
                'recipient' => $recipient,
                'message_id' => $sentMessage->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $sentMessage->id,
                    'channel' => $sentMessage->channel,
                    'recipient' => $sentMessage->recipient,
                    'status' => $sentMessage->status,
                    'sent_at' => $sentMessage->sent_at?->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send manual message', [
                'channel' => $channel,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to send message: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Validate phone number format
     */
    protected function isValidPhone(string $phone): bool
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Must start with + or have enough digits for international format
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        // Check for reasonable length (10-15 digits total including country code)
        $digitsOnly = preg_replace('/\D/', '', $cleaned);
        $length = strlen($digitsOnly);

        // Most valid international numbers have 10-15 digits
        // Country code (1-3) + national number (7-12)
        return $length >= 10 && $length <= 15;
    }

    /**
     * Get additional phone validation details
     */
    protected function getPhoneValidationError(string $phone): ?string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        $digitsOnly = preg_replace('/\D/', '', $cleaned);
        $length = strlen($digitsOnly);

        // Most international mobile numbers have 11-13 digits:
        // Italy: +39 333 123 4567 = 12 digits
        // Belgium: +32 472 49 12 82 = 11 digits
        // USA: +1 555 123 4567 = 11 digits
        // UK: +44 7700 900123 = 12 digits
        if ($length < 11) {
            return "Phone number too short ({$length} digits). International mobile numbers typically have 11-13 digits. Examples: +39 333 123 4567 (Italy), +32 472 12 34 56 (Belgium), +1 555 123 4567 (USA)";
        }

        if ($length > 15) {
            return "Phone number too long ({$length} digits). Please check the number format.";
        }

        return null;
    }

    /**
     * Get message history (manual messages without booking)
     * GET /api/messages/manual-history
     */
    public function history(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 100);

        $messages = \App\Models\Message::whereNull('booking_id')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'channel' => $msg->channel,
                    'recipient' => $msg->recipient,
                    'subject' => $msg->subject,
                    'content' => $msg->content,
                    'status' => $msg->status,
                    'error_message' => $msg->error_message,
                    'sent_at' => $msg->sent_at?->toIso8601String(),
                    'delivered_at' => $msg->delivered_at?->toIso8601String(),
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Sync message statuses from Twilio
     * POST /api/messages/sync-status
     */
    public function syncStatus(): JsonResponse
    {
        try {
            $client = new \Twilio\Rest\Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );

            // Get pending/sent messages from last 24 hours
            $messages = \App\Models\Message::whereNull('booking_id')
                ->whereNotNull('external_id')
                ->whereIn('status', ['sent', 'queued', 'pending'])
                ->where('created_at', '>=', now()->subDay())
                ->get();

            $updated = 0;

            foreach ($messages as $msg) {
                try {
                    $twilioMsg = $client->messages($msg->external_id)->fetch();

                    if ($twilioMsg->status === 'delivered') {
                        $msg->markDelivered();
                        $updated++;
                    } elseif ($twilioMsg->status === 'read') {
                        $msg->markRead();
                        $updated++;
                    } elseif (in_array($twilioMsg->status, ['failed', 'undelivered'])) {
                        $errorMsg = $twilioMsg->errorCode
                            ? "Error {$twilioMsg->errorCode}"
                            : "Status: {$twilioMsg->status}";
                        $msg->markFailed($errorMsg);
                        $updated++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to sync message status', [
                        'message_id' => $msg->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$updated} message(s)",
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync message statuses', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to sync statuses: ' . $e->getMessage()],
            ], 500);
        }
    }
}
