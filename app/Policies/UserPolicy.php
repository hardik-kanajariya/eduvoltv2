<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\BaseTenantPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Example User Policy demonstrating BaseTenantPolicy usage
 *
 * This policy shows how to extend BaseTenantPolicy for specific models
 * while maintaining tenant scoping and adding model-specific logic.
 */
class UserPolicy extends BaseTenantPolicy
{
    /**
     * Check if user can view any users.
     * Inherits tenant checking from parent, adds role-based logic.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        if (!parent::viewAny($user)) {
            return false;
        }

        // Add additional authorization logic here
        // For example: check if user has 'manage-users' permission
        
        return true; // Allow if basic tenant checks pass
    }

    /**
     * Check if user can view a specific user.
     * Inherits tenant checking from parent.
     */
    public function view(?Authenticatable $user, Model $model): bool
    {
        // Base tenant checking
        if (!parent::view($user, $model)) {
            return false;
        }

        // Add model-specific logic
        // For example: users can always view their own profile
        if ($user && $model instanceof User && $user->id === $model->id) {
            return true;
        }

        // Or check if user has permission to view other users
        return true; // Allow if basic tenant checks pass
    }

    /**
     * Check if user can create new users.
     */
    public function create(?Authenticatable $user): bool
    {
        if (!parent::create($user)) {
            return false;
        }

        // Add creation-specific logic
        // For example: check if user has 'create-users' permission
        
        return true;
    }

    /**
     * Check if user can update a specific user.
     */
    public function update(?Authenticatable $user, Model $model): bool
    {
        if (!parent::update($user, $model)) {
            return false;
        }

        // Add update-specific logic
        // For example: users can update their own profile, 
        // or user needs 'edit-users' permission for others
        if ($user && $model instanceof User && $user->id === $model->id) {
            return true;
        }

        // Check if user has permission to edit other users
        return true; // Allow if basic tenant checks pass
    }

    /**
     * Check if user can delete a specific user.
     */
    public function delete(?Authenticatable $user, Model $model): bool
    {
        if (!parent::delete($user, $model)) {
            return false;
        }

        // Add deletion-specific logic
        // For example: users cannot delete themselves
        if ($user && $model instanceof User && $user->id === $model->id) {
            return false;
        }

        // Check if user has permission to delete other users
        return true; // Allow if basic tenant checks pass and not self-deletion
    }

    /**
     * Check if user can permanently delete a specific user.
     */
    public function forceDelete(?Authenticatable $user, Model $model): bool
    {
        if (!parent::forceDelete($user, $model)) {
            return false;
        }

        // Add force deletion-specific logic
        // This is typically more restricted than soft delete
        // For example: only super admins can permanently delete users
        
        return false; // Deny by default - implement proper permission checking
    }

    /**
     * Check if user can restore a soft-deleted user.
     */
    public function restore(?Authenticatable $user, Model $model): bool
    {
        if (!parent::restore($user, $model)) {
            return false;
        }

        // Add restoration-specific logic
        // For example: check if user has 'restore-users' permission
        
        return true;
    }

    /**
     * Example of a custom policy method specific to User model.
     */
    public function changePassword(?Authenticatable $user, Model $model): bool
    {
        if (!$this->authorizeWithTenant($user, $model)) {
            return false;
        }

        // Users can change their own password
        if ($user && $model instanceof User && $user->id === $model->id) {
            return true;
        }

        // Or check if user has 'change-user-passwords' permission
        return false; // Deny by default
    }

    /**
     * Example of checking permissions for user role assignment.
     */
    public function assignRole(?Authenticatable $user, Model $model): bool
    {
        if (!$this->authorizeWithTenant($user, $model)) {
            return false;
        }

        // Add role assignment logic
        // For example: only administrators can assign roles
        
        return false; // Deny by default - implement proper permission checking
    }

    /**
     * Example of bulk operations authorization.
     */
    public function bulkDelete(?Authenticatable $user, array $userIds): bool
    {
        if (!$this->authorizeWithTenant($user)) {
            return false;
        }

        // Check if user has permission for bulk operations
        // This might require special permissions
        
        return false; // Deny by default - implement proper permission checking
    }
}