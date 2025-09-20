<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $this->createPermissions();

        // Create roles
        $this->createRoles();

        // Assign permissions to roles
        $this->assignPermissions();
    }

    /**
     * Create system permissions.
     */
    private function createPermissions(): void
    {
        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'students.view',
            'students.create',
            'students.update',
            'students.delete',
            'teachers.view',
            'teachers.create',
            'teachers.update',
            'teachers.delete',
            'grades.view',
            'grades.create',
            'grades.update',
            'grades.delete',
            'reports.view',
            'reports.export',
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    /**
     * Create educational roles.
     */
    private function createRoles(): void
    {
        $roles = [
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'teacher' => 'Teacher',
            'student' => 'Student',
            'parent' => 'Parent',
        ];

        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate(['name' => $name]);
        }
    }

    /**
     * Assign permissions to roles.
     */
    private function assignPermissions(): void
    {
        // Super Admin gets all permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        $superAdmin->givePermissionTo(Permission::all());

        // Admin gets most permissions
        $admin = Role::where('name', 'admin')->first();
        $admin->givePermissionTo([
            'users.view',
            'users.create',
            'users.update',
            'students.view',
            'students.create',
            'students.update',
            'students.delete',
            'teachers.view',
            'teachers.create',
            'teachers.update',
            'teachers.delete',
            'grades.view',
            'grades.create',
            'grades.update',
            'grades.delete',
            'reports.view',
            'reports.export',
        ]);

        // Teacher gets limited permissions
        $teacher = Role::where('name', 'teacher')->first();
        $teacher->givePermissionTo([
            'students.view',
            'grades.view',
            'grades.create',
            'grades.update',
            'reports.view',
        ]);
    }
}
