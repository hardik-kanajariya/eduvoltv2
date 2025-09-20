<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

abstract class BaseEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The timestamp when the event occurred.
     */
    public readonly Carbon $timestamp;

    /**
     * Metadata associated with the event.
     */
    public readonly array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(array $metadata = [])
    {
        $this->timestamp = $this->createTimestamp();
        $this->metadata = array_merge($this->getDefaultMetadata(), $metadata);
    }

    /**
     * Create timestamp safely for testing.
     */
    protected function createTimestamp(): Carbon
    {
        return new Carbon();
    }

    /**
     * Get the event name for logging purposes.
     */
    abstract public function getEventName(): string;

    /**
     * Get the event data for serialization.
     */
    abstract public function getEventData(): array;

    /**
     * Get default metadata for all events.
     */
    protected function getDefaultMetadata(): array
    {
        return [
            'event_class' => static::class,
            'application' => $this->getConfigValue('app.name', 'Laravel'),
            'environment' => $this->getConfigValue('app.env', 'production'),
            'user_id' => $this->getUserId(),
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
        ];
    }

    /**
     * Get config value safely for testing.
     */
    protected function getConfigValue(string $key, mixed $default = null): mixed
    {
        try {
            return function_exists('config') && app()->bound('config') ? config($key, $default) : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Get user ID safely for testing.
     */
    protected function getUserId(): ?int
    {
        try {
            return function_exists('auth') && app()->bound('auth') ? auth()->id() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get IP address safely for testing.
     */
    protected function getIpAddress(): ?string
    {
        try {
            return function_exists('request') && app()->bound('request') ? request()?->ip() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get user agent safely for testing.
     */
    protected function getUserAgent(): ?string
    {
        try {
            return function_exists('request') && app()->bound('request') ? request()?->userAgent() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Get the event payload for logging.
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'timestamp' => $this->timestamp->toISOString(),
            'data' => $this->getEventData(),
            'metadata' => $this->metadata,
        ];
    }
}
