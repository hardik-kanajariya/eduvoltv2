<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class BaseTenantModel extends Model
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Apply tenant scope to all queries
        static::addGlobalScope(new TenantScope());

        // Automatically set tenant_id when creating
        static::creating(function ($model) {
            if (!$model->tenant_id && tenantId()) {
                $model->tenant_id = tenantId();
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to the current tenant.
     */
    public function scopeForCurrentTenant($query)
    {
        if ($tenantId = tenantId()) {
            return $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Create a new model instance without tenant scoping.
     */
    public static function withoutTenantScope()
    {
        return static::withoutGlobalScope(TenantScope::class);
    }

    /**
     * Get all models across all tenants (removes tenant scope).
     */
    public static function allTenants()
    {
        return static::withoutGlobalScope(TenantScope::class);
    }

    /**
     * Check if the model belongs to the current tenant.
     */
    public function belongsToCurrentTenant(): bool
    {
        return $this->tenant_id === tenantId();
    }

    /**
     * Check if the model belongs to a specific tenant.
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * Ensure tenant_id is always included in fillable array.
     */
    public function getFillable(): array
    {
        $fillable = parent::getFillable();

        if (!in_array('tenant_id', $fillable)) {
            $fillable[] = 'tenant_id';
        }

        return $fillable;
    }
}
