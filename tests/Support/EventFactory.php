<?php

namespace Tests\Support;

use App\Events\Audit\DataChangeEvent;
use App\Events\Audit\SystemEvent;
use App\Events\Audit\UserActionEvent;

class EventFactory
{
    /**
     * Create a sample UserActionEvent for testing.
     */
    public static function createUserActionEvent(array $overrides = []): UserActionEvent
    {
        return new UserActionEvent(
            action: $overrides['action'] ?? 'create',
            resourceType: $overrides['resourceType'] ?? 'user',
            resourceId: $overrides['resourceId'] ?? '123',
            oldValues: $overrides['oldValues'] ?? [],
            newValues: $overrides['newValues'] ?? ['name' => 'John Doe'],
            metadata: $overrides['metadata'] ?? []
        );
    }

    /**
     * Create a sample SystemEvent for testing.
     */
    public static function createSystemEvent(array $overrides = []): SystemEvent
    {
        return new SystemEvent(
            eventType: $overrides['eventType'] ?? 'database_connection',
            component: $overrides['component'] ?? 'database',
            level: $overrides['level'] ?? 'info',
            message: $overrides['message'] ?? 'Database connection established',
            context: $overrides['context'] ?? ['host' => 'localhost'],
            metadata: $overrides['metadata'] ?? []
        );
    }

    /**
     * Create a sample DataChangeEvent for testing.
     */
    public static function createDataChangeEvent(array $overrides = []): DataChangeEvent
    {
        return new DataChangeEvent(
            operation: $overrides['operation'] ?? 'update',
            table: $overrides['table'] ?? 'users',
            primaryKey: $overrides['primaryKey'] ?? '123',
            oldData: $overrides['oldData'] ?? ['name' => 'Old Name'],
            newData: $overrides['newData'] ?? ['name' => 'New Name'],
            changedFields: $overrides['changedFields'] ?? ['name'],
            metadata: $overrides['metadata'] ?? []
        );
    }
}
