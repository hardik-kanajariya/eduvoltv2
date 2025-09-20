<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Educational system permissions grouped by resource.
     */
    public const EDUCATIONAL_PERMISSIONS = [
        'students' => [
            'students.create' => 'Create students',
            'students.read' => 'View students',
            'students.update' => 'Update students',
            'students.delete' => 'Delete students',
        ],
        'teachers' => [
            'teachers.create' => 'Create teachers',
            'teachers.read' => 'View teachers',
            'teachers.update' => 'Update teachers',
            'teachers.delete' => 'Delete teachers',
        ],
        'classes' => [
            'classes.create' => 'Create classes',
            'classes.read' => 'View classes',
            'classes.update' => 'Update classes',
            'classes.delete' => 'Delete classes',
        ],
        'attendance' => [
            'attendance.create' => 'Mark attendance',
            'attendance.read' => 'View attendance',
            'attendance.update' => 'Update attendance',
            'attendance.reports' => 'Generate attendance reports',
        ],
        'grades' => [
            'grades.create' => 'Create grades',
            'grades.read' => 'View grades',
            'grades.update' => 'Update grades',
            'grades.reports' => 'Generate grade reports',
        ],
        'fees' => [
            'fees.create' => 'Create fee structures',
            'fees.read' => 'View fees',
            'fees.update' => 'Update fees',
            'fees.collect' => 'Collect fees',
            'fees.reports' => 'Generate fee reports',
        ],
        'users' => [
            'users.create' => 'Create users',
            'users.read' => 'View users',
            'users.update' => 'Update users',
            'users.delete' => 'Delete users',
        ],
        'roles' => [
            'roles.create' => 'Create roles',
            'roles.read' => 'View roles',
            'roles.update' => 'Update roles',
            'roles.delete' => 'Delete roles',
            'roles.assign' => 'Assign roles',
        ],
        'settings' => [
            'settings.read' => 'View settings',
            'settings.update' => 'Update settings',
        ],
    ];

    /**
     * Create all educational permissions.
     */
    public static function createEducationalPermissions(): void
    {
        foreach (static::EDUCATIONAL_PERMISSIONS as $resource => $permissions) {
            foreach ($permissions as $slug => $name) {
                [$resourceName, $action] = explode('.', $slug);

                static::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'resource' => $resourceName,
                        'action' => $action,
                        'description' => $name,
                    ]
                );
            }
        }
    }
}
