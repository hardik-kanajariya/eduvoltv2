<?php

namespace App\Providers;

use App\Services\PolicyRegistrationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the policy registration service
        $this->app->singleton(PolicyRegistrationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Automatically register all policies
        $this->app->make(PolicyRegistrationService::class)->registerPolicies();
    }
}
