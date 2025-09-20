<?php

namespace App\Models;

use App\Models\Traits\HasTwoFactorAuthentication;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasTwoFactorAuthentication;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the user.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot(['tenant_id', 'assigned_at', 'expires_at'])
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role within a tenant.
     */
    public function hasRole(string $role, ?int $tenantId = null): bool
    {
        $query = $this->roles()->where('slug', $role);
        
        if ($tenantId) {
            $query->wherePivot('tenant_id', $tenantId);
        }
        
        return $query->exists();
    }

    /**
     * Check if user has any of the given roles within a tenant.
     */
    public function hasAnyRole(array $roles, ?int $tenantId = null): bool
    {
        $query = $this->roles()->whereIn('slug', $roles);
        
        if ($tenantId) {
            $query->wherePivot('tenant_id', $tenantId);
        }
        
        return $query->exists();
    }

    /**
     * Check if user has a specific permission within a tenant.
     */
    public function hasPermission(string $permission, ?int $tenantId = null): bool
    {
        $query = $this->roles();
        
        if ($tenantId) {
            $query->wherePivot('tenant_id', $tenantId);
        }
        
        return $query->whereHas('permissions', function ($query) use ($permission) {
            $query->where('slug', $permission);
        })->exists();
    }

    /**
     * Assign a role to the user within a tenant.
     */
    public function assignRole(Role|string $role, int $tenantId): self
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([
            $role->id => [
                'tenant_id' => $tenantId,
                'assigned_at' => now(),
            ]
        ]);

        return $this;
    }

    /**
     * Remove a role from the user within a tenant.
     */
    public function removeRole(Role|string $role, int $tenantId): self
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        $this->roles()->wherePivot('tenant_id', $tenantId)->detach($role->id);

        return $this;
    }

    /**
     * Get user's roles for a specific tenant.
     */
    public function rolesForTenant(int $tenantId): BelongsToMany
    {
        return $this->roles()->wherePivot('tenant_id', $tenantId);
    }
}
