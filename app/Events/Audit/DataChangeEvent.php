<?php

namespace App\Events\Audit;

use App\Events\BaseEvent;

class DataChangeEvent extends BaseEvent
{
    /**
     * Create a new data change event.
     */
    public function __construct(
        public readonly string $operation,
        public readonly string $table,
        public readonly string $primaryKey,
        public readonly array $oldData = [],
        public readonly array $newData = [],
        public readonly array $changedFields = [],
        array $metadata = []
    ) {
        parent::__construct($metadata);
    }

    /**
     * Get the event name for logging purposes.
     */
    public function getEventName(): string
    {
        return 'data.change';
    }

    /**
     * Get the event data for serialization.
     */
    public function getEventData(): array
    {
        return [
            'operation' => $this->operation,
            'table' => $this->table,
            'primary_key' => $this->primaryKey,
            'old_data' => $this->oldData,
            'new_data' => $this->newData,
            'changed_fields' => $this->changedFields,
        ];
    }
}
