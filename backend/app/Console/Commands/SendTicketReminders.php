<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\TwilioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendTicketReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:remind
                            {--dry-run : Show what would be sent without actually sending}
                            {--force : Send even if outside scheduled hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders to admins about unsent tickets for today';

    /**
     * Admin phone numbers to notify
     */
    protected array $adminPhones = [
        '+393272491282',
        '+393401520611',
    ];

    /**
     * Scheduled reminder times (Florence timezone: Europe/Rome)
     */
    protected array $reminderTimes = [
        '07:00',
        '11:00',
        '14:00',
    ];

    /**
     * Execute the console command.
     */
    public function handle(TwilioService $twilioService): int
    {
        $now = Carbon::now('Europe/Rome');
        $currentTime = $now->format('H:i');
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        // Check if we're within a reminder window (±5 minutes of scheduled time)
        $shouldSend = $isForced;
        if (!$isForced) {
            foreach ($this->reminderTimes as $time) {
                $scheduledTime = Carbon::parse($time, 'Europe/Rome');
                $diffMinutes = abs($now->diffInMinutes($scheduledTime));
                if ($diffMinutes <= 5) {
                    $shouldSend = true;
                    break;
                }
            }
        }

        if (!$shouldSend) {
            $this->info("Not within reminder window. Current time: {$currentTime}");
            $this->info("Scheduled times: " . implode(', ', $this->reminderTimes));
            return 0;
        }

        // Get today's date in Florence timezone
        $today = $now->toDateString();

        // Find unsent Timed Entry tickets for today
        $unsentBookings = Booking::where('bokun_product_id', Booking::TIMED_ENTRY_PRODUCT_ID)
            ->whereDate('tour_date', $today)
            ->whereNull('tickets_sent_at')
            ->whereNull('cancelled_at')
            ->orderBy('tour_date')
            ->get();

        $count = $unsentBookings->count();

        if ($count === 0) {
            $this->info("No unsent tickets for today ({$today}).");
            Log::info('Ticket reminder: No unsent tickets for today', ['date' => $today]);
            return 0;
        }

        // Group by time slot
        $byTimeSlot = $unsentBookings->groupBy(function ($booking) {
            return Carbon::parse($booking->tour_date)->format('H:i');
        });

        // Build message
        $message = "🎫 *Uffizi Ticket Reminder*\n\n";
        $message .= "You have *{$count} unsent ticket(s)* for today ({$now->format('D, M j')}):\n\n";

        foreach ($byTimeSlot as $time => $bookings) {
            $slotCount = $bookings->count();
            $message .= "• {$time}: {$slotCount} booking(s)\n";
        }

        // Add abandoned wizard notice
        $abandonedCount = $unsentBookings->where('wizard_abandoned_at', '!=', null)->count();
        if ($abandonedCount > 0) {
            $message .= "\n⚠️ {$abandonedCount} wizard(s) abandoned mid-process\n";
        }

        $message .= "\n👉 https://uffizi.deetech.cc";

        $this->info("Message to send:");
        $this->line($message);
        $this->newLine();

        if ($isDryRun) {
            $this->warn("DRY RUN - No messages sent");
            $this->info("Would send to: " . implode(', ', $this->adminPhones));
            return 0;
        }

        // Send WhatsApp messages to admin phones
        $sentCount = 0;
        foreach ($this->adminPhones as $phone) {
            try {
                $this->info("Sending to {$phone}...");

                // Use Twilio to send WhatsApp message directly
                $client = new \Twilio\Rest\Client(
                    config('services.twilio.account_sid'),
                    config('services.twilio.auth_token')
                );

                $whatsappFrom = config('services.twilio.whatsapp_from');

                $client->messages->create(
                    "whatsapp:{$phone}",
                    [
                        'from' => "whatsapp:{$whatsappFrom}",
                        'body' => $message,
                    ]
                );

                $sentCount++;
                $this->info("✓ Sent to {$phone}");

            } catch (\Exception $e) {
                $this->error("✗ Failed to send to {$phone}: " . $e->getMessage());
                Log::error('Ticket reminder failed', [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Ticket reminders sent', [
            'unsent_count' => $count,
            'sent_to' => $sentCount,
            'date' => $today,
        ]);

        $this->newLine();
        $this->info("Sent {$sentCount}/" . count($this->adminPhones) . " reminders.");

        return 0;
    }
}
