<?php

namespace Tests\Feature\Events;

use App\Events\Audit\UserActionEvent;
use App\Events\Audit\SystemEvent;
use App\Events\Audit\DataChangeEvent;
use Illuminate\Support\Facades\Event;
use Tests\Support\EventTestingHelpers;
use Tests\Support\EventFactory;
use Tests\TestCase;

class EventDispatchingTest extends TestCase
{
    use EventTestingHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeEvents();
    }

    public function test_user_action_event_can_be_dispatched(): void
    {
        $event = EventFactory::createUserActionEvent([
            'action' => 'create',
            'resourceType' => 'user',
            'resourceId' => '123'
        ]);

        Event::dispatch($event);

        $this->assertEventDispatched(UserActionEvent::class);
        $this->assertEventHasData(UserActionEvent::class, [
            'action' => 'create',
            'resource_type' => 'user',
            'resource_id' => '123'
        ]);
    }

    public function test_system_event_can_be_dispatched(): void
    {
        $event = EventFactory::createSystemEvent([
            'eventType' => 'database_connection',
            'component' => 'database',
            'level' => 'info'
        ]);

        Event::dispatch($event);

        $this->assertEventDispatched(SystemEvent::class);
        $this->assertEventHasData(SystemEvent::class, [
            'event_type' => 'database_connection',
            'component' => 'database',
            'level' => 'info'
        ]);
    }

    public function test_data_change_event_can_be_dispatched(): void
    {
        $event = EventFactory::createDataChangeEvent([
            'operation' => 'update',
            'table' => 'users',
            'primaryKey' => '123'
        ]);

        Event::dispatch($event);

        $this->assertEventDispatched(DataChangeEvent::class);
        $this->assertEventHasData(DataChangeEvent::class, [
            'operation' => 'update',
            'table' => 'users',
            'primary_key' => '123'
        ]);
    }

    public function test_multiple_events_can_be_dispatched(): void
    {
        $userEvent = EventFactory::createUserActionEvent();
        $systemEvent = EventFactory::createSystemEvent();

        Event::dispatch($userEvent);
        Event::dispatch($systemEvent);

        $this->assertEventDispatched(UserActionEvent::class);
        $this->assertEventDispatched(SystemEvent::class);
    }

    public function test_event_metadata_is_properly_set(): void
    {
        $event = EventFactory::createUserActionEvent([
            'metadata' => ['custom_field' => 'custom_value']
        ]);

        Event::dispatch($event);

        $this->assertEventHasMetadata(UserActionEvent::class, [
            'custom_field' => 'custom_value'
        ]);
    }
}
