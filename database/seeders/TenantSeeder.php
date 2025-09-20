<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Development tenant for localhost
        Tenant::create([
            'name' => 'Development Tenant',
            'slug' => 'development',
            'domain' => 'localhost',
            'subdomain' => 'dev',
            'status' => 'active',
            'settings' => [
                'max_students' => 1000,
                'max_teachers' => 100,
                'features' => ['attendance', 'grades', 'reports', 'timetable'],
            ],
        ]);

        Tenant::create([
            'name' => 'Demo School',
            'slug' => 'demo-school',
            'domain' => 'demo.eduvolt.com',
            'subdomain' => 'demo',
            'status' => 'active',
            'settings' => [
                'max_students' => 1000,
                'max_teachers' => 100,
                'features' => ['attendance', 'grades', 'reports'],
            ],
        ]);

        Tenant::create([
            'name' => 'Test Institution',
            'slug' => 'test-institution',
            'domain' => 'test.eduvolt.com',
            'subdomain' => 'test',
            'status' => 'active',
            'settings' => [
                'max_students' => 500,
                'max_teachers' => 50,
                'features' => ['attendance', 'grades'],
            ],
        ]);
    }
}
