# Event/Listener Architecture Documentation

## Overview

The EduVoltV2 platform implements a robust event-driven architecture for system-wide communication and audit logging. This system is built on Laravel 12's event discovery mechanism and provides focused, single-purpose events for audit logging, notifications, and system integrations.

## Architecture Components

### Base Event Class (`App\Events\BaseEvent`)

All events extend from the `BaseEvent` abstract class which provides:
- Automatic timestamp generation
- Metadata collection (user ID, IP address, user agent, environment info)
- Serialization methods for logging
- Broadcasting capabilities

### Audit Events

#### UserActionEvent (`App\Events\Audit\UserActionEvent`)
Records user actions throughout the system.

```php
use App\Events\Audit\UserActionEvent;

// Example: Log user creation
Event::dispatch(new UserActionEvent(
    action: 'create',
    resourceType: 'user',
    resourceId: '123',
    oldValues: [],
    newValues: ['name' => 'John Doe', 'email' => 'john@example.com']
));
```

#### SystemEvent (`App\Events\Audit\SystemEvent`)
Records system-level events and status changes.

```php
use App\Events\Audit\SystemEvent;

// Example: Log database connection
Event::dispatch(new SystemEvent(
    eventType: 'database_connection',
    component: 'database',
    level: 'info',
    message: 'Database connection established',
    context: ['host' => 'localhost', 'database' => 'eduvolt']
));
```

#### DataChangeEvent (`App\Events\Audit\DataChangeEvent`)
Records data modifications with field-level tracking.

```php
use App\Events\Audit\DataChangeEvent;

// Example: Log data change
Event::dispatch(new DataChangeEvent(
    operation: 'update',
    table: 'users',
    primaryKey: '123',
    oldData: ['name' => 'Old Name'],
    newData: ['name' => 'New Name'],
    changedFields: ['name']
));
```

### Event Listeners

#### Audit Logging Listeners
- `LogUserAction` - Logs user actions to audit channel
- `LogSystemEvent` - Logs system events to audit channel  
- `LogDataChange` - Logs data changes to audit channel

All audit logs are written to the `audit` log channel (configurable in `config/logging.php`).

## Event Discovery

The system uses Laravel 12's automatic event discovery feature. Events and listeners are automatically registered when placed in:
- `app/Events/` directory
- `app/Listeners/` directory

To view all discovered events:
```bash
php artisan event:list
```

## Testing Support

### EventTestingHelpers Trait

Provides convenient methods for testing events:

```php
use Tests\Support\EventTestingHelpers;

class MyTest extends TestCase
{
    use EventTestingHelpers;
    
    public function test_event_is_dispatched()
    {
        $this->fakeEvents();
        
        // Your code that dispatches events
        
        $this->assertEventDispatched(UserActionEvent::class);
        $this->assertEventHasData(UserActionEvent::class, [
            'action' => 'create'
        ]);
    }
}
```

### EventFactory

Creates test event instances:

```php
use Tests\Support\EventFactory;

$event = EventFactory::createUserActionEvent([
    'action' => 'delete',
    'resourceType' => 'post'
]);
```

## Configuration

### Logging Configuration

The audit log channel is configured in `config/logging.php`:

```php
'audit' => [
    'driver' => 'daily',
    'path' => storage_path('logs/audit.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_AUDIT_DAYS', 90),
    'replace_placeholders' => true,
],
```

### Event Discovery

Event discovery is configured in `bootstrap/app.php`:

```php
->withEvents(discover: [
    __DIR__ . '/../app/Events',
    __DIR__ . '/../app/Listeners',
])
```

## Usage Examples

### User Registration Audit
```php
// In your user registration controller
Event::dispatch(new UserActionEvent(
    action: 'register',
    resourceType: 'user',
    resourceId: $user->id,
    newValues: $user->only(['name', 'email'])
));
```

### System Status Monitoring
```php
// In your health check or monitoring code
Event::dispatch(new SystemEvent(
    eventType: 'health_check',
    component: 'application',
    level: 'info',
    message: 'Application health check completed',
    context: ['status' => 'healthy', 'response_time' => 150]
));
```

### Data Auditing
```php
// In your model observers or form handlers
Event::dispatch(new DataChangeEvent(
    operation: 'update',
    table: 'students',
    primaryKey: $student->id,
    oldData: $student->getOriginal(),
    newData: $student->getAttributes(),
    changedFields: array_keys($student->getDirty())
));
```

## Best Practices

1. **Keep events focused** - Each event should represent a single, well-defined occurrence
2. **Use appropriate event types** - Choose UserActionEvent for user-initiated actions, SystemEvent for system operations, DataChangeEvent for data modifications
3. **Include relevant context** - Add metadata that will be useful for auditing and debugging
4. **Test event dispatching** - Use the provided testing utilities to verify events are dispatched correctly
5. **Monitor audit logs** - Regularly review audit logs for security and compliance purposes

## Files Structure

```
app/
├── Events/
│   ├── BaseEvent.php
│   └── Audit/
│       ├── UserActionEvent.php
│       ├── SystemEvent.php
│       └── DataChangeEvent.php
├── Listeners/
│   └── Audit/
│       ├── LogUserAction.php
│       ├── LogSystemEvent.php
│       └── LogDataChange.php
└── Providers/
    └── EventServiceProvider.php

tests/
├── Support/
│   ├── EventTestingHelpers.php
│   └── EventFactory.php
├── Unit/Events/
│   ├── BaseEventTest.php
│   └── UserActionEventTest.php
└── Feature/Events/
    ├── EventDispatchingTest.php
    └── EventListenerIntegrationTest.php
```

## Commands

- `php artisan event:list` - List all registered events and listeners
- `php artisan test tests/Feature/Events/` - Run event-related tests
- `php artisan test tests/Unit/Events/` - Run event unit tests