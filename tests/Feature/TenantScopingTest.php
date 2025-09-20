<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Providers\TenantServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_are_scoped_to_tenant(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Create users for each tenant
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id, 'name' => 'User 1']);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id, 'name' => 'User 2']);

        // Set tenant context to tenant 1
        TenantServiceProvider::setTenant($tenant1->id);

        // Should only see users from tenant 1
        $users = User::all();
        $this->assertCount(1, $users);
        $this->assertEquals('User 1', $users->first()->name);
        $this->assertEquals($tenant1->id, $users->first()->tenant_id);

        // Set tenant context to tenant 2
        TenantServiceProvider::setTenant($tenant2->id);

        // Should only see users from tenant 2
        $users = User::all();
        $this->assertCount(1, $users);
        $this->assertEquals('User 2', $users->first()->name);
        $this->assertEquals($tenant2->id, $users->first()->tenant_id);
    }

    public function test_users_without_tenant_scope_shows_all(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create users for each tenant
        User::factory()->create(['tenant_id' => $tenant1->id]);
        User::factory()->create(['tenant_id' => $tenant2->id]);

        // Set tenant context
        TenantServiceProvider::setTenant($tenant1->id);

        // Without tenant scope, should see all users
        $users = User::withoutTenant()->get();
        $this->assertCount(2, $users);
    }

    public function test_user_creation_auto_assigns_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();

        // Set tenant context
        TenantServiceProvider::setTenant($tenant->id);

        // Create user without explicitly setting tenant_id
        $user = User::factory()->create(['name' => 'Auto Tenant User']);

        $this->assertEquals($tenant->id, $user->tenant_id);
    }

    public function test_tenant_relationship_works(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $user->tenant);
        $this->assertEquals('Test Tenant', $user->tenant->name);
        $this->assertTrue($tenant->users->contains($user));
    }
}
