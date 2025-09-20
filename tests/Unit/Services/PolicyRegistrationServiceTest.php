<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PolicyRegistrationService;
use Tests\TestCase;
use Illuminate\Support\Facades\Gate;

/**
 * Test the PolicyRegistrationService
 */
class PolicyRegistrationServiceTest extends TestCase
{
    private PolicyRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PolicyRegistrationService();
    }

    public function test_can_register_policy_manually(): void
    {
        // Create a mock model and policy for testing
        $modelClass = 'App\\Models\\TestModel';
        $policyClass = 'App\\Policies\\TestPolicy';

        // Mock the classes exist (in real scenario they would)
        if (!class_exists($modelClass)) {
            eval("namespace App\\Models; class TestModel {}");
        }
        if (!class_exists($policyClass)) {
            eval("namespace App\\Policies; class TestPolicy {}");
        }

        $this->service->registerPolicy($modelClass, $policyClass);

        $this->assertTrue($this->service->hasPolicy($modelClass));
    }

    public function test_throws_exception_for_invalid_model_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Model or Policy class does not exist.');

        $this->service->registerPolicy('NonExistentModel', 'NonExistentPolicy');
    }

    public function test_can_check_if_policy_exists(): void
    {
        $modelClass = 'App\\Models\\TestModel2';
        $policyClass = 'App\\Policies\\TestPolicy2';

        // Create the classes
        if (!class_exists($modelClass)) {
            eval("namespace App\\Models; class TestModel2 {}");
        }
        if (!class_exists($policyClass)) {
            eval("namespace App\\Policies; class TestPolicy2 {}");
        }

        $this->assertFalse($this->service->hasPolicy($modelClass));

        $this->service->registerPolicy($modelClass, $policyClass);

        $this->assertTrue($this->service->hasPolicy($modelClass));
    }

    public function test_get_registered_policies_returns_array(): void
    {
        $policies = $this->service->getRegisteredPolicies();
        $this->assertIsArray($policies);
    }

    public function test_register_policies_method_exists(): void
    {
        // Test that the method exists and can be called without error
        // In a real application this would discover and register actual policies
        $this->expectNotToPerformAssertions();
        $this->service->registerPolicies();
    }
}