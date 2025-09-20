<?php

namespace App\Models;

use App\Models\Traits\HasTwoFactorAuthentication;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasTwoFactorAuthentication;
    use HasRoles;

    /**
     * The guard name for Spatie permissions.
     */
    protected $guard_name = 'web';

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
     * Get the team for Spatie permissions (tenant context).
     */
    public function getTeamId(): ?int
    {
        return $this->tenant_id;
    }

    /**
     * Assign a role to the user within a tenant using Spatie.
     */
    public function assignRoleInTenant(string $role, int $tenantId): self
    {
        $this->assignRole($role, $tenantId);

        return $this;
    }

    /**
     * Check if user has role in specific tenant using Spatie.
     */
    public function hasRoleInTenant(string $role, int $tenantId): bool
    {
        return $this->hasRole($role, $tenantId);
    }

    /**
     * Check if user has permission in specific tenant using Spatie.
     */
    public function hasPermissionInTenant(string $permission, int $tenantId): bool
    {
        return $this->hasPermissionTo($permission, $tenantId);
    }
}
