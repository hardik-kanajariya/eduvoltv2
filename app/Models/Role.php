<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Educational system roles for single-school management.
     */
    public const EDUCATIONAL_ROLES = [
        'super_admin' => 'Super Administrator', // School owner/principal
        'admin' => 'Administrator',
        'teacher' => 'Teacher',
        'parent' => 'Parent',
        'student' => 'Student',
        'accountant' => 'Accountant',
        'librarian' => 'Librarian',
        'warden' => 'Warden',
        'driver' => 'Driver',
    ];
}
