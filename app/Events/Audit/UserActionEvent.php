<?php

namespace App\Events\Audit;

use App\Events\BaseEvent;

class UserActionEvent extends BaseEvent
{
    /**
     * Create a new user action event.
     */
    public function __construct(
        public readonly string $action,
        public readonly string $resourceType,
        public readonly ?string $resourceId = null,
        public readonly array $oldValues = [],
        public readonly array $newValues = [],
        array $metadata = []
    ) {
        parent::__construct($metadata);
    }

    /**
     * Get the event name for logging purposes.
     */
    public function getEventName(): string
    {
        return 'user.action';
    }

    /**
     * Get the event data for serialization.
     */
    public function getEventData(): array
    {
        return [
            'action' => $this->action,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
        ];
    }
}
