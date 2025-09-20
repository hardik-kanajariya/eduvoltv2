<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Demo accounts configuration with consistent credentials for easy testing.
     */
    private array $demoAccounts = [
        [
            'role' => 'admin',
            'name' => 'Demo Admin',
            'email' => 'admin@demo.eduvolt.com',
            'password' => 'DemoAdmin123!',
            'description' => 'System Administrator - Full access to all features',
        ],
        [
            'role' => 'teacher',
            'name' => 'Demo Teacher',
            'email' => 'teacher@demo.eduvolt.com',
            'password' => 'DemoTeacher123!',
            'description' => 'Teacher Account - Manage classes, students, and grades',
        ],
        [
            'role' => 'student',
            'name' => 'Demo Student',
            'email' => 'student@demo.eduvolt.com',
            'password' => 'DemoStudent123!',
            'description' => 'Student Account - View assignments, grades, and attendance',
        ],
        [
            'role' => 'parent',
            'name' => 'Demo Parent',
            'email' => 'parent@demo.eduvolt.com',
            'password' => 'DemoParent123!',
            'description' => 'Parent Account - Monitor child\'s academic progress',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run if demo accounts are enabled
        if (!config('app.demo_accounts_enabled', false)) {
            $this->command->info('Demo accounts are disabled. Set DEMO_ACCOUNTS_ENABLED=true to enable.');
            return;
        }

        $this->command->info('Creating demo accounts...');

        // Get or create demo tenant
        $demoTenant = $this->createDemoTenant();

        // Create demo accounts (without role assignment for now)
        foreach ($this->demoAccounts as $accountData) {
            $this->createDemoAccount($accountData, $demoTenant->id);
        }

        $this->command->info('Demo accounts created successfully!');
        $this->displayDemoAccountsInfo();
    }

    /**
     * Create or get the demo tenant.
     */
    private function createDemoTenant(): Tenant
    {
        return Tenant::firstOrCreate(
            ['slug' => 'demo-school'],
            [
                'name' => 'Demo School',
                'domain' => 'demo.eduvolt.com',
                'subdomain' => 'demo',
                'status' => 'active',
                'description' => 'Demo tenant for testing purposes',
                'settings' => [
                    'max_students' => 1000,
                    'max_teachers' => 100,
                    'features' => ['attendance', 'grades', 'reports', 'analytics'],
                    'is_demo' => true,
                ],
            ]
        );
    }

    /**
     * Create roles and permissions if they don't exist.
     */
    private function createRolesAndPermissions(): void
    {
        $roles = ['admin', 'teacher', 'student', 'parent'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Create basic permissions
        $permissions = [
            'view_dashboard',
            'manage_users',
            'manage_students',
            'manage_teachers',
            'view_reports',
            'manage_attendance',
            'manage_grades',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    /**
     * Create a demo account.
     */
    private function createDemoAccount(array $accountData, int $tenantId): User
    {
        // Check if user already exists
        $existingUser = User::where('email', $accountData['email'])->first();

        if ($existingUser) {
            $this->command->warn("Demo account {$accountData['email']} already exists. Skipping...");
            return $existingUser;
        }

        // Create the user
        $user = User::create([
            'name' => $accountData['name'],
            'email' => $accountData['email'],
            'password' => Hash::make($accountData['password']),
            'tenant_id' => $tenantId,
            'email_verified_at' => now(),
        ]);

        $this->command->info("Created demo {$accountData['role']}: {$accountData['email']}");

        return $user;
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

        $this->command->info('These accounts will be available on the login page when DEMO_ACCOUNTS_ENABLED=true');
        $this->command->newLine();
    }

    /**
     * Get demo accounts for frontend usage.
     */
    public static function getDemoAccountsForFrontend(): array
    {
        if (!config('app.demo_accounts_enabled', false)) {
            return [];
        }

        return [
            [
                'role' => 'admin',
                'name' => 'Demo Admin',
                'email' => 'admin@demo.eduvolt.com',
                'password' => 'DemoAdmin123!',
                'description' => 'System Administrator',
            ],
            [
                'role' => 'teacher',
                'name' => 'Demo Teacher',
                'email' => 'teacher@demo.eduvolt.com',
                'password' => 'DemoTeacher123!',
                'description' => 'Teacher Account',
            ],
            [
                'role' => 'student',
                'name' => 'Demo Student',
                'email' => 'student@demo.eduvolt.com',
                'password' => 'DemoStudent123!',
                'description' => 'Student Account',
            ],
            [
                'role' => 'parent',
                'name' => 'Demo Parent',
                'email' => 'parent@demo.eduvolt.com',
                'password' => 'DemoParent123!',
                'description' => 'Parent Account',
            ],
        ];
    }
}
