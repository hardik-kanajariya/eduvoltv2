<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// Domain Contracts
use App\Domain\Contracts\UserRepositoryInterface;
// Application Contracts
use App\Application\Contracts\UserServiceInterface;
// Infrastructure Implementations
use App\Infrastructure\Repositories\EloquentUserRepository;
// Application Services
use App\Application\Services\UserService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerCleanArchitectureServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register Clean Architecture service bindings
     */
    private function registerCleanArchitectureServices(): void
    {
        // Bind repository interfaces to their implementations
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        // Bind application service interfaces to their implementations
        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
        );
    }
}
