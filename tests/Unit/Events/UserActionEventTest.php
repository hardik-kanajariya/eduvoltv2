<?php

namespace Tests\Unit\Events;

use App\Events\Audit\UserActionEvent;
use PHPUnit\Framework\TestCase;

class UserActionEventTest extends TestCase
{
    public function test_user_action_event_creation(): void
    {
        $event = new UserActionEvent(
            action: 'create',
            resourceType: 'user',
            resourceId: '123',
            oldValues: [],
            newValues: ['name' => 'John Doe'],
            metadata: ['custom' => 'value']
        );

        $this->assertEquals('create', $event->action);
        $this->assertEquals('user', $event->resourceType);
        $this->assertEquals('123', $event->resourceId);
        $this->assertEquals([], $event->oldValues);
        $this->assertEquals(['name' => 'John Doe'], $event->newValues);
        $this->assertEquals('user.action', $event->getEventName());
        $this->assertNotNull($event->timestamp);
        $this->assertArrayHasKey('custom', $event->metadata);
        $this->assertEquals('value', $event->metadata['custom']);
    }

    public function test_user_action_event_data_serialization(): void
    {
        $event = new UserActionEvent(
            action: 'update',
            resourceType: 'profile',
            resourceId: '456',
            oldValues: ['email' => 'old@test.com'],
            newValues: ['email' => 'new@test.com']
        );

        $eventData = $event->getEventData();

        $this->assertEquals([
            'action' => 'update',
            'resource_type' => 'profile',
            'resource_id' => '456',
            'old_values' => ['email' => 'old@test.com'],
            'new_values' => ['email' => 'new@test.com'],
        ], $eventData);
    }

    public function test_user_action_event_to_array(): void
    {
        $event = new UserActionEvent(
            action: 'delete',
            resourceType: 'post',
            resourceId: '789'
        );

        $array = $event->toArray();

        $this->assertArrayHasKey('event_name', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals('user.action', $array['event_name']);
        $this->assertEquals('delete', $array['data']['action']);
    }
}
