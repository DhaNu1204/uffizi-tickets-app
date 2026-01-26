<?php

namespace App\Console\Commands;

use App\Models\MessageAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attachments:cleanup
                            {--hours=24 : Delete attachments older than this many hours}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned attachments (not attached to any message)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("Looking for orphaned attachments older than {$hours} hours...");

        // Find orphaned attachments (no message_id and older than X hours)
        $query = MessageAttachment::whereNull('message_id')
            ->where('created_at', '<', now()->subHours($hours));

        $count = $query->count();

        if ($count === 0) {
            $this->info('No orphaned attachments found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} orphaned attachment(s).");

        if ($dryRun) {
            $this->warn('Dry run mode - no files will be deleted.');

            $attachments = $query->get();
            foreach ($attachments as $attachment) {
                $this->line("  Would delete: {$attachment->original_name} (ID: {$attachment->id})");
            }

            return Command::SUCCESS;
        }

        // Confirm deletion
        if (!$this->confirm("Delete {$count} orphaned attachment(s)?")) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;

        $attachments = $query->get();
        foreach ($attachments as $attachment) {
            try {
                $name = $attachment->original_name;
                $attachment->delete(); // This also deletes the physical file
                $deleted++;
                $this->line("  Deleted: {$name}");
            } catch (\Exception $e) {
                $failed++;
                $this->error("  Failed to delete {$attachment->original_name}: {$e->getMessage()}");
                Log::error('Failed to delete orphaned attachment', [
                    'attachment_id' => $attachment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Cleanup complete: {$deleted} deleted, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
