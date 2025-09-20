<?php

namespace App\Events\Audit;

use App\Events\BaseEvent;

class SystemEvent extends BaseEvent
{
    /**
     * Create a new system event.
     */
    public function __construct(
        public readonly string $eventType,
        public readonly string $component,
        public readonly string $level = 'info',
        public readonly ?string $message = null,
        public readonly array $context = [],
        array $metadata = []
    ) {
        parent::__construct($metadata);
    }

    /**
     * Get the event name for logging purposes.
     */
    public function getEventName(): string
    {
        return 'system.event';
    }

    /**
     * Get the event data for serialization.
     */
    public function getEventData(): array
    {
        return [
            'event_type' => $this->eventType,
            'component' => $this->component,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
