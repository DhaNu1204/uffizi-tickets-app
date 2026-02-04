<?php

namespace App\Console\Commands;

use App\Services\MessagingService;
use Illuminate\Console\Command;

class RetryFailedMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:retry-failed
                            {--limit=50 : Maximum number of messages to retry}
                            {--channel= : Filter by channel (whatsapp, email, sms)}
                            {--dry-run : Preview what would be retried without actually retrying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed messages that haven\'t exceeded max retry attempts';

    /**
     * Execute the console command.
     */
    public function handle(MessagingService $messagingService): int
    {
        $limit = (int) $this->option('limit');
        $channel = $this->option('channel');
        $dryRun = $this->option('dry-run');

        $this->info('=== Message Retry Command ===');
        $this->info("Limit: {$limit}");
        $this->info("Channel: " . ($channel ?: 'all'));
        $this->info("Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        // Get retryable messages
        $messages = $messagingService->getRetryableMessages($limit, $channel);

        if ($messages->isEmpty()) {
            $this->info('No retryable messages found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$messages->count()} retryable message(s):");
        $this->newLine();

        // Display table of messages to retry
        $tableData = $messages->map(fn($m) => [
            'ID' => $m->id,
            'Channel' => $m->channel,
            'Recipient' => substr($m->recipient, 0, 30),
            'Booking' => $m->booking?->customer_name ?? 'N/A',
            'Failed At' => $m->failed_at?->format('Y-m-d H:i'),
            'Retries' => $m->retry_count,
            'Error' => substr($m->error_message ?? '', 0, 50),
        ])->toArray();

        $this->table(
            ['ID', 'Channel', 'Recipient', 'Booking', 'Failed At', 'Retries', 'Error'],
            $tableData
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN - No messages were actually retried.');
            $this->info('Remove --dry-run to execute retries.');
            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->confirm("Retry {$messages->count()} message(s)?")) {
            $this->info('Aborted.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Starting batch retry...');
        $this->newLine();

        // Execute batch retry
        $results = $messagingService->batchRetryFailedMessages($limit, $channel);

        // Display results
        $this->info("=== Retry Results ===");
        $this->info("Total: {$results['total']}");
        $this->info("Success: {$results['success']}");
        $this->info("Failed: {$results['failed']}");
        $this->newLine();

        // Show detailed results
        if (!empty($results['results'])) {
            $resultTable = collect($results['results'])->map(fn($r) => [
                'ID' => $r['message_id'],
                'Channel' => $r['channel'],
                'Recipient' => substr($r['recipient'], 0, 30),
                'Status' => $r['success'] ? '✓ Success' : '✗ Failed',
                'Error' => $r['success'] ? '' : substr($r['error'] ?? '', 0, 40),
            ])->toArray();

            $this->table(
                ['ID', 'Channel', 'Recipient', 'Status', 'Error'],
                $resultTable
            );
        }

        // Return appropriate exit code
        if ($results['failed'] > 0) {
            $this->warn("Some messages failed to retry. Check logs for details.");
            return Command::FAILURE;
        }

        $this->info("All messages retried successfully!");
        return Command::SUCCESS;
    }
}
