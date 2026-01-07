<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedWebhooks;
use App\Models\WebhookLog;
use Illuminate\Console\Command;

class RetryFailedWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhooks:retry
                            {--max-retries=3 : Maximum retry attempts per webhook}
                            {--sync : Run synchronously instead of dispatching to queue}';

    /**
     * The console command description.
     */
    protected $description = 'Retry failed webhook processing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxRetries = (int) $this->option('max-retries');
        $sync = $this->option('sync');

        $pendingCount = WebhookLog::retryable($maxRetries)->count();

        if ($pendingCount === 0) {
            $this->info('No failed webhooks to retry.');
            return Command::SUCCESS;
        }

        $this->info("Found {$pendingCount} failed webhooks to retry.");

        if ($sync) {
            $this->info('Running synchronously...');
            (new RetryFailedWebhooks($maxRetries))->handle();
        } else {
            RetryFailedWebhooks::dispatch($maxRetries);
            $this->info('Retry job dispatched to queue.');
        }

        return Command::SUCCESS;
    }
}
