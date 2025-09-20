<?php

namespace App\Listeners\Audit;

use App\Events\Audit\DataChangeEvent;
use Illuminate\Support\Facades\Log;

class LogDataChange
{
    /**
     * Handle the event.
     */
    public function handle(DataChangeEvent $event): void
    {
        Log::channel('audit')->info('Data change logged', [
            'event_name' => $event->getEventName(),
            'operation' => $event->operation,
            'table' => $event->table,
            'primary_key' => $event->primaryKey,
            'changed_fields' => $event->changedFields,
            'timestamp' => $event->timestamp->toISOString(),
            'user_id' => $event->metadata['user_id'] ?? null,
            'old_data' => $event->oldData,
            'new_data' => $event->newData,
        ]);
    }
}
