<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TenantResolverMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            return $this->handleTenantNotFound($request);
        }

        if (!$tenant->isActive()) {
            return $this->handleInactiveTenant($request, $tenant);
        }

        // Store tenant in application context
        app()->instance('tenant', $tenant);
        app()->instance('tenant.id', $tenant->id);

        // Store in session for easy access
        session(['tenant' => $tenant]);

        return $next($request);
    }

    /**
     * Resolve tenant from the request.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        // Try subdomain-based detection first
        $subdomain = $this->extractSubdomain($request);

        if ($subdomain) {
            return $this->getTenantBySubdomain($subdomain);
        }

        // Fallback to domain-based detection
        $domain = $request->getHost();
        $tenant = $this->getTenantByDomain($domain);

        if ($tenant) {
            return $tenant;
        }

        // For development environments (localhost, 127.0.0.1, etc.)
        if ($this->isDevelopmentEnvironment($request)) {
            return $this->getDefaultTenant();
        }

        return null;
    }

    /**
     * Extract subdomain from request.
     */
    protected function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        // Skip if it's localhost or IP address
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Return first part as subdomain if we have more than 2 parts
        // e.g., "school1.eduvolt.com" -> "school1"
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }

    /**
     * Get tenant by subdomain with caching.
     */
    protected function getTenantBySubdomain(string $subdomain): ?Tenant
    {
        $cacheKey = "tenant:subdomain:{$subdomain}";

        return Cache::remember($cacheKey, 3600, function () use ($subdomain) {
            return Tenant::findBySubdomain($subdomain);
        });
    }

    /**
     * Get tenant by domain with caching.
     */
    protected function getTenantByDomain(string $domain): ?Tenant
    {
        $cacheKey = "tenant:domain:{$domain}";

        return Cache::remember($cacheKey, 3600, function () use ($domain) {
            return Tenant::where('domain', $domain)->first();
        });
    }

    /**
     * Handle when tenant is not found.
     */
    protected function handleTenantNotFound(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'The requested tenant could not be found.',
            ], 404);
        }

        // For development/testing, you might want to redirect to a tenant selection page
        // For production, you'd typically show a custom 404 page
        abort(404, 'Tenant not found');
    }

    /**
     * Handle when tenant is inactive.
     */
    protected function handleInactiveTenant(Request $request, Tenant $tenant): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant inactive',
                'message' => 'This tenant account is currently inactive.',
                'tenant' => [
                    'name' => $tenant->name,
                    'status' => $tenant->status,
                ],
            ], 403);
        }

        // Show maintenance page for inactive tenants
        return response()->view('errors.tenant-inactive', compact('tenant'), 503);
    }

    /**
     * Check if this is a development environment.
     */
    protected function isDevelopmentEnvironment(Request $request): bool
    {
        $host = $request->getHost();

        return in_array($host, [
            'localhost',
            '127.0.0.1',
            '::1',
        ]) || filter_var($host, FILTER_VALIDATE_IP) || app()->environment('local', 'testing');
    }

    /**
     * Get default tenant for development environments.
     */
    protected function getDefaultTenant(): ?Tenant
    {
        // Try to get a tenant from session first (for tenant switching in dev)
        if (session()->has('dev_tenant_id')) {
            $tenant = Tenant::find(session('dev_tenant_id'));
            if ($tenant) {
                return $tenant;
            }
        }

        // Fallback to first active tenant or create a default one
        return Tenant::where('status', 'active')->first() ?? $this->createDefaultTenant();
    }

    /**
     * Create a default tenant for development.
     */
    protected function createDefaultTenant(): Tenant
    {
        return Tenant::firstOrCreate(
            ['domain' => 'localhost'],
            [
                'name' => 'Development Tenant',
                'slug' => 'development',
                'subdomain' => 'dev',
                'status' => 'active',
                'settings' => [
                    'max_students' => 1000,
                    'max_teachers' => 100,
                    'features' => ['attendance', 'grades', 'reports', 'timetable'],
                ],
            ]
        );
    }
}

/**
 * Helper functions for tenant context.
 */
if (!function_exists('tenant')) {
    /**
     * Get the current tenant instance.
     */
    function tenant(): ?Tenant
    {
        return app('tenant');
    }
}

if (!function_exists('tenantId')) {
    /**
     * Get the current tenant ID.
     */
    function tenantId(): ?int
    {
        return app('tenant.id');
    }
}
