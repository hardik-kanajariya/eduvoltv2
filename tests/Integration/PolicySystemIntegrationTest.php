<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Policies\BaseTenantPolicy;
use App\Services\PolicyRegistrationService;
use Tests\TestCase;

/**
 * Integration test for the complete policy system
 */
class PolicySystemIntegrationTest extends TestCase
{
    public function test_policy_system_integration(): void
    {
        // Test that the policy registration service can be instantiated
        $service = app(PolicyRegistrationService::class);
        $this->assertInstanceOf(PolicyRegistrationService::class, $service);

        // Test that policies can be registered
        $this->expectNotToPerformAssertions();
        $service->registerPolicies();
    }

    public function test_base_tenant_policy_can_be_instantiated(): void
    {
        // Create a concrete implementation for testing
        $policy = new class () extends BaseTenantPolicy {
            // Concrete implementation for testing
        };

        $this->assertInstanceOf(BaseTenantPolicy::class, $policy);
    }

    public function test_app_service_provider_boots_successfully(): void
    {
        // This test ensures that the AppServiceProvider can boot without errors
        // and that the policy registration service is properly registered
        $this->expectNotToPerformAssertions();

        // Re-boot the application to test the service provider
        $this->app->make('App\Providers\AppServiceProvider')->boot();
    }
}
