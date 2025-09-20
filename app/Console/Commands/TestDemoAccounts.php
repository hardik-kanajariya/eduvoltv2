<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DemoAccountsService;
use App\Models\User;

class TestDemoAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:demo-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test demo accounts functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $demoService = app(DemoAccountsService::class);

        $this->info('=== DEMO ACCOUNTS TEST ===');
        $this->newLine();

        // Test configuration
        $enabled = $demoService->isEnabled();
        $this->info("Demo accounts enabled: " . ($enabled ? 'YES' : 'NO'));
        $this->info("Config value: " . config('app.demo_accounts_enabled', 'not set'));
        $this->newLine();

        if ($enabled) {
            // Test accounts retrieval
            $accounts = $demoService->getDemoAccounts();
            $this->info("Demo accounts available: " . count($accounts));

            foreach ($accounts as $account) {
                $this->line("- {$account['role']}: {$account['email']}");
            }

            $this->newLine();

            // Test database accounts
            $this->info("Demo accounts in database:");
            $demoEmails = collect($accounts)->pluck('email')->toArray();
            $dbUsers = User::whereIn('email', $demoEmails)->get();

            foreach ($dbUsers as $user) {
                $this->line("✓ {$user->email} (ID: {$user->id}, Tenant: {$user->tenant_id})");
            }

            if ($dbUsers->count() !== count($accounts)) {
                $this->warn("Warning: Not all demo accounts exist in database!");
            }
        }

        $this->newLine();
        $this->info('Test completed!');
    }
}
