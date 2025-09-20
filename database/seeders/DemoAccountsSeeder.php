<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Demo accounts configuration.
     */
    private array $demoAccounts = [
        [
            'role' => 'super_admin',
            'name' => 'Demo Super Admin',
            'email' => 'admin@school.local',
            'password' => 'DemoAdmin123!',
            'description' => 'School Administrator - Full access to all features',
        ],
        [
            'role' => 'admin',
            'name' => 'Demo Admin',
            'email' => 'admin2@school.local',
            'password' => 'DemoAdmin123!',
            'description' => 'Administrator - Manage school operations',
        ],
        [
            'role' => 'teacher',
            'name' => 'Demo Teacher',
            'email' => 'teacher@school.local',
            'password' => 'DemoTeacher123!',
            'description' => 'Teacher Account - Manage classes, students, and grades',
        ],
        [
            'role' => 'student',
            'name' => 'Demo Student',
            'email' => 'student@school.local',
            'password' => 'DemoStudent123!',
            'description' => 'Student Account - View assignments, grades, and attendance',
        ],
        [
            'role' => 'parent',
            'name' => 'Demo Parent',
            'email' => 'parent@school.local',
            'password' => 'DemoParent123!',
            'description' => 'Parent Account - Monitor child\'s academic progress',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating demo accounts...');

        foreach ($this->demoAccounts as $accountData) {
            $this->createDemoAccount($accountData);
        }

        $this->command->info('Demo accounts created successfully!');
        $this->displayDemoAccountsInfo();
    }

    /**
     * Create a demo account.
     */
    private function createDemoAccount(array $accountData): void
    {
        $user = User::firstOrCreate(
            ['email' => $accountData['email']],
            [
                'name' => $accountData['name'],
                'password' => Hash::make($accountData['password']),
                'email_verified_at' => now(),
            ]
        );

        // Make sure role exists before assigning
        $role = Role::firstOrCreate(['name' => $accountData['role']]);

        if (!$user->hasRole($accountData['role'])) {
            $user->assignRole($accountData['role']);
        }

        $this->command->info("Created demo {$accountData['role']}: {$accountData['email']}");
    }

    /**
     * Display demo accounts information.
     */
    private function displayDemoAccountsInfo(): void
    {
        $this->command->newLine();
        $this->command->info('=== DEMO ACCOUNTS CREATED ===');
        $this->command->newLine();

        foreach ($this->demoAccounts as $account) {
            $this->command->info("🔹 {$account['description']}");
            $this->command->line("   Email: {$account['email']}");
            $this->command->line("   Password: {$account['password']}");
            $this->command->newLine();
        }

        $this->command->info('These accounts are available for login');
        $this->command->newLine();
    }

    /**
     * Get demo accounts data for frontend usage.
     */
    public static function getDemoAccountsForFrontend(): array
    {
        return [
            [
                'role' => 'super_admin',
                'name' => 'Demo Super Admin',
                'email' => 'admin@school.local',
                'password' => 'DemoAdmin123!',
                'description' => 'School Administrator - Full access to all features',
            ],
            [
                'role' => 'admin',
                'name' => 'Demo Admin',
                'email' => 'admin2@school.local',
                'password' => 'DemoAdmin123!',
                'description' => 'Administrator - Manage school operations',
            ],
            [
                'role' => 'teacher',
                'name' => 'Demo Teacher',
                'email' => 'teacher@school.local',
                'password' => 'DemoTeacher123!',
                'description' => 'Teacher - Manage classes and students',
            ],
            [
                'role' => 'student',
                'name' => 'Demo Student',
                'email' => 'student@school.local',
                'password' => 'DemoStudent123!',
                'description' => 'Student - Access coursework and grades',
            ],
            [
                'role' => 'parent',
                'name' => 'Demo Parent',
                'email' => 'parent@school.local',
                'password' => 'DemoParent123!',
                'description' => 'Parent - Monitor child\'s progress',
            ],
        ];
    }
}
