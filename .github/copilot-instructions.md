# EduVoltV2 - Multi-Tenant Educational SaaS Platform

EduVoltV2 is a Laravel 12-based multi-tenant SaaS educational platform designed for institutions, students, and administrators. The platform uses Docker Sail for development, MySQL for data persistence, and implements Clean Architecture principles.

**ALWAYS reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

## Current Repository State

**CRITICAL**: This repository is currently in the planning phase. The `github_issues.csv` file contains 4000+ detailed specifications for the platform but **NO SOURCE CODE EXISTS YET**. 

When working in this repository:
- Reference `github_issues.csv` for detailed feature specifications and requirements
- The codebase will be implemented using Laravel 12 + Docker Sail + MySQL stack
- All issues follow multi-tenant SaaS architecture patterns
- Priority levels: P1 (MVP), P2 (Pilot), P3 (GA)

### Working with Current Planning State
```bash
# View total number of issues
wc -l github_issues.csv

# Search for specific functionality
grep -i "authentication" github_issues.csv
grep -i "dashboard" github_issues.csv
grep -i "billing" github_issues.csv

# Filter by priority and status
grep "priority:P1" github_issues.csv | grep "status:todo"
grep "priority:P2" github_issues.csv | grep "status:todo"

# Find issues by area/component
grep "area:foundation" github_issues.csv
grep "area:auth" github_issues.csv
grep "area:billing" github_issues.csv
```

## Technology Stack (Planned)

Based on the issue specifications:
- **Backend**: Laravel 12 (PHP 8.3+)
- **Database**: MySQL 8.0+ with tenant_id per-row multi-tenancy
- **Development Environment**: Docker Sail
- **Code Standards**: PSR-12 compliance
- **Testing**: PHPUnit, Laravel testing utilities
- **Architecture**: Clean Architecture with Domain/Application/Infrastructure layers

## Implementation Phase Instructions

### When Ready to Begin Implementation

**Step 1: Verify Prerequisites**
```bash
# Confirm environment setup
php --version          # Should be PHP 8.3+
composer --version     # Should be Composer 2.8+
docker --version       # Should be Docker 20.10+
```

**Step 2: Initialize Laravel 12 Project**
```bash
# Create fresh Laravel 12 project - NEVER CANCEL: Takes 5-10 minutes
# Set timeout to 15+ minutes
composer create-project laravel/laravel eduvolt-app "12.*"

# Move into project directory  
cd eduvolt-app

# Configure Docker Sail - NEVER CANCEL: Takes 3-5 minutes
# Set timeout to 10+ minutes
php artisan sail:install --with=mysql,redis,meilisearch

# Copy environment file and configure
cp .env.example .env
php artisan key:generate
```

**Step 3: Multi-Tenant Foundation Setup**
```bash
# Install multi-tenancy package
composer require stancl/tenancy

# Publish tenancy configuration
php artisan vendor:publish --provider="Stancl\Tenancy\TenancyServiceProvider"

# Create tenant migrations based on specifications
php artisan make:migration add_tenant_id_to_all_tables
```

### Pre-Implementation Validation
```bash
# Verify current repository state
test -f github_issues.csv && echo "Planning repository confirmed" || echo "ERROR: Missing issue specifications"

# Count total specifications
echo "Total issues specified: $(wc -l < github_issues.csv)"

# Verify key architectural components are specified
grep -q "Clean Architecture" github_issues.csv && echo "✓ Architecture specified" || echo "✗ Architecture missing"
grep -q "Docker Sail" github_issues.csv && echo "✓ Docker setup specified" || echo "✗ Docker setup missing"
grep -q "tenant_id" github_issues.csv && echo "✓ Multi-tenancy specified" || echo "✗ Multi-tenancy missing"
```

## Project Setup (For Future Implementation)

### Prerequisites Installation
```bash
# Install Docker (Ubuntu/Debian)
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
sudo usermod -aG docker $USER

# Install PHP 8.3+ and Composer
apt-get update
apt-get install -y php8.3 php8.3-cli php8.3-mbstring php8.3-xml php8.3-mysql php8.3-curl
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### Project Setup (When Source Code Exists)
```bash
# Clone and setup project
composer install --timeout=300
cp .env.example .env
php artisan key:generate

# Start Docker Sail - NEVER CANCEL: Takes 5-10 minutes for initial setup
# Set timeout to 15+ minutes for initial container builds
./vendor/bin/sail up -d

# Database setup - NEVER CANCEL: May take 2-3 minutes
# Set timeout to 10+ minutes
./vendor/bin/sail artisan migrate:fresh --seed

# Install Node.js dependencies for frontend assets
./vendor/bin/sail npm install

# Build frontend assets - NEVER CANCEL: Takes 2-5 minutes
# Set timeout to 10+ minutes
./vendor/bin/sail npm run build
```

### Development Workflow

#### Running the Application
```bash
# Start development environment
./vendor/bin/sail up -d

# View application (when implemented)
# Frontend: http://localhost
# Admin Panel: http://localhost/admin
# API Documentation: http://localhost/api/documentation
```

#### Code Quality and Standards
```bash
# Format code with Laravel Pint - NEVER CANCEL: Takes 1-2 minutes
./vendor/bin/sail php vendor/bin/pint

# Run PHP CS Fixer for PSR-12 compliance
./vendor/bin/sail php vendor/bin/php-cs-fixer fix

# Static analysis with Larastan/PHPStan
./vendor/bin/sail php vendor/bin/phpstan analyse
```

#### Testing
```bash
# Run full test suite - NEVER CANCEL: Takes 10-15 minutes
# Set timeout to 30+ minutes for comprehensive testing
./vendor/bin/sail php artisan test

# Run specific test types
./vendor/bin/sail php artisan test --testsuite=Feature
./vendor/bin/sail php artisan test --testsuite=Unit

# Run tests with coverage - NEVER CANCEL: Takes 15-20 minutes
# Set timeout to 40+ minutes
./vendor/bin/sail php artisan test --coverage
```

#### Database Operations
```bash
# Fresh migration with seeding - NEVER CANCEL: Takes 2-5 minutes
./vendor/bin/sail artisan migrate:fresh --seed

# Create new migration
./vendor/bin/sail artisan make:migration create_example_table

# Run specific seeders for multi-tenant data
./vendor/bin/sail artisan db:seed --class=TenantSeeder
```

## Validation Scenarios

### CRITICAL: Always test these scenarios after making changes

#### 1. Multi-Tenant Data Isolation
```bash
# Test tenant data separation
./vendor/bin/sail php artisan test --filter=TenantIsolationTest

# Verify tenant_id scoping in queries
./vendor/bin/sail php artisan tinker
# In tinker: User::where('tenant_id', 1)->get()
```

#### 2. Authentication and Authorization
```bash
# Test user authentication flows
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Test role-based access control
./vendor/bin/sail php artisan test --filter=RolePermissionTest
```

#### 3. API Functionality
```bash
# Test API endpoints
curl -X GET http://localhost/api/health
curl -X GET http://localhost/api/v1/users -H "Authorization: Bearer TOKEN"

# API documentation check
curl -X GET http://localhost/api/documentation
```

#### 4. Database Performance
```bash
# Check query performance and N+1 issues
./vendor/bin/sail php artisan telescope:clear
# Make API calls, then check telescope dashboard
```

## File Structure (When Implemented)

Based on Clean Architecture specifications in issues:
```
app/
├── Domain/           # Business logic and entities
├── Application/      # Use cases and services  
├── Infrastructure/   # External concerns (DB, APIs)
├── Http/            # Controllers and middleware
└── Models/          # Eloquent models with tenant scoping

database/
├── migrations/      # All tables include tenant_id
├── seeders/         # Multi-tenant test data
└── factories/       # Model factories for testing

tests/
├── Feature/         # Integration tests
├── Unit/           # Unit tests
└── Tenant/         # Tenant isolation tests
```

## Common Development Tasks

### Creating New Features
1. **ALWAYS** check `github_issues.csv` for existing specifications
2. Create migration with tenant_id column: `./vendor/bin/sail artisan make:migration`
3. Create model with tenant scoping: `./vendor/bin/sail artisan make:model`
4. Create controller: `./vendor/bin/sail artisan make:controller`
5. Add routes with tenant middleware
6. Create tests for tenant isolation
7. Run full validation scenarios before committing

### Before Committing Changes
```bash
# MANDATORY quality checks - NEVER CANCEL any of these
./vendor/bin/sail php vendor/bin/pint                    # 1-2 minutes
./vendor/bin/sail php vendor/bin/phpstan analyse         # 2-3 minutes  
./vendor/bin/sail php artisan test                       # 10-15 minutes, timeout 30+ minutes
```

### Debugging Common Issues
- **Tenant data bleeding**: Check `tenant_id` scoping in models and queries
- **Permission errors**: Verify role-based access control implementation
- **API authentication**: Check JWT token generation and validation
- **Database connections**: Verify Docker Sail MySQL container status

## CI/CD Pipeline (When Implemented)

The GitHub Actions workflow will include:
- PSR-12 code style checking
- PHPStan static analysis
- PHPUnit test execution with coverage
- Multi-tenant isolation testing
- Docker container security scanning

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