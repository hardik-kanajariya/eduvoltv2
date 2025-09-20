<?php

namespace App\Listeners\Audit;

use App\Events\Audit\UserActionEvent;
use Illuminate\Support\Facades\Log;

class LogUserAction
{
    /**
     * Handle the event.
     */
    public function handle(UserActionEvent $event): void
    {
        Log::channel('audit')->info('User action logged', [
            'event_name' => $event->getEventName(),
            'user_id' => $event->metadata['user_id'] ?? null,
            'action' => $event->action,
            'resource_type' => $event->resourceType,
            'resource_id' => $event->resourceId,
            'timestamp' => $event->timestamp->toISOString(),
            'ip_address' => $event->metadata['ip_address'] ?? null,
            'user_agent' => $event->metadata['user_agent'] ?? null,
        ]);
    }
}
