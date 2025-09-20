# Multi-Tenant Database Schema Documentation

## Overview
EduVoltV2 implements a single-database, multi-tenant architecture using a `tenant_id` column strategy for data isolation.

## Schema Design Decisions

### 1. Tenant Identification
- **Primary Key**: `id` (unsigned big integer)
- **Domain-based**: Each tenant has a unique domain for identification
- **Settings**: JSON column for tenant-specific configuration

### 2. Tenant ID Strategy
- **Column Type**: `unsigned_big_integer` for scalability
- **Placement**: Added to all user-facing tables
- **Indexing**: All tenant_id columns are indexed for query performance

### 3. Tables with tenant_id

#### Core Tables
- `users`: User accounts scoped to tenant
  - Unique constraint on `[email, tenant_id]` (allows same email across tenants)
- `sessions`: User sessions linked to tenant context
- `jobs`: Background jobs can be tenant-specific (optional)

#### System Tables (No tenant_id)
- `tenants`: Master tenant registry
- `cache`: Shared cache (tenant context handled in application)
- `password_reset_tokens`: Handled via email uniqueness

### 4. Query Scoping
- **Global Scope**: `TenantScope` automatically filters queries by current tenant
- **Trait**: `HasTenant` provides tenant-aware functionality
- **Manual Override**: `withoutTenant()` and `forTenant()` scopes available

## Implementation Details

### Automatic Tenant Assignment
```php
// Models using HasTenant trait automatically get tenant_id on creation
$user = User::create([...]);  // tenant_id set from current context
```

### Manual Tenant Context
```php
// Set tenant context
TenantServiceProvider::setTenant($tenantId);

// All queries now scoped to this tenant
$users = User::all();  // Only returns users for current tenant
```

### Testing with Multiple Tenants
```php
// Create test tenant
$tenant = Tenant::factory()->create();

// Set context
TenantServiceProvider::setTenant($tenant->id);

// Create tenant-specific data
$user = User::factory()->create();  // Automatically gets tenant_id
```

## Migration Strategy

### New Installations
- All tables created with tenant_id from start
- No migration needed

### Existing Installations (Future)
- Migration will add tenant_id columns
- Default tenant created for existing data
- Data migration assigns existing records to default tenant

## Performance Considerations

1. **Indexing**: All tenant_id columns have database indexes
2. **Query Optimization**: Tenant filtering happens at database level
3. **Connection Pooling**: Single database reduces connection overhead
4. **Caching**: Application-level tenant context for cache keys

## Security Considerations

1. **Automatic Scoping**: Global scope prevents cross-tenant data leaks
2. **Explicit Overrides**: `withoutTenant()` requires intentional use
3. **Context Validation**: Tenant context validated via middleware
4. **Database Constraints**: Foreign key constraints respect tenant boundaries

## Testing Migration Rollback

```bash
# Test forward migration
php artisan migrate

# Test rollback
php artisan migrate:rollback

# Test fresh migration
php artisan migrate:fresh --seed
```