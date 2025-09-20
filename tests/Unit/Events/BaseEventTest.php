<?php

namespace Tests\Unit\Events;

use App\Events\BaseEvent;
use PHPUnit\Framework\TestCase;

// Create a concrete implementation for testing
class TestEvent extends BaseEvent
{
    public function __construct(
        public readonly string $testData,
        array $metadata = []
    ) {
        parent::__construct($metadata);
    }

    public function getEventName(): string
    {
        return 'test.event';
    }

    public function getEventData(): array
    {
        return ['test_data' => $this->testData];
    }
}

class BaseEventTest extends TestCase
{
    public function test_base_event_sets_timestamp(): void
    {
        $event = new TestEvent('test');

        $this->assertNotNull($event->timestamp);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $event->timestamp);
    }

    public function test_base_event_includes_default_metadata(): void
    {
        $event = new TestEvent('test');

        $this->assertArrayHasKey('event_class', $event->metadata);
        $this->assertArrayHasKey('application', $event->metadata);
        $this->assertArrayHasKey('environment', $event->metadata);
        $this->assertEquals(TestEvent::class, $event->metadata['event_class']);
    }

    public function test_base_event_merges_custom_metadata(): void
    {
        $customMetadata = ['custom_field' => 'custom_value'];
        $event = new TestEvent('test', $customMetadata);

        $this->assertArrayHasKey('custom_field', $event->metadata);
        $this->assertEquals('custom_value', $event->metadata['custom_field']);
        $this->assertArrayHasKey('event_class', $event->metadata); // Still has defaults
    }

    public function test_base_event_to_array_structure(): void
    {
        $event = new TestEvent('test_data');
        $array = $event->toArray();

        $this->assertArrayHasKey('event_name', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertEquals('test.event', $array['event_name']);
        $this->assertEquals(['test_data' => 'test_data'], $array['data']);
    }

    public function test_base_event_broadcast_channels_default_empty(): void
    {
        $event = new TestEvent('test');

        $this->assertEquals([], $event->broadcastOn());
    }
}
