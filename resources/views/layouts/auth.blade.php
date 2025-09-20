<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EduVoltV2') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /* Basic styling for forms when Vite is not available */
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                background-color: #f9fafb;
                margin: 0;
                padding: 0;
            }
            .auth-container {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .auth-card {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                padding: 2rem;
                width: 100%;
                max-width: 400px;
            }
            .auth-header {
                text-align: center;
                margin-bottom: 2rem;
            }
            .auth-title {
                font-size: 1.5rem;
                font-weight: 600;
                color: #111827;
                margin: 0;
            }
            .form-group {
                margin-bottom: 1rem;
            }
            label {
                display: block;
                font-weight: 500;
                color: #374151;
                margin-bottom: 0.5rem;
            }
            input[type="email"],
            input[type="password"],
            input[type="text"] {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #d1d5db;
                border-radius: 0.375rem;
                font-size: 1rem;
                box-sizing: border-box;
            }
            input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            .btn {
                width: 100%;
                padding: 0.75rem;
                background-color: #3b82f6;
                color: white;
                border: none;
                border-radius: 0.375rem;
                font-weight: 500;
                cursor: pointer;
                font-size: 1rem;
            }
            .btn:hover {
                background-color: #2563eb;
            }
            .btn:disabled {
                background-color: #9ca3af;
                cursor: not-allowed;
            }
            .error {
                color: #dc2626;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }
            .success {
                color: #059669;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }
            .link {
                color: #3b82f6;
                text-decoration: none;
                font-size: 0.875rem;
            }
            .link:hover {
                text-decoration: underline;
            }
            .text-center {
                text-align: center;
            }
            .mt-4 {
                margin-top: 1rem;
            }
            .checkbox-group {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .checkbox-group input[type="checkbox"] {
                width: auto;
            }
        </style>
    @endif
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title">{{ config('app.name', 'EduVoltV2') }}</h1>
            </div>

            @yield('content')
        </div>
    </div>
</body>
</html>