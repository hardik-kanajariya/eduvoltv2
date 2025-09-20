<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            DemoAccountsSeeder::class,
        ]);

        // User::factory(10)->create();

        // Only create test user if demo accounts are not enabled
        if (!config('app.demo_accounts_enabled', false)) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'tenant_id' => 1, // Assign to the first tenant
            ]);
        }
    }
}
