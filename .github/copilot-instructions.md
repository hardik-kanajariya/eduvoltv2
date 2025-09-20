# EduVoltV2 - Educational SaaS Platform

EduVoltV2 is a Laravel 12-based educational SaaS platform with comprehensive multi-tenant architecture, validation systems, and event-driven design. The platform is **FULLY IMPLEMENTED** with sophisticated foundation patterns and comprehensive testing suite.

**ALWAYS reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

## Current Repository State

**PRODUCTION-READY**: This repository contains a fully functional Laravel 12 application with sophisticated foundation patterns implemented and tested.

### Current Architecture
- **Backend**: Laravel 12.x (PHP 8.2+) with specialized form request validation system
- **Database**: Multi-tenant architecture with full schema implemented (tenants, users, sessions, jobs, cache)
- **Queue System**: Redis-based with comprehensive health monitoring and testing tools
- **Frontend**: Tailwind CSS 4.0 + Vite build system
- **Multi-tenancy**: Complete tenant-scoped validation rules and data isolation patterns
- **Health Monitoring**: Advanced health check endpoint at `/health` with Redis, database, cache, and queue checks
- **Authentication**: Full Laravel Breeze-style auth system with email verification

### Key Implementation Patterns (IMPLEMENTED)
- **Advanced Form Validation**: `BaseFormRequest` and `TenantScopedFormRequest` classes with 30+ helper traits
- **Multi-tenant Data Scoping**: `TenantExists` validation rule with automatic tenant scoping
- **Event-Driven Architecture**: `BaseEvent` abstract class with automatic metadata and structured event testing
- **Custom Validation Rules**: `PhoneNumber`, `StrongPassword`, `AcademicGrade`, and `TenantExists` with flexible configuration
- **Queue System**: Redis-based with Docker Sail, comprehensive testing scripts, and health monitoring
- **Policy System**: `BaseTenantPolicy` with tenant-aware authorization patterns
- **Testing Infrastructure**: 49+ tests with specialized helper traits and factories

## Essential Development Workflows

### Quick Start (Development Environment)
```bash
# Copy environment file (if not already done)
cp .env.example .env

# Alternative: Use direct PHP instead of Sail if Docker unavailable
php artisan migrate
php artisan serve    # Instead of sail

# OR use Docker Sail environment
./vendor/bin/sail up -d
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
./vendor/bin/sail artisan migrate

# View application: http://localhost
# Health check: http://localhost/health
```

### Code Quality & Testing Workflow
```bash
# MANDATORY before commits - NEVER CANCEL these
php vendor/bin/pint                    # Laravel Pint formatting (PSR-12) - direct PHP
php artisan test                       # Run PHPUnit test suite (49+ tests, all passing)

# OR with Sail:
./vendor/bin/sail php vendor/bin/pint
./vendor/bin/sail php artisan test

# Queue system testing
php artisan queue:test --redis-check      # Test Redis connectivity
php artisan queue:test --dispatch="Test"  # Dispatch test job
# ./manage-queue.sh health                # Full queue health check (if available)
```

### Form Request Development Pattern
```bash
# Create tenant-scoped form request (use actual patterns from codebase)
php artisan make:request Student/StoreStudentRequest

# Extend TenantScopedFormRequest and implement getTenantScopedRules()
# Use validation helpers: $this->getRulesFor('name_rules'), $this->getPhoneRule()
# Test with: php artisan test tests/Feature/Student/
```

### Event System Development
```bash
# Create events and listeners (auto-discovered in app/Events/ and app/Listeners/)
php artisan make:event Audit/UserActionEvent
php artisan make:listener Audit/LogUserAction

# Verify event discovery
php artisan event:list

# Test events with EventFactory and EventTestingHelpers trait
```

## Current Implementation Patterns (FULLY IMPLEMENTED)

### Multi-Tenant Architecture (COMPLETE)
The project implements a complete multi-tenant SaaS architecture:

**Tenant Model**: Full tenant management with fields: `id`, `name`, `slug`, `domain`, `subdomain`, `database_name`, `status`, `settings`, `trial_ends_at`
```php
// Tenant::create() with all fields implemented
// Relationships: hasMany(User::class), users() method
// Helper methods: isActive(), isOnTrial()
```

**User-Tenant Relationship**: Implemented with foreign key constraints
```php
// Users table includes tenant_id with proper foreign key constraint
// User model: belongsTo(Tenant::class) relationship
// Migration: tenant_id foreign key with cascade delete
```

### Form Request Validation System (COMPLETE)
**BaseFormRequest**: Foundation class with comprehensive patterns (`app/Http/Requests/BaseFormRequest.php`)
```php
// Use pre-defined patterns instead of manual rules
'first_name' => $this->getRulesFor('name_rules'),
'email' => $this->getRulesFor('email_rules', ['unique:users,email']),
'tenant_id' => $this->getRulesFor('tenant_id_rules'),

// Available patterns: email_rules, phone_rules, name_rules, password_rules, 
// date_rules, optional_text_rules, required_text_rules, numeric_id_rules
```

**TenantScopedFormRequest**: Auto-applies tenant scoping to `exists` rules (`app/Http/Requests/TenantScopedFormRequest.php`)
```php
// Before: 'course_id' => ['required', 'exists:courses,id']
// After: 'course_id' => ['required', 'exists:courses,id,tenant_id,5']
protected function getTenantScopedRules(): array { /* implement rules */ }
```

**HasValidationHelpers Trait**: 30+ specialized helpers for educational domain (`app/Http/Requests/Traits/HasValidationHelpers.php`)
```php
$this->getAcademicYearRules()      // Current year ±10/+5
$this->getStudentAgeRules(5, 100)  // Age validation with date constraints
$this->getAcademicGradeRule('gpa') // Multiple grading systems
$this->getCurrencyRules(0, 99999)  // Fee validation with decimal precision
$this->getFileUploadRules(['pdf', 'doc'], 2048)  // File upload validation
$this->getImageUploadRules(1024)   // Image upload validation
```

### Custom Validation Rules (COMPLETE)
- **TenantExists** (`app/Rules/TenantExists.php`): Validates resources exist within tenant scope
```php
new TenantExists('courses', 'id', $tenantId)
// Usage: 'course_id' => ['required', 'integer', new TenantExists('courses')]
```
- **PhoneNumber** (`app/Rules/PhoneNumber.php`): International phone validation with country restrictions
```php
PhoneNumber::withCountryCode(['US']) // Requires country code
PhoneNumber::forCountries(['US', 'UK', 'AU']) // Specific countries
```
- **StrongPassword** (`app/Rules/StrongPassword.php`): Configurable strength levels
```php
StrongPassword::moderate() // Different strength levels
```
- **AcademicGrade** (`app/Rules/AcademicGrade.php`): Multi-system grade validation

### Event-Driven Architecture with Testing (COMPLETE)
**BaseEvent** (`app/Events/BaseEvent.php`): Auto-generates metadata (timestamp, environment, event class)
```php
// All events extend BaseEvent and implement getEventName() and getEventData()
abstract class BaseEvent {
    public readonly Carbon $timestamp;
    public readonly array $metadata;
    abstract public function getEventName(): string;
    abstract public function getEventData(): array;
}
```

**Implemented Events** (`app/Events/Audit/`):
- `UserActionEvent` - User CRUD operations
- `SystemEvent` - System-level events
- `DataChangeEvent` - Database change tracking

**Event Testing Pattern**: Use `EventFactory` and `EventTestingHelpers` trait
```php
// In tests: $event = EventFactory::createUserActionEvent(['action' => 'delete']);
use Tests\Support\EventTestingHelpers;
$this->fakeEvents();
Event::dispatch($event);
$this->assertEventDispatched(UserActionEvent::class);
$this->assertEventHasData(UserActionEvent::class, ['action' => 'delete']);
```

### Authorization & Policy System (COMPLETE)
**BaseTenantPolicy** (`app/Policies/BaseTenantPolicy.php`): Tenant-aware authorization
```php
// Automatically checks tenant ownership for all model operations
// Methods: view, viewAny, create, update, delete with tenant scoping
// Helper: belongsToTenant($model, $tenantId), userCanAccessTenant($tenantId)
```

**Policy Testing** (`tests/Support/PolicyTestCase.php`): Specialized testing utilities
```php
$this->createMockUser($tenantId);
$this->createMockModel($tenantId);
$this->testTenantScenarios($callback);
$this->assertPolicyPasses($callback);
```

### Queue System & Health Monitoring (COMPLETE)
**Redis-First Queue**: Configured for background job processing with comprehensive monitoring
```bash
# Test Redis connectivity and dispatch jobs (WORKING COMMANDS)
php artisan queue:test --redis-check      # Test Redis connectivity
php artisan queue:test --dispatch="Message"  # Dispatch test job

# Optional Docker Sail versions:
./vendor/bin/sail artisan queue:test --redis-check
./vendor/bin/sail artisan queue:test --dispatch="Message"

# Queue worker command:
php artisan queue:work redis --verbose
```

**TestQueueJob** (`app/Jobs/TestQueueJob.php`): Example job with proper error handling and logging
- Implements `ShouldQueue` with timeout (60s) and retry (3 attempts) configuration
- Uses traits: `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`
- Comprehensive logging with structured data

**Health Monitoring** (`app/Http/Controllers/HealthController.php`): Advanced health check at `/health`
```json
{
  "status": "healthy",
  "checks": {
    "database": {"status": "healthy", "message": "Database connection successful"},
    "cache": {"status": "healthy", "message": "Cache is working properly"},
    "redis": {"status": "healthy", "message": "Redis connection successful"},
    "queue": {"status": "healthy", "message": "Queue system operational", "connection": "redis"}
  },
  "info": {
    "app_name": "EduVoltV2", "environment": "local", "laravel_version": "12.x"
  }
}
```

### Database Architecture (COMPLETE)
- **Multi-Environment Support**: SQLite for local development, MySQL for Docker/production
- **Complete Schema**: tenants, users (with tenant_id FK), sessions, cache, jobs, failed_jobs tables
- **Foreign Key Constraints**: Proper tenant_id relationships with cascade delete
- **Seeding System**: TenantSeeder with demo data, DatabaseSeeder integration

### Testing Infrastructure (COMPLETE)
**Test Structure** (49+ tests, 120+ assertions):
```bash
tests/
├── Support/
│   ├── EventTestingHelpers.php    # Event testing utilities
│   ├── EventFactory.php           # Event instance creation
│   └── PolicyTestCase.php         # Policy testing base class
├── Unit/
│   ├── Events/                    # Event unit tests
│   ├── Policies/                  # Policy unit tests
│   └── Rules/                     # Validation rule tests
└── Feature/
    ├── Events/                    # Event integration tests
    └── Auth/                      # Authentication tests
```

**Testing Patterns**:
```php
// Form Request Testing
$request = new StoreStudentRequest();
$validator = Validator::make([], $request->rules());
$this->assertTrue($validator->fails());

// Event Testing
use Tests\Support\EventTestingHelpers;
$this->fakeEvents();
$event = EventFactory::createUserActionEvent(['action' => 'create']);
Event::dispatch($event);
$this->assertEventDispatched(UserActionEvent::class);

// Policy Testing
$this->testTenantScenarios(function ($user, $model) {
    return $this->policy->view($user, $model);
});

// Validation Rule Testing (callback-based)
$rule = new PhoneNumber();
$passes = true;
$rule->validate('phone', '+1234567890', function() use (&$passes) {
    $passes = false;
});
$this->assertTrue($passes);
```

## Development Workflow Guidelines

### Adding New Features
1. **Start with migrations**: `php artisan make:migration create_feature_table`
2. **Create models**: `php artisan make:model Feature`
3. **Build controllers**: `php artisan make:controller FeatureController`
4. **Define routes**: Add to `routes/web.php` or create API routes
5. **Create tests**: `php artisan make:test FeatureTest`
6. **Run validation**: Always test health endpoint and existing functionality

### Code Quality Workflow
```bash
# MANDATORY before commits - NEVER CANCEL these
php vendor/bin/pint                    # 1-2 minutes
php artisan test                       # 5-10 minutes, timeout 20+ minutes
```

### Testing Patterns (49 tests, 120 assertions)
**Form Request Testing**: Validate rules, authorization, and tenant scoping
```php
$request = new StoreStudentRequest();
$validator = Validator::make([], $request->rules());
$this->assertTrue($validator->fails());
```

**Event Testing**: Use `EventTestingHelpers` trait and `EventFactory`
```php
use Tests\Support\EventTestingHelpers;
$this->fakeEvents();
$event = EventFactory::createUserActionEvent(['action' => 'create']);
Event::dispatch($event);
$this->assertEventDispatched(UserActionEvent::class);
$this->assertEventHasData(UserActionEvent::class, ['action' => 'create']);
```

**Validation Rule Testing**: Custom rules use callback-based validation
```php
$rule = new PhoneNumber();
$passes = true;
$rule->validate('phone', '+1234567890', function() use (&$passes) {
    $passes = false;
});
$this->assertTrue($passes);
```

### Debugging and Troubleshooting
- **Logs**: Check `storage/logs/laravel.log` for application errors
- **Database**: Use `php artisan tinker` for interactive queries
- **Docker Issues**: `./vendor/bin/sail down && ./vendor/bin/sail up -d`
- **Asset Problems**: `npm run build` to rebuild frontend
- **Health Check**: Visit `/health` endpoint to verify system status

## Performance Expectations

### Build and Test Times (Current Environment)
- **Initial Docker setup**: 5-10 minutes (NEVER CANCEL - timeout 15+ minutes)
- **Composer install**: 2-3 minutes (timeout 10+ minutes)
- **Database migrations**: 2-5 minutes (timeout 10+ minutes)
- **Full test suite**: 5-10 minutes (NEVER CANCEL - timeout 20+ minutes)
- **Frontend build**: 2-5 minutes (timeout 10+ minutes)
- **Code formatting**: 1-2 minutes (timeout 5+ minutes)

### Development Server Startup
- Docker Sail containers: 30-60 seconds
- Laravel application boot: 5-10 seconds
- Database connections: 2-3 seconds

## Security Considerations

Based on specifications:
- Multi-tenant data isolation with tenant_id scoping
- Role-based access control (RBAC) implementation
- API authentication using Laravel Sanctum/Passport
- Input validation and sanitization
- SQL injection prevention through Eloquent ORM
- XSS protection with blade templating

## Troubleshooting

### Common Issues When Implementation Begins
1. **Docker Sail not starting**: Check Docker daemon and port conflicts
2. **Database connection failed**: Verify MySQL container and credentials
3. **Tenant data issues**: Ensure all models use tenant scoping
4. **Test failures**: Check tenant isolation and data seeding
5. **Performance issues**: Monitor telescope for N+1 queries

### Log Locations
- Laravel logs: `storage/logs/laravel.log`
- Docker Sail logs: `./vendor/bin/sail logs`
- Test output: `tests/coverage/`

## Issue Reference

For detailed feature specifications, search `github_issues.csv`:

### By Epic (Major Feature Groups)
- QA/Testing: `grep "epic:qa" github_issues.csv` (29 issues)
- Documentation: `grep "epic:docs" github_issues.csv` (21 issues)  
- Students: `grep "epic:students" github_issues.csv` (12 issues)
- Security: `grep "epic:security" github_issues.csv` (12 issues)
- Reports: `grep "epic:reports" github_issues.csv` (11 issues)
- Foundation: `grep "epic:foundation" github_issues.csv` (8 issues)

### By Functional Area
- Security: `grep "area:security" github_issues.csv` (14 issues)
- Student Management: `grep "area:students" github_issues.csv` (11 issues)
- Quality Assurance: `grep "area:qa" github_issues.csv` (11 issues)
- Timetable: `grep "area:timetable" github_issues.csv` (8 issues)
- Foundation/Core: `grep "area:foundation" github_issues.csv` (8 issues)
- Fees/Billing: `grep "area:fees" github_issues.csv` (8 issues)
- Attendance: `grep "area:attendance" github_issues.csv` (8 issues)

### By Priority and Technology
- Multi-tenancy: `grep "saas:multitenant" github_issues.csv` (13 issues)
- Priority P1 (MVP): `grep "priority:P1" github_issues.csv` (82 issues)
- Laravel Stack: `grep "stack:laravel12" github_issues.csv` (78 issues)
- Database Related: `grep "db:mysql" github_issues.csv` (4 issues)

### Quick Analysis Commands
```bash
# Count issues by priority
grep -o 'priority:[^,]*' github_issues.csv | sort | uniq -c | sort -nr

# Count issues by status  
grep -o 'status:[^,]*' github_issues.csv | sort | uniq -c | sort -nr

# Find issues with specific estimate
grep "estimate:2h" github_issues.csv | wc -l
grep "estimate:4h" github_issues.csv | wc -l
```

**Total Issues**: 4061 detailed specifications covering all platform aspects