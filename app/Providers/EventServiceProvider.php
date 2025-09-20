<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Event mappings will be automatically discovered
        // or can be manually defined here for specific events
    ];

    /**
     * The subscribers to register.
     *
     * @var array<int, string>
     */
    protected $subscribe = [
        // Event subscribers will be registered here
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Get the discovery paths used to automatically discover events and listeners.
     *
     * @return array<int, string>
     */
    protected function discoverEventsWithin(): array
    {
        return [
            $this->app->path('Events'),
            $this->app->path('Listeners'),
        ];
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return static::$shouldDiscoverEvents;
    }
}
