<?php

declare(strict_types=1);

namespace App\Policies\Contracts;

/**
 * Interface for tenant-aware policies
 *
 * Defines the contract for policies that need to operate within
 * a specific tenant context and check tenant ownership.
 */
interface TenantAware
{
    /**
     * Check if the given model belongs to the specified tenant.
     */
    public function belongsToTenant(mixed $model, int $tenantId): bool;

    /**
     * Get the tenant ID from the model.
     */
    public function getTenantIdFromModel(mixed $model): ?int;

    /**
     * Check if the authenticated user can access the specified tenant.
     */
    public function userCanAccessTenant(?int $tenantId): bool;
}