<?php

namespace App\Models;

use App\Models\Traits\HasTwoFactorAuthentication;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'provider',
        'provider_id',
        'provider_token',
        'avatar',
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
        'provider_token',
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
     * Assign a role to the user using Spatie.
     */
    public function assignUserRole(string $role): self
    {
        $this->assignRole($role);

        return $this;
    }

    /**
     * Check if user has specific role using Spatie.
     */
    public function hasUserRole(string $role): bool
    {
        return $this->hasRole($role);
    }

    /**
     * Check if user has specific permission using Spatie.
     */
    public function hasUserPermission(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }
}
