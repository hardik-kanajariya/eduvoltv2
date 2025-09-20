<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class StudentPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->isTeacherOrAbove($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?Authenticatable $user, Model $model): bool
    {
        $student = $model;

        // Admin and teachers can view all students
        if ($this->isTeacherOrAbove($user)) {
            return true;
        }

        // Students can view their own profile
        if ($user && $user->hasRole('student') && $user->email === $student->email) {
            return true;
        }

        // Parents can view their children's profiles
        if ($user && $user->hasRole('parent') && $user->email === $student->parent_email) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?Authenticatable $user, Model $model): bool
    {
        $student = $model;

        // Admin can update all students
        if ($this->isAdmin($user)) {
            return true;
        }

        // Students can update their own profile (limited fields)
        if ($user && $user->hasRole('student') && $user->email === $student->email) {
            return true;
        }

        // Parents can update their children's profiles (limited fields)
        if ($user && $user->hasRole('parent') && $user->email === $student->parent_email) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?Authenticatable $user, Model $model): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?Authenticatable $user, Model $model): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?Authenticatable $user, Model $model): bool
    {
        return $this->isSuperAdmin($user);
    }
}
