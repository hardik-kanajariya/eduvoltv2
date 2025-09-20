<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app' => config('app.name'),
        'version' => config('app.version', 'Laravel 12'),
        'database' => [
            'connection' => config('database.default'),
            'status' => 'connected'
        ]
    ]);
});
