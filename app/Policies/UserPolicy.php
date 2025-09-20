<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * User Policy for school management system
 */
class UserPolicy extends BasePolicy
{
    /**
     * Check if user can view any users.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->isTeacherOrAbove($user);
    }

    /**
     * Check if user can view specific user.
     */
    public function view(?Authenticatable $user, Model $model): bool
    {
        $targetUser = $model;

        if ($this->isAdmin($user)) {
            return true;
        }

        // Teachers can view their own profile and student/parent profiles
        if ($this->isTeacherOrAbove($user)) {
            return $user->id === $targetUser->id ||
                $targetUser->hasAnyRole(['student', 'parent']);
        }

        // Users can view their own profile
        return $user->id === $targetUser->id;
    }

    /**
     * Check if user can create new users.
     */
    public function create(?Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Check if user can update a user.
     */
    public function update(?Authenticatable $user, Model $model): bool
    {
        $targetUser = $model;

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->isAdmin($user)) {
            // Admins can't modify super admins or other admins
            return !$targetUser->hasAnyRole(['super_admin', 'admin']);
        }

        // Users can update their own profile
        return $user->id === $targetUser->id;
    }

    /**
     * Check if user can delete a user.
     */
    public function delete(?Authenticatable $user, Model $model): bool
    {
        $targetUser = $model;

        if ($this->isSuperAdmin($user)) {
            // Super admins can delete anyone except themselves
            return $user->id !== $targetUser->id;
        }

        if ($this->isAdmin($user)) {
            // Admins can delete non-admin users
            return !$targetUser->hasAnyRole(['super_admin', 'admin']);
        }

        return false;
    }
}
