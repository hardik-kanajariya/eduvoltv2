# Clean Architecture Implementation

This document explains the Clean Architecture structure implemented in this Laravel application.

## Architecture Overview

The application follows Clean Architecture principles with clear separation of concerns across three main layers:

### 1. Domain Layer (`app/Domain/`)
- **Entities/**: Business entities containing core business logic and rules
- **ValueObjects/**: Immutable objects that represent domain concepts
- **Contracts/**: Interfaces for repository and domain services
- **Services/**: Domain services for complex business operations

### 2. Application Layer (`app/Application/`)
- **UseCases/**: Application-specific business rules and use cases
- **Services/**: Application services that orchestrate domain operations
- **Contracts/**: Interfaces for application services

### 3. Infrastructure Layer (`app/Infrastructure/`)
- **Repositories/**: Concrete implementations of domain repository interfaces
- **Services/**: External service implementations (APIs, file systems, etc.)

## Key Principles

1. **Dependency Inversion**: High-level modules do not depend on low-level modules. Both depend on abstractions.
2. **Interface Segregation**: Interfaces are specific to client needs.
3. **Single Responsibility**: Each class has one reason to change.
4. **Open/Closed**: Open for extension, closed for modification.

## Example Implementation

### Domain Entity
```php
// app/Domain/Entities/User.php
class User
{
    private UserId $id;
    private string $name;
    private Email $email;
    
    public function verifyEmail(): void {
        // Business logic here
    }
}
```

### Repository Interface (Domain)
```php
// app/Domain/Contracts/UserRepositoryInterface.php
interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function save(User $user): User;
}
```

### Application Service
```php
// app/Application/Services/UserService.php
class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
    
    public function createUser(string $name, string $email): User {
        // Application logic here
    }
}
```

### Infrastructure Implementation
```php
// app/Infrastructure/Repositories/EloquentUserRepository.php
class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User {
        // Database access implementation
    }
}
```

### Controller (Interface Layer)
```php
// app/Http/Controllers/UserController.php
class UserController extends Controller
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}
    
    public function store(Request $request): JsonResponse {
        // HTTP request handling
    }
}
```

## Dependency Injection Configuration

Service bindings are configured in `app/Providers/AppServiceProvider.php`:

```php
$this->app->bind(
    UserRepositoryInterface::class,
    EloquentUserRepository::class
);

$this->app->bind(
    UserServiceInterface::class,
    UserService::class
);
```

## Testing

Tests are organized by architectural layer:

- `tests/Unit/Domain/`: Domain entity and value object tests
- `tests/Unit/Application/`: Application service tests
- `tests/Unit/Infrastructure/`: Repository implementation tests

## Benefits

1. **Testability**: Easy to mock dependencies and test business logic
2. **Maintainability**: Clear separation of concerns
3. **Flexibility**: Easy to change external dependencies without affecting business logic
4. **Scalability**: Structure supports growing complexity
5. **Framework Independence**: Business logic is not tied to Laravel

## Usage Example

```php
// In a controller
public function createUser(Request $request): JsonResponse
{
    try {
        $user = $this->userService->createUser(
            $request->name,
            $request->email
        );
        
        return response()->json($user->toArray(), 201);
    } catch (\DomainException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}
```

The architecture ensures that business rules are enforced at the domain level, application logic is coordinated in the application layer, and infrastructure concerns are isolated in the infrastructure layer.