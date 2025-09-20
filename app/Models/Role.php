<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Educational system roles for tenant-scoped assignments.
     */
    public const EDUCATIONAL_ROLES = [
        'owner' => 'School Owner',
        'admin' => 'Administrator',
        'teacher' => 'Teacher',
        'parent' => 'Parent',
        'student' => 'Student',
        'accountant' => 'Accountant',
        'librarian' => 'Librarian',
        'warden' => 'Warden',
        'driver' => 'Driver',
    ];

    /**
     * Get the tenant that owns the role.
     * Note: With Spatie teams feature enabled, the tenant_id is stored in the tenant_id column
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Create role for a specific tenant.
     */
    public static function createForTenant(string $name, int $tenantId, string $guardName = 'web'): self
    {
        return static::create([
            'name' => $name,
            'tenant_id' => $tenantId,
            'guard_name' => $guardName,
        ]);
    }
}
