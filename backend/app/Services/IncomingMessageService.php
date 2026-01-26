<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class IncomingMessageService
{
    /**
     * Handle an incoming Twilio webhook for WhatsApp/SMS messages
     *
     * @param array $data The webhook payload from Twilio
     * @return Message The created message record
     */
    public function handleIncoming(array $data): Message
    {
        // Extract message details from Twilio webhook
        $from = $data['From'] ?? '';
        $body = $data['Body'] ?? '';
        $messageSid = $data['MessageSid'] ?? null;
        $profileName = $data['ProfileName'] ?? null; // WhatsApp profile name

        // Determine channel from the 'From' field
        $channel = $this->determineChannel($from);

        // Normalize the phone number (remove whatsapp: prefix if present)
        $phoneNumber = $this->extractPhoneNumber($from);

        Log::info('Incoming message received', [
            'from' => $from,
            'phone' => $phoneNumber,
            'channel' => $channel,
            'message_sid' => $messageSid,
            'profile_name' => $profileName,
        ]);

        // Find or create conversation
        $conversation = Conversation::findOrCreateByPhone($phoneNumber, $channel);

        // Try to find a matching booking if conversation doesn't have one
        if (!$conversation->booking_id) {
            $booking = $this->findBookingByPhone($phoneNumber);
            if ($booking) {
                $conversation->update(['booking_id' => $booking->id]);
            }
        }

        // Create the incoming message record
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'booking_id' => $conversation->booking_id,
            'channel' => $channel,
            'direction' => Message::DIRECTION_INBOUND,
            'external_id' => $messageSid,
            'recipient' => $phoneNumber, // Store as "recipient" even though it's the sender
            'sender_name' => $profileName,
            'content' => $body,
            'status' => Message::STATUS_DELIVERED, // Inbound messages are already delivered
            'delivered_at' => now(),
        ]);

        // Update conversation metadata
        $conversation->incrementUnread();

        // Reactivate if archived
        if ($conversation->status === Conversation::STATUS_ARCHIVED) {
            $conversation->reactivate();
        }

        Log::info('Incoming message stored', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'booking_id' => $conversation->booking_id,
        ]);

        return $message;
    }

    /**
     * Determine the channel from the Twilio 'From' field
     */
    protected function determineChannel(string $from): string
    {
        if (str_starts_with($from, 'whatsapp:')) {
            return Conversation::CHANNEL_WHATSAPP;
        }

        return Conversation::CHANNEL_SMS;
    }

    /**
     * Extract phone number from Twilio 'From' field
     */
    protected function extractPhoneNumber(string $from): string
    {
        // Remove whatsapp: prefix if present
        if (str_starts_with($from, 'whatsapp:')) {
            $from = substr($from, 9);
        }

        return Conversation::normalizePhoneNumber($from);
    }

    /**
     * Find a booking by phone number
     */
    protected function findBookingByPhone(string $phone): ?Booking
    {
        // Try exact match first
        $booking = Booking::where('customer_phone', $phone)
            ->orderBy('tour_date', 'desc')
            ->first();

        if ($booking) {
            return $booking;
        }

        // Try partial match (last 10 digits)
        $digits = preg_replace('/\D/', '', $phone);
        $lastDigits = substr($digits, -10);

        return Booking::whereRaw(
            "REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '+', '') LIKE ?",
            ["%{$lastDigits}"]
        )
            ->orderBy('tour_date', 'desc')
            ->first();
    }

    /**
     * Link a conversation to a booking
     */
    public function linkToBooking(Conversation $conversation, int $bookingId): void
    {
        $conversation->update(['booking_id' => $bookingId]);

        // Also update any messages in the conversation
        Message::where('conversation_id', $conversation->id)
            ->whereNull('booking_id')
            ->update(['booking_id' => $bookingId]);

        Log::info('Conversation linked to booking', [
            'conversation_id' => $conversation->id,
            'booking_id' => $bookingId,
        ]);
    }
}
