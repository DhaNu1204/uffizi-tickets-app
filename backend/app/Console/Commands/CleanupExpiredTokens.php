<?php

namespace App\Console\Commands;

use App\Services\DownloadTokenService;
use Illuminate\Console\Command;

class CleanupExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired download tokens';

    /**
     * Execute the console command.
     */
    public function handle(DownloadTokenService $tokenService): int
    {
        $count = $tokenService->cleanupExpiredTokens();
        $this->info("Cleaned up {$count} expired tokens.");
        return Command::SUCCESS;
    }
}
