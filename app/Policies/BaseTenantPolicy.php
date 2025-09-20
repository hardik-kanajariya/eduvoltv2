<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Contracts\TenantAware;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * Base Policy class with built-in tenant scoping
 *
 * All policies extending this class will automatically check tenant ownership
 * before allowing any operations. This ensures data isolation between tenants.
 */
abstract class BaseTenantPolicy implements TenantAware
{
    use HandlesAuthorization;

    /**
     * Determine if the given model belongs to the specified tenant.
     */
    public function belongsToTenant(mixed $model, int $tenantId): bool
    {
        if (!$model instanceof Model) {
            return false;
        }

        // Check if model has tenant_id attribute
        if (!isset($model->tenant_id)) {
            return false;
        }

        return (int) $model->tenant_id === $tenantId;
    }

    /**
     * Get the tenant ID from the model.
     */
    public function getTenantIdFromModel(mixed $model): ?int
    {
        if (!$model instanceof Model) {
            return null;
        }

        return isset($model->tenant_id) ? (int) $model->tenant_id : null;
    }

    /**
     * Check if the authenticated user can access the specified tenant.
     */
    public function userCanAccessTenant(?int $tenantId, ?Authenticatable $user = null): bool
    {
        if ($tenantId === null) {
            return false;
        }

        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        // Check if user belongs to the tenant
        return isset($user->tenant_id) && (int) $user->tenant_id === $tenantId;
    }

    /**
     * Get the current user's default tenant ID.
     */
    protected function getCurrentTenantId(?Authenticatable $user = null): ?int
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return null;
        }

        // TODO: Implement getting user's default tenant
        // This would typically come from a user property or relationship

        return null; // Placeholder - implement actual logic
    }

    /**
     * Authorize action with tenant scoping.
     *
     * This method should be called before any policy method to ensure
     * the operation is allowed within the tenant context.
     */
    protected function authorizeWithTenant(?Authenticatable $user, mixed $model = null, ?int $tenantId = null): bool
    {
        if (!$user) {
            return false;
        }

        // If model is provided, check it belongs to the tenant
        if ($model !== null) {
            $modelTenantId = $this->getTenantIdFromModel($model);

            if ($modelTenantId === null) {
                return false;
            }

            // If specific tenant ID is provided, ensure model belongs to it
            if ($tenantId !== null && $modelTenantId !== $tenantId) {
                return false;
            }

            // Check user can access the model's tenant
            return $this->userCanAccessTenant($modelTenantId, $user);
        }

        // If no model but tenant ID is provided, check user access
        if ($tenantId !== null) {
            return $this->userCanAccessTenant($tenantId, $user);
        }

        // If neither model nor tenant ID, check user has default tenant
        $userTenantId = $this->getCurrentTenantId($user);

        return $userTenantId !== null && $this->userCanAccessTenant($userTenantId, $user);
    }

    /**
     * Check if user can view the model.
     * Base implementation that checks tenant ownership.
     */
    public function view(?Authenticatable $user, Model $model): bool
    {
        return $this->authorizeWithTenant($user, $model);
    }

    /**
     * Check if user can view any models of this type.
     * Base implementation that checks user has access to a tenant.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->authorizeWithTenant($user);
    }

    /**
     * Check if user can create models of this type.
     * Base implementation that checks user has access to a tenant.
     */
    public function create(?Authenticatable $user): bool
    {
        return $this->authorizeWithTenant($user);
    }

    /**
     * Check if user can update the model.
     * Base implementation that checks tenant ownership.
     */
    public function update(?Authenticatable $user, Model $model): bool
    {
        return $this->authorizeWithTenant($user, $model);
    }

    /**
     * Check if user can delete the model.
     * Base implementation that checks tenant ownership.
     */
    public function delete(?Authenticatable $user, Model $model): bool
    {
        return $this->authorizeWithTenant($user, $model);
    }

    /**
     * Check if user can restore the model.
     * Base implementation that checks tenant ownership.
     */
    public function restore(?Authenticatable $user, Model $model): bool
    {
        return $this->authorizeWithTenant($user, $model);
    }

    /**
     * Check if user can permanently delete the model.
     * Base implementation that checks tenant ownership.
     */
    public function forceDelete(?Authenticatable $user, Model $model): bool
    {
        return $this->authorizeWithTenant($user, $model);
    }

    /**
     * Helper method to create a model within tenant context.
     *
     * @param ?Authenticatable $user
     * @param array $attributes Model attributes to create
     * @param ?int $tenantId Specific tenant ID (uses user's default if null)
     */
    public function createInTenant(?Authenticatable $user, array $attributes = [], ?int $tenantId = null): bool
    {
        if (!$user) {
            return false;
        }

        // Use provided tenant ID or user's default
        $targetTenantId = $tenantId ?? $this->getCurrentTenantId($user);

        if ($targetTenantId === null) {
            return false;
        }

        return $this->userCanAccessTenant($targetTenantId, $user);
    }

    /**
     * Helper method to update a model within tenant context.
     */
    public function updateInTenant(?Authenticatable $user, Model $model, array $attributes = []): bool
    {
        // First check basic update permission
        if (!$this->update($user, $model)) {
            return false;
        }

        // If tenant_id is being changed, ensure user can access target tenant
        if (isset($attributes['tenant_id'])) {
            $newTenantId = (int) $attributes['tenant_id'];

            return $this->userCanAccessTenant($newTenantId, $user);
        }

        return true;
    }

    /**
     * Check if user can transfer model to another tenant.
     */
    public function transfer(?Authenticatable $user, Model $model, int $targetTenantId): bool
    {
        // Must be able to update the model in current tenant
        if (!$this->update($user, $model)) {
            return false;
        }

        // Must have access to target tenant
        return $this->userCanAccessTenant($targetTenantId, $user);
    }
}
