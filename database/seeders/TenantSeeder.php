<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
