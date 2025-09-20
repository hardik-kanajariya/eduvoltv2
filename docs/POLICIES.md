# Policy System Documentation

This documentation covers the tenant-aware policy system implemented in EduVoltV2, including base classes, automatic registration, and usage patterns.

## Overview

The policy system provides:

- **BaseTenantPolicy**: Base class with built-in tenant scoping for all operations
- **TenantAware Interface**: Contract for tenant-aware policies
- **Automatic Registration**: Service to discover and register policies automatically
- **Testing Utilities**: Helper classes for testing tenant-scoped policies
- **Authorization Helpers**: Methods for common authorization patterns

## Core Components

### BaseTenantPolicy

The foundation of all tenant-aware policies. Every policy extending this class automatically:

- Checks tenant ownership before allowing operations
- Provides helper methods for common authorization patterns
- Ensures data isolation between tenants
- Includes all standard Laravel policy methods (view, create, update, delete, etc.)

```php
<?php

namespace App\Policies;

use App\Policies\BaseTenantPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class StudentPolicy extends BaseTenantPolicy
{
    public function view(?Authenticatable $user, Model $model): bool
    {
        // Base tenant checking is automatically applied
        if (!parent::view($user, $model)) {
            return false;
        }

        // Add model-specific logic here
        return true;
    }
}
```

### TenantAware Interface

Defines the contract for tenant-aware policies:

```php
interface TenantAware
{
    public function belongsToTenant(mixed $model, int $tenantId): bool;
    public function getTenantIdFromModel(mixed $model): ?int;
    public function userCanAccessTenant(?int $tenantId): bool;
}
```

### Automatic Policy Registration

The `PolicyRegistrationService` automatically discovers and registers policies:

```php
// Automatically called in AppServiceProvider
$policyService = app(PolicyRegistrationService::class);
$policyService->registerPolicies();

// Manual registration
$policyService->registerPolicy(Student::class, StudentPolicy::class);
```

## Usage Patterns

### Basic Policy Implementation

```php
<?php

namespace App\Policies;

use App\Models\Student;
use App\Policies\BaseTenantPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class StudentPolicy extends BaseTenantPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        // Inherits tenant checking from parent
        return parent::viewAny($user);
    }

    public function create(?Authenticatable $user): bool
    {
        if (!parent::create($user)) {
            return false;
        }

        // Add role-based authorization
        return $user->hasPermission('create-students');
    }
}
```

### Custom Authorization Methods

```php
class StudentPolicy extends BaseTenantPolicy
{
    public function enroll(?Authenticatable $user, Model $student): bool
    {
        // Use the base tenant authorization helper
        if (!$this->authorizeWithTenant($user, $student)) {
            return false;
        }

        // Custom logic for enrollment
        return $user->hasPermission('enroll-students');
    }

    public function transfer(?Authenticatable $user, Model $student, int $targetTenantId): bool
    {
        // Use built-in transfer method
        return parent::transfer($user, $student, $targetTenantId);
    }
}
```

### Tenant-Specific Operations

```php
class StudentPolicy extends BaseTenantPolicy
{
    public function createInSpecificTenant(?Authenticatable $user, int $tenantId): bool
    {
        return $this->createInTenant($user, [], $tenantId);
    }

    public function updateWithTenantChange(?Authenticatable $user, Model $student, array $attributes): bool
    {
        return $this->updateInTenant($user, $student, $attributes);
    }
}
```

## Authorization Helpers

The BaseTenantPolicy provides several helper methods:

### Core Authorization

- `authorizeWithTenant($user, $model, $tenantId)` - Base tenant authorization check
- `belongsToTenant($model, $tenantId)` - Check if model belongs to tenant
- `userCanAccessTenant($tenantId)` - Check if user can access tenant

### Model Operations

- `createInTenant($user, $attributes, $tenantId)` - Create model in specific tenant
- `updateInTenant($user, $model, $attributes)` - Update with tenant validation
- `transfer($user, $model, $targetTenantId)` - Transfer model between tenants

### Standard Laravel Methods

All standard Laravel policy methods are implemented with tenant scoping:

- `viewAny($user)` - View any models (with tenant access check)
- `view($user, $model)` - View specific model (with tenant ownership check)
- `create($user)` - Create new models (with tenant access check)
- `update($user, $model)` - Update model (with tenant ownership check)
- `delete($user, $model)` - Delete model (with tenant ownership check)
- `restore($user, $model)` - Restore soft-deleted model
- `forceDelete($user, $model)` - Permanently delete model

## Testing

### Using PolicyTestCase

The `PolicyTestCase` provides utilities for testing tenant-scoped policies:

```php
<?php

namespace Tests\Unit\Policies;

use App\Policies\StudentPolicy;
use Tests\Support\PolicyTestCase;

class StudentPolicyTest extends PolicyTestCase
{
    private StudentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StudentPolicy();
    }

    public function test_view_policy(): void
    {
        $this->testTenantScenarios(function ($user, $model) {
            return $this->policy->view($user, $model);
        });
    }

    public function test_create_policy(): void
    {
        $this->testTenantScenariosForNonModelPolicies(function ($user) {
            return $this->policy->create($user);
        });
    }
}
```

### Testing Utilities

- `createMockUser($tenantId)` - Create mock user with tenant
- `createMockModel($tenantId)` - Create mock model with tenant
- `testTenantScenarios($callback)` - Test all tenant combinations
- `assertPolicyPasses($callback)` - Assert policy returns true
- `assertPolicyFails($callback)` - Assert policy returns false

## Implementation Notes

### Tenant Access Logic

The current implementation includes placeholder logic for tenant access checking. You need to implement:

```php
protected function userCanAccessTenant(?int $tenantId): bool
{
    // Implement actual tenant access checking logic
    // This would typically check against a user_tenants pivot table
    // or similar multi-tenant access control mechanism
    
    return true; // Placeholder - implement actual logic
}

protected function getCurrentTenantId(?Authenticatable $user = null): ?int
{
    // Implement getting user's default tenant
    // This would typically come from a user property or relationship
    
    return null; // Placeholder - implement actual logic
}
```

### Model Requirements

For policies to work correctly, models must:

1. Have a `tenant_id` attribute
2. Implement proper tenant scoping in queries
3. Follow Laravel naming conventions for automatic policy discovery

### Gate Integration

Policies are automatically registered with Laravel's Gate system. You can use them anywhere:

```php
// In controllers
$this->authorize('view', $student);
$this->authorize('create', Student::class);

// In Blade templates
@can('update', $student)
    <button>Edit</button>
@endcan

// In code
if (Gate::allows('delete', $student)) {
    $student->delete();
}
```

## Best Practices

1. **Always extend BaseTenantPolicy** for tenant-scoped resources
2. **Call parent methods first** before adding custom logic
3. **Use authorization helpers** for consistent tenant checking
4. **Test all tenant scenarios** using the provided test utilities
5. **Implement proper tenant access logic** in the placeholder methods
6. **Follow Laravel naming conventions** for automatic policy discovery
7. **Document custom policy methods** for team understanding

## Security Considerations

- **Tenant isolation**: All policies automatically enforce tenant boundaries
- **Data leakage prevention**: Models without `tenant_id` are automatically rejected
- **User authentication**: All policy methods check for authenticated users
- **Permission inheritance**: Child policies inherit tenant restrictions from parent
- **Transfer validation**: Moving models between tenants requires explicit authorization

## Example: Complete Policy Implementation

```php
<?php

namespace App\Policies;

use App\Models\Course;
use App\Policies\BaseTenantPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CoursePolicy extends BaseTenantPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return parent::viewAny($user) && $user->hasRole(['teacher', 'admin']);
    }

    public function view(?Authenticatable $user, Model $course): bool
    {
        if (!parent::view($user, $course)) {
            return false;
        }

        // Teachers can view courses they're assigned to
        if ($user->hasRole('teacher') && $course->teachers->contains($user)) {
            return true;
        }

        // Admins can view all courses in their tenant
        return $user->hasRole('admin');
    }

    public function create(?Authenticatable $user): bool
    {
        return parent::create($user) && $user->hasPermission('create-courses');
    }

    public function update(?Authenticatable $user, Model $course): bool
    {
        if (!parent::update($user, $course)) {
            return false;
        }

        return $user->hasPermission('edit-courses') || 
               ($user->hasRole('teacher') && $course->teachers->contains($user));
    }

    public function publish(?Authenticatable $user, Model $course): bool
    {
        if (!$this->authorizeWithTenant($user, $course)) {
            return false;
        }

        return $user->hasPermission('publish-courses');
    }
}
```

This policy demonstrates:
- Proper inheritance from BaseTenantPolicy
- Role-based authorization combined with tenant scoping
- Custom methods for model-specific operations
- Consistent use of authorization helpers