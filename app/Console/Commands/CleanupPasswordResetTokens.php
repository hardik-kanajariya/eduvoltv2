<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPasswordResetTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:cleanup-tokens 
                            {--expired : Only clean up expired tokens}
                            {--days=1 : Number of days to consider tokens expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired password reset tokens from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $onlyExpired = $this->option('expired');

        $this->info('Starting password reset token cleanup...');

        if ($onlyExpired) {
            // Clean only expired tokens (older than configured time)
            $expiredTime = now()->subMinutes(config('auth.passwords.users.expire', 60));
            $deletedCount = DB::table('password_reset_tokens')
                ->where('created_at', '<', $expiredTime)
                ->delete();

            $this->info("Cleaned up {$deletedCount} expired password reset tokens.");
        } else {
            // Clean tokens older than specified days
            $cutoffTime = now()->subDays($days);
            $deletedCount = DB::table('password_reset_tokens')
                ->where('created_at', '<', $cutoffTime)
                ->delete();

            $this->info("Cleaned up {$deletedCount} password reset tokens older than {$days} days.");
        }

        // Show remaining tokens count
        $remainingCount = DB::table('password_reset_tokens')->count();
        $this->info("Remaining password reset tokens: {$remainingCount}");

        return self::SUCCESS;
    }
}
