<?php

namespace App\Traits;

use App\Scopes\TenantScope;

trait HasTenant
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            if (!$model->tenant_id && app()->bound('current_tenant_id')) {
                $model->tenant_id = app('current_tenant_id');
            }
        });
    }

    /**
     * Get the current tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->tenant_id;
    }

    /**
     * Set the tenant ID for the model.
     */
    public function setTenantId(int $tenantId): void
    {
        $this->setAttribute('tenant_id', $tenantId);
    }

    /**
     * Scope a query to exclude tenant filtering.
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope a query to a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId);
    }
}