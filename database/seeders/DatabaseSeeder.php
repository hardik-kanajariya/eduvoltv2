<?php

namespace Database\Seeders;

use App\Models\Tenant;
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
        // Create sample tenants
        $tenant1 = Tenant::create([
            'name' => 'ACME School',
            'domain' => 'acme.eduvolt.test',
            'is_active' => true,
            'settings' => [
                'timezone' => 'UTC',
                'locale' => 'en',
            ],
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Demo University',
            'domain' => 'demo.eduvolt.test',
            'is_active' => true,
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
            ],
        ]);

        // Create users for each tenant
        User::create([
            'name' => 'ACME Admin',
            'email' => 'admin@acme.eduvolt.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant1->id,
        ]);

        User::create([
            'name' => 'Demo Admin',
            'email' => 'admin@demo.eduvolt.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant2->id,
        ]);

        // Create additional test users
        User::factory(5)->create(['tenant_id' => $tenant1->id]);
        User::factory(3)->create(['tenant_id' => $tenant2->id]);
    }
}
