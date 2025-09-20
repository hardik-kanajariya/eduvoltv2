<?php

namespace App\Listeners\Audit;

use App\Events\Audit\SystemEvent;
use Illuminate\Support\Facades\Log;

class LogSystemEvent
{
    /**
     * Handle the event.
     */
    public function handle(SystemEvent $event): void
    {
        Log::channel('audit')->log($event->level, $event->message ?? 'System event occurred', [
            'event_name' => $event->getEventName(),
            'event_type' => $event->eventType,
            'component' => $event->component,
            'context' => $event->context,
            'timestamp' => $event->timestamp->toISOString(),
            'environment' => $event->metadata['environment'] ?? null,
        ]);
    }
}
