# EduVoltV2 - Educational SaaS Platform

EduVoltV2 is a Laravel 12-based educational SaaS platform with comprehensive multi-tenant architecture, validation systems, and event-driven design. The foundation includes Docker Sail for development, configured for MySQL/SQLite database support, with Tailwind CSS and Vite for frontend assets.

**ALWAYS reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

## Current Repository State

**ACTIVE DEVELOPMENT**: This repository contains a functional Laravel 12 application with sophisticated foundation patterns implemented.

### Current Architecture
- **Backend**: Laravel 12.x (PHP 8.2+) with specialized form request validation system
- **Database**: Dual-mode setup - SQLite default, MySQL via Docker Sail
- **Queue System**: Redis-based with comprehensive health monitoring and testing tools
- **Frontend**: Tailwind CSS 4.0 + Vite build system
- **Multi-tenancy**: Tenant-scoped validation rules and data isolation patterns
- **Health Monitoring**: Advanced health check endpoint at `/health` with Redis, database, cache, and queue checks

### Key Implementation Patterns
- **Advanced Form Validation**: Custom `BaseFormRequest` and `TenantScopedFormRequest` classes with helper traits
- **Multi-tenant Data Scoping**: `TenantExists` validation rule and automatic tenant scoping
- **Event-Driven Architecture**: `BaseEvent` classes with automatic metadata and structured event testing
- **Custom Validation Rules**: `PhoneNumber`, `StrongPassword`, `AcademicGrade`, and `TenantExists` with flexible configuration
- **Queue System**: Redis-based with Docker Sail, comprehensive testing scripts, and health monitoring
- **Database-first sessions/cache/queue** for multi-environment compatibility

## Essential Development Workflows

### Quick Start (Development Environment)
```bash
# Copy environment file (if not already done)
cp .env.example .env

# Start Docker Sail environment
./vendor/bin/sail up -d

# Install frontend dependencies
./vendor/bin/sail npm install

# Build assets for development
./vendor/bin/sail npm run dev

# Run database migrations
./vendor/bin/sail artisan migrate

# View application: http://localhost
# Health check: http://localhost/health
```

### Code Quality & Testing Workflow
```bash
# MANDATORY before commits - NEVER CANCEL these
./vendor/bin/sail php vendor/bin/pint                    # Laravel Pint formatting (PSR-12)
./vendor/bin/sail php artisan test                       # Run PHPUnit test suite (49 tests, all passing)

# Queue system testing
./vendor/bin/sail artisan queue:test --redis-check      # Test Redis connectivity
./vendor/bin/sail artisan queue:test --dispatch="Test"  # Dispatch test job
./manage-queue.sh health                                 # Full queue health check
```

### Form Request Development Pattern
```bash
# Create tenant-scoped form request
./vendor/bin/sail artisan make:request Student/StoreStudentRequest

# Extend TenantScopedFormRequest and implement getTenantScopedRules()
# Use validation helpers: $this->getRulesFor('name_rules'), $this->getPhoneRule()
# Test with: ./vendor/bin/sail php artisan test tests/Feature/Student/
```

### Event System Development
```bash
# Create events and listeners (auto-discovered in app/Events/ and app/Listeners/)
./vendor/bin/sail artisan make:event Audit/UserActionEvent
./vendor/bin/sail artisan make:listener Audit/LogUserAction

# Verify event discovery
./vendor/bin/sail artisan event:list

# Test events with EventFactory and EventTestingHelpers trait
```

## Current Implementation Patterns

### Multi-Tenant Form Request Architecture
The project implements sophisticated form validation with automatic tenant scoping:

**BaseFormRequest**: Foundation class with common validation patterns and tenant authorization
```php
// Use pre-defined patterns instead of manual rules
'first_name' => $this->getRulesFor('name_rules'),
'email' => $this->getRulesFor('email_rules', ['unique:users,email']),
```

**TenantScopedFormRequest**: Auto-applies tenant scoping to `exists` rules
```php
// Before: 'course_id' => ['required', 'exists:courses,id']
// After: 'course_id' => ['required', 'exists:courses,id,tenant_id,5']
protected function getTenantScopedRules(): array { /* implement rules */ }
```

**HasValidationHelpers Trait**: 30+ specialized helpers for educational domain
```php
$this->getAcademicYearRules()      // Current year ±10/+5
$this->getStudentAgeRules(5, 100)  // Age validation with date constraints
$this->getAcademicGradeRule('gpa') // Multiple grading systems
$this->getCurrencyRules(0, 99999)  // Fee validation with decimal precision
```

### Custom Validation Rules (Business Logic)
- **TenantExists**: Validates resources exist within tenant scope (`new TenantExists('courses', 'id', $tenantId)`)
- **PhoneNumber**: International phone validation with country restrictions (`PhoneNumber::withCountryCode(['US'])`)
- **StrongPassword**: Configurable strength levels (`StrongPassword::moderate()`)
- **AcademicGrade**: Multi-system grade validation (percentage, GPA, letter grades, international systems)

### Event-Driven Architecture with Testing
**BaseEvent**: Auto-generates metadata (timestamp, environment, event class)
```php
// All events extend BaseEvent and implement getEventName() and getEventData()
class UserActionEvent extends BaseEvent {
    public function getEventName(): string { return 'user.action'; }
}
```

**Event Testing Pattern**: Use `EventFactory` and `EventTestingHelpers` trait
```php
// In tests: $event = EventFactory::createUserActionEvent(['action' => 'delete']);
$this->assertEventDispatched(UserActionEvent::class);
$this->assertEventHasData(UserActionEvent::class, ['action' => 'delete']);
```

### Queue System & Redis Integration
**Redis-First Queue**: Configured for background job processing with health monitoring
```bash
# Test Redis connectivity and dispatch jobs
./vendor/bin/sail artisan queue:test --redis-check
./vendor/bin/sail artisan queue:test --dispatch="Message"

# Monitor queues with management script
./manage-queue.sh status    # Queue status and failed jobs
./manage-queue.sh health    # Full health check including Redis
./manage-queue.sh monitor   # Real-time queue monitoring
```

**TestQueueJob**: Example job with proper error handling and logging
- Implements `ShouldQueue` with timeout and retry configuration
- Uses traits: `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels` (separate use statements)

### Database Configuration Patterns
- **Default**: SQLite for local development (`database/database.sqlite`)
- **Docker**: MySQL via Sail container (`DB_HOST=mysql`)
- **Testing**: SQLite with `APP_KEY` configured in `phpunit.xml`
- **Sessions/Cache/Queue**: Database-driven for multi-environment compatibility

### Health Monitoring System
**HealthController** at `/health` endpoint checks:
- Database connectivity (PDO connection test)
- Cache operations (write/read test)
- Redis connectivity (ping and operations)
- Queue system status (driver configuration)
- Returns JSON with detailed status and application info

## Development Workflow Guidelines

### Adding New Features
1. **Start with migrations**: `./vendor/bin/sail artisan make:migration create_feature_table`
2. **Create models**: `./vendor/bin/sail artisan make:model Feature`
3. **Build controllers**: `./vendor/bin/sail artisan make:controller FeatureController`
4. **Define routes**: Add to `routes/web.php` or create API routes
5. **Create tests**: `./vendor/bin/sail artisan make:test FeatureTest`
6. **Run validation**: Always test health endpoint and existing functionality

### Code Quality Workflow
```bash
# MANDATORY before commits - NEVER CANCEL these
./vendor/bin/sail php vendor/bin/pint                    # 1-2 minutes
./vendor/bin/sail php artisan test                       # 5-10 minutes, timeout 20+ minutes
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
- **Database**: Use `./vendor/bin/sail artisan tinker` for interactive queries
- **Docker Issues**: `./vendor/bin/sail down && ./vendor/bin/sail up -d`
- **Asset Problems**: `./vendor/bin/sail npm run build` to rebuild frontend
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

## Performance Expectations

### Build and Test Times (When Source Code Exists)
- **Initial Docker setup**: 5-10 minutes (NEVER CANCEL - timeout 15+ minutes)
- **Composer install**: 2-3 minutes (timeout 10+ minutes)
- **Database migrations**: 2-5 minutes (timeout 10+ minutes)
- **Full test suite**: 10-15 minutes (NEVER CANCEL - timeout 30+ minutes)
- **Frontend build**: 2-5 minutes (timeout 10+ minutes)
- **Code formatting**: 1-2 minutes (timeout 5+ minutes)
- **Static analysis**: 2-3 minutes (timeout 5+ minutes)

### Current Planning Phase Operations
- **CSV analysis commands**: < 5 seconds
- **Issue searching**: < 2 seconds
- **Priority/area filtering**: < 3 seconds

### Development Server Startup (Future)
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