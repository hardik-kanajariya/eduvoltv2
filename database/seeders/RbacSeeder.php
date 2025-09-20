<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create all educational permissions
        Permission::createEducationalPermissions();

        // Create system-level roles (non-tenant specific)
        $this->createSystemRoles();

        // Create educational roles for tenants
        $this->createEducationalRoles();
    }

    /**
     * Create system-level roles.
     */
    private function createSystemRoles(): void
    {
        // Super Admin - can access everything across all tenants
        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Administrator',
                'description' => 'Full system access across all tenants',
                'is_system_role' => true,
                'tenant_id' => null,
            ]
        );

        // Assign all permissions to super admin
        $allPermissions = Permission::all();
        $superAdmin->permissions()->sync($allPermissions->pluck('id'));
    }

    /**
     * Create educational roles for tenants.
     */
    private function createEducationalRoles(): void
    {
        foreach (Role::EDUCATIONAL_ROLES as $slug => $name) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => "Educational role: {$name}",
                    'is_system_role' => false,
                    'tenant_id' => null, // Template role, will be copied per tenant
                ]
            );

            // Assign default permissions based on role
            $this->assignDefaultPermissions($role, $slug);
        }
    }

    /**
     * Assign default permissions to roles.
     */
    private function assignDefaultPermissions(Role $role, string $slug): void
    {
        $permissions = [];

        switch ($slug) {
            case 'owner':
                // School owner has all permissions
                $permissions = Permission::all()->pluck('id')->toArray();
                break;

            case 'admin':
                // Admin has most permissions except user management
                $permissions = Permission::whereNotIn('resource', ['roles'])->pluck('id')->toArray();
                break;

            case 'teacher':
                // Teachers can manage students, attendance, and grades
                $permissions = Permission::whereIn('resource', ['students', 'attendance', 'grades', 'classes'])
                    ->whereIn('action', ['read', 'create', 'update'])
                    ->pluck('id')->toArray();
                break;

            case 'parent':
                // Parents can only view their children's information
                $permissions = Permission::whereIn('resource', ['students', 'attendance', 'grades'])
                    ->where('action', 'read')
                    ->pluck('id')->toArray();
                break;

            case 'student':
                // Students can view their own information
                $permissions = Permission::whereIn('resource', ['attendance', 'grades'])
                    ->where('action', 'read')
                    ->pluck('id')->toArray();
                break;

            case 'accountant':
                // Accountants manage fees and related reports
                $permissions = Permission::whereIn('resource', ['fees', 'students'])
                    ->pluck('id')->toArray();
                break;

            case 'librarian':
                // Librarians manage library resources (to be implemented)
                $permissions = Permission::whereIn('resource', ['students'])
                    ->where('action', 'read')
                    ->pluck('id')->toArray();
                break;

            case 'warden':
                // Wardens manage hostel/dormitory (to be implemented)
                $permissions = Permission::whereIn('resource', ['students'])
                    ->whereIn('action', ['read', 'update'])
                    ->pluck('id')->toArray();
                break;

            case 'driver':
                // Drivers manage transportation (to be implemented)
                $permissions = Permission::whereIn('resource', ['students'])
                    ->where('action', 'read')
                    ->pluck('id')->toArray();
                break;
        }

        if (!empty($permissions)) {
            $role->permissions()->sync($permissions);
        }
    }
}
