<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Base Policy class for school management system
 *
 * Provides common authorization patterns for single-school setup.
 * Super admin has full access, other roles have specific permissions.
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Check if user is super admin (school administrator).
     */
    public function isSuperAdmin(Authenticatable $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Check if user is admin or super admin.
     */
    public function isAdmin(Authenticatable $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Check if user is teacher, admin, or super admin.
     */
    public function isTeacherOrAbove(Authenticatable $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    /**
     * Before method - super admin can do everything.
     */
    public function before(Authenticatable $user, string $ability): ?bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return null;
    }

    /**
     * Check if user can view the model.
     * Base implementation - override in specific policies for custom logic.
     */
    public function view(?Authenticatable $user, Model $model): bool
    {
        return $this->isTeacherOrAbove($user);
    }

    /**
     * Check if user can view any models of this type.
     * Base implementation - override in specific policies for custom logic.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->isTeacherOrAbove($user);
    }

    /**
     * Check if user can create models of this type.
     * Base implementation - override in specific policies for custom logic.
     */
    public function create(?Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Check if user can update the model.
     * Base implementation - override in specific policies for custom logic.
     */
    public function update(?Authenticatable $user, Model $model): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Check if user can delete the model.
     * Base implementation - override in specific policies for custom logic.
     */
    public function delete(?Authenticatable $user, Model $model): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Check if user can restore the model.
     * Base implementation - override in specific policies for custom logic.
     */
    public function restore(?Authenticatable $user, Model $model): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Check if user can permanently delete the model.
     * Base implementation - override in specific policies for custom logic.
     */
    public function forceDelete(?Authenticatable $user, Model $model): bool
    {
        return $this->isSuperAdmin($user);
    }
}
