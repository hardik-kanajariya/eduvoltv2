<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply tenant scoping if we have a current tenant
        if ($tenantId = tenantId()) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }

    /**
     * Extend the query builder with the necessary where clauses.
     */
    public function extend(Builder $builder): void
    {
        $this->addWithoutTenant($builder);
        $this->addWithTenant($builder);
        $this->addOnlyTenant($builder);
    }

    /**
     * Add the without-tenant extension to the builder.
     */
    protected function addWithoutTenant(Builder $builder): void
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the with-tenant extension to the builder.
     */
    protected function addWithTenant(Builder $builder): void
    {
        $builder->macro('withTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
        });
    }

    /**
     * Add the only-tenant extension to the builder.
     */
    protected function addOnlyTenant(Builder $builder): void
    {
        $builder->macro('onlyTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
        });
    }
}