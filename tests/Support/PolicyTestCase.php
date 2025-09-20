<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tests\TestCase;

/**
 * Base test case for testing policies with tenant scoping
 *
 * Provides helper methods and utilities for testing tenant-aware policies,
 * including mock users, models, and tenant scenarios.
 */
abstract class PolicyTestCase extends TestCase
{
    /**
     * Create a mock user for testing.
     */
    protected function createMockUser(?int $tenantId = null): Authenticatable
    {
        // Create a simple mock user object
        $user = new class () extends Authenticatable {
            protected $fillable = ['id', 'tenant_id', 'name', 'email'];

            public function __construct(array $attributes = [])
            {
                $this->attributes = $attributes;
            }
        };

        $user->fill([
            'id' => $tenantId ?? 1,
            'tenant_id' => $tenantId,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        return $user;
    }

    /**
     * Create a mock model for testing with tenant scoping.
     */
    protected function createMockModel(?int $tenantId = null, array $attributes = []): Model
    {
        $model = new class () extends Model {
            protected $fillable = ['id', 'tenant_id', 'name'];
            public $timestamps = false;

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
                $this->exists = true;
            }
        };

        $defaultAttributes = [
            'id' => 1,
            'tenant_id' => $tenantId ?? 1,
            'name' => 'Test Model',
        ];

        $model->fill(array_merge($defaultAttributes, $attributes));

        return $model;
    }

    /**
     * Create a mock model without tenant_id for testing.
     */
    protected function createMockModelWithoutTenant(array $attributes = []): Model
    {
        $model = new class () extends Model {
            protected $fillable = ['id', 'name'];
            public $timestamps = false;

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
                $this->exists = true;
            }
        };

        $defaultAttributes = [
            'id' => 1,
            'name' => 'Test Model Without Tenant',
        ];

        $model->fill(array_merge($defaultAttributes, $attributes));

        return $model;
    }

    /**
     * Assert that a policy method returns true.
     */
    protected function assertPolicyPasses(callable $policyMethod, string $message = ''): void
    {
        $result = call_user_func($policyMethod);
        $this->assertTrue($result, $message ?: 'Policy check should pass');
    }

    /**
     * Assert that a policy method returns false.
     */
    protected function assertPolicyFails(callable $policyMethod, string $message = ''): void
    {
        $result = call_user_func($policyMethod);
        $this->assertFalse($result, $message ?: 'Policy check should fail');
    }

    /**
     * Test common tenant scenarios for a policy method.
     *
     * @param callable $policyMethodFactory Function that takes (user, model) and returns policy result
     * @param bool $expectPassForSameTenant Should the policy pass when user and model have same tenant
     * @param bool $expectFailForDifferentTenant Should the policy fail when user and model have different tenants
     */
    protected function testTenantScenarios(
        callable $policyMethodFactory,
        bool $expectPassForSameTenant = true,
        bool $expectFailForDifferentTenant = true
    ): void {
        // Test same tenant scenario
        $user = $this->createMockUser(1);
        $model = $this->createMockModel(1);

        $result = $policyMethodFactory($user, $model);

        if ($expectPassForSameTenant) {
            $this->assertTrue($result, 'Policy should pass when user and model belong to same tenant');
        } else {
            $this->assertFalse($result, 'Policy should fail even when user and model belong to same tenant');
        }

        // Test different tenant scenario
        $user = $this->createMockUser(1);
        $model = $this->createMockModel(2);

        $result = $policyMethodFactory($user, $model);

        if ($expectFailForDifferentTenant) {
            $this->assertFalse($result, 'Policy should fail when user and model belong to different tenants');
        } else {
            $this->assertTrue($result, 'Policy should pass even when user and model belong to different tenants');
        }

        // Test null user scenario
        $model = $this->createMockModel(1);
        $result = $policyMethodFactory(null, $model);
        $this->assertFalse($result, 'Policy should fail when user is null');

        // Test model without tenant
        $user = $this->createMockUser(1);
        $modelWithoutTenant = $this->createMockModelWithoutTenant();
        $result = $policyMethodFactory($user, $modelWithoutTenant);
        $this->assertFalse($result, 'Policy should fail when model has no tenant_id');
    }

    /**
     * Test tenant scenarios for policies that don't require a model (like viewAny, create).
     */
    protected function testTenantScenariosForNonModelPolicies(
        callable $policyMethodFactory,
        bool $expectPassForUserWithTenant = true
    ): void {
        // Test user with tenant
        $user = $this->createMockUser(1);
        $result = $policyMethodFactory($user);

        if ($expectPassForUserWithTenant) {
            $this->assertTrue($result, 'Policy should pass when user has tenant access');
        } else {
            $this->assertFalse($result, 'Policy should fail even when user has tenant access');
        }

        // Test null user
        $result = $policyMethodFactory(null);
        $this->assertFalse($result, 'Policy should fail when user is null');

        // Test user without tenant
        $userWithoutTenant = $this->createMockUser(null);
        $result = $policyMethodFactory($userWithoutTenant);
        $this->assertFalse($result, 'Policy should fail when user has no tenant access');
    }

    /**
     * Create multiple users with different tenant access for testing.
     */
    protected function createTenantUsers(): array
    {
        return [
            'tenant_1_user' => $this->createMockUser(1),
            'tenant_2_user' => $this->createMockUser(2),
            'no_tenant_user' => $this->createMockUser(null),
        ];
    }

    /**
     * Create multiple models in different tenants for testing.
     */
    protected function createTenantModels(): array
    {
        return [
            'tenant_1_model' => $this->createMockModel(1),
            'tenant_2_model' => $this->createMockModel(2),
            'no_tenant_model' => $this->createMockModelWithoutTenant(),
        ];
    }

    /**
     * Helper to test policy method with all user/model combinations.
     */
    protected function testPolicyWithAllCombinations(callable $policyMethodFactory, array $expectedResults): void
    {
        $users = $this->createTenantUsers();
        $models = $this->createTenantModels();

        foreach ($users as $userKey => $user) {
            foreach ($models as $modelKey => $model) {
                $result = $policyMethodFactory($user, $model);
                $expectedKey = "{$userKey}_with_{$modelKey}";

                if (isset($expectedResults[$expectedKey])) {
                    $expected = $expectedResults[$expectedKey];
                    $this->assertEquals(
                        $expected,
                        $result,
                        "Policy check failed for {$userKey} with {$modelKey}"
                    );
                }
            }
        }
    }
}
