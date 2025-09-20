# EduVoltV2 - Educational SaaS Platform

EduVoltV2 is a Laravel 12-based educational platform in early development. The foundation includes Docker Sail for development, configured for MySQL/SQLite database support, with Tailwind CSS and Vite for frontend assets.

**ALWAYS reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

## Current Repository State

**ACTIVE DEVELOPMENT**: This repository contains a functional Laravel 12 application with basic foundation setup completed.

### Current Architecture
- **Backend**: Laravel 12.x (PHP 8.2+) with standard MVC structure
- **Database**: Configured for MySQL (Docker) + SQLite (local/testing)
- **Frontend**: Tailwind CSS 4.0 + Vite build system
- **Development**: Docker Sail with MySQL container
- **Health Monitoring**: Basic health check endpoint at `/health`

### Key Implementation Patterns
- Uses **database sessions** (not file-based) for multi-environment compatibility
- **Database-first queue** configuration for background job processing
- **Dual database setup**: MySQL for Docker Sail, SQLite for testing
- **Modern Tailwind CSS 4.0** with Vite integration via `@tailwindcss/vite` plugin

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

### Key Commands by Task
```bash
# Code Quality & Standards
./vendor/bin/sail php vendor/bin/pint                    # Laravel Pint formatting (PSR-12)
./vendor/bin/sail php artisan test                       # Run PHPUnit test suite

# Database Operations
./vendor/bin/sail artisan migrate:fresh --seed           # Fresh DB with test data
./vendor/bin/sail artisan tinker                         # Interactive PHP REPL

# Asset Management  
./vendor/bin/sail npm run build                          # Production asset build
./vendor/bin/sail npm run dev                            # Development with HMR

# Background Processing
./vendor/bin/sail artisan queue:work                     # Process background jobs
```

## Current Implementation Patterns

### Database Configuration
- **Default**: SQLite for local development (`database/database.sqlite`)
- **Docker**: MySQL via Sail container (`DB_HOST=mysql`)
- **Sessions**: Database-stored sessions (table: `sessions`)
- **Queue**: Database-driven job processing (table: `jobs`)
- **Cache**: Database caching by default (table: `cache`)

### Frontend Asset Pipeline
- **Build Tool**: Vite 7.x with Laravel integration
- **CSS Framework**: Tailwind CSS 4.0 with `@tailwindcss/vite` plugin
- **Entry Points**: `resources/css/app.css`, `resources/js/app.js`
- **HMR**: Hot Module Replacement on `localhost:5173`

### Application Structure
```
app/Http/Controllers/HealthController.php  # Health monitoring endpoint
routes/web.php                            # Web routes with health check
resources/views/welcome.blade.php         # Default Laravel welcome page
database/migrations/                       # Core Laravel tables (users, cache, jobs)
```

### Key Configuration Files
- `docker-compose.yml`: Sail configuration with MySQL 8.0
- `vite.config.js`: Frontend build with Tailwind CSS integration
- `.env.example`: Template with `APP_NAME=EduVoltV2` branding

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