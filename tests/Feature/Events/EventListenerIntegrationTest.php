<?php

namespace Tests\Feature\Events;

use App\Events\Audit\UserActionEvent;
use App\Events\Audit\SystemEvent;
use App\Events\Audit\DataChangeEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EventListenerIntegrationTest extends TestCase
{
    public function test_user_action_event_triggers_audit_log(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('User action logged', \Mockery::type('array'))
            ->once();

        $event = new UserActionEvent(
            action: 'create',
            resourceType: 'user',
            resourceId: '123',
            newValues: ['name' => 'John Doe']
        );

        Event::dispatch($event);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function test_system_event_triggers_audit_log(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->with('info', 'Database connection established', \Mockery::type('array'))
            ->once();

        $event = new SystemEvent(
            eventType: 'database_connection',
            component: 'database',
            level: 'info',
            message: 'Database connection established'
        );

        Event::dispatch($event);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function test_data_change_event_triggers_audit_log(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('Data change logged', \Mockery::type('array'))
            ->once();

        $event = new DataChangeEvent(
            operation: 'update',
            table: 'users',
            primaryKey: '123',
            oldData: ['name' => 'Old Name'],
            newData: ['name' => 'New Name'],
            changedFields: ['name']
        );

        Event::dispatch($event);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function test_event_discovery_is_working(): void
    {
        // Test that events are automatically discovered by checking the event list
        $this->artisan('event:list')
            ->expectsOutputToContain('App\Events\Audit\UserActionEvent')
            ->expectsOutputToContain('App\Events\Audit\SystemEvent')
            ->expectsOutputToContain('App\Events\Audit\DataChangeEvent')
            ->expectsOutputToContain('App\Listeners\Audit\LogUserAction')
            ->expectsOutputToContain('App\Listeners\Audit\LogSystemEvent')
            ->expectsOutputToContain('App\Listeners\Audit\LogDataChange');
    }
}
