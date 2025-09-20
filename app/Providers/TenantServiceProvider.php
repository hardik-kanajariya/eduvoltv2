<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('current_tenant_id', function () {
            return null; // Will be set by middleware or manually in tests
        });

        $this->app->singleton('current_tenant', function () {
            $tenantId = app('current_tenant_id');

            return $tenantId ? Tenant::find($tenantId) : null;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Set the current tenant context.
     */
    public static function setTenant(?int $tenantId): void
    {
        app()->instance('current_tenant_id', $tenantId);
        app()->forgetInstance('current_tenant');
    }

    /**
     * Get the current tenant ID.
     */
    public static function getCurrentTenantId(): ?int
    {
        return app('current_tenant_id');
    }

    /**
     * Get the current tenant.
     */
    public static function getCurrentTenant(): ?Tenant
    {
        return app('current_tenant');
    }
}
