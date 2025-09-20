<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Assert;

trait EventTestingHelpers
{
    /**
     * Assert that an event was dispatched.
     */
    protected function assertEventDispatched(string $event, ?callable $callback = null): void
    {
        Event::assertDispatched($event, $callback);
    }

    /**
     * Assert that an event was not dispatched.
     */
    protected function assertEventNotDispatched(string $event, ?callable $callback = null): void
    {
        Event::assertNotDispatched($event, $callback);
    }

    /**
     * Assert that no events were dispatched.
     */
    protected function assertNoEventsDispatched(): void
    {
        Event::assertNothingDispatched();
    }

    /**
     * Assert that an event has specific data.
     */
    protected function assertEventHasData(string $event, array $expectedData): void
    {
        Event::assertDispatched($event, function ($event) use ($expectedData) {
            $eventData = $event->getEventData();

            foreach ($expectedData as $key => $value) {
                Assert::assertEquals(
                    $value,
                    data_get($eventData, $key),
                    "Event data key '{$key}' does not match expected value."
                );
            }

            return true;
        });
    }

    /**
     * Assert that an event has specific metadata.
     */
    protected function assertEventHasMetadata(string $event, array $expectedMetadata): void
    {
        Event::assertDispatched($event, function ($event) use ($expectedMetadata) {
            foreach ($expectedMetadata as $key => $value) {
                Assert::assertEquals(
                    $value,
                    data_get($event->metadata, $key),
                    "Event metadata key '{$key}' does not match expected value."
                );
            }

            return true;
        });
    }

    /**
     * Get the count of times an event was dispatched.
     */
    protected function getEventDispatchCount(string $event): int
    {
        $count = 0;
        Event::assertDispatched($event, function () use (&$count) {
            $count++;

            return false; // Continue counting
        });

        return $count;
    }

    /**
     * Fake events for testing.
     */
    protected function fakeEvents(array $eventsToFake = []): void
    {
        if (empty($eventsToFake)) {
            Event::fake();
        } else {
            Event::fake($eventsToFake);
        }
    }
}
