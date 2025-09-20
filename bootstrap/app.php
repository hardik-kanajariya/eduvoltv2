<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register tenant resolver middleware as an alias
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantResolverMiddleware::class,
        ]);

        // Apply tenant middleware to web routes by default
        $middleware->web(append: [
            \App\Http\Middleware\TenantResolverMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withEvents(discover: [
        __DIR__ . '/../app/Events',
        __DIR__ . '/../app/Listeners',
    ])
    ->create();
