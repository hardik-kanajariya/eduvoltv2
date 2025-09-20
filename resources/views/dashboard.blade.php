<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EduVoltV2') }} - Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                background-color: #f9fafb;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }
            .header {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                padding: 1.5rem;
                margin-bottom: 2rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .welcome {
                font-size: 1.5rem;
                font-weight: 600;
                color: #111827;
            }
            .user-info {
                color: #6b7280;
                font-size: 0.875rem;
            }
            .logout-btn {
                background-color: #dc2626;
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                cursor: pointer;
                font-size: 0.875rem;
                text-decoration: none;
                display: inline-block;
            }
            .logout-btn:hover {
                background-color: #b91c1c;
            }
            .card {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            .verification-notice {
                background-color: #fef3c7;
                border: 1px solid #f59e0b;
                color: #92400e;
                padding: 1rem;
                border-radius: 0.375rem;
                margin-bottom: 1.5rem;
            }
            .verification-notice a {
                color: #92400e;
                text-decoration: underline;
            }
        </style>
    @endif
</head>
<body>
    <div class="container">
        <!-- Verification Notice -->
        @if (request()->get('verified'))
            <div class="verification-notice">
                ✅ Your email has been verified successfully!
            </div>
        @elseif (!$user->hasVerifiedEmail())
            <div class="verification-notice">
                📧 Please verify your email address. <a href="{{ route('verification.notice') }}">Click here to resend verification email</a>.
            </div>
        @endif

        <div class="header">
            <div>
                <h1 class="welcome">Welcome to {{ config('app.name', 'EduVoltV2') }}</h1>
                <div class="user-info">
                    Logged in as: {{ $user->name }} ({{ $user->email }})
                    @if ($user->email_verified_at)
                        • Email verified ✅
                    @else
                        • Email not verified ❌
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">
                    Logout
                </button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top: 0; color: #111827;">Dashboard</h2>
            <p style="color: #6b7280;">
                You're successfully logged in to the EduVoltV2 platform! This is your dashboard where you can manage your account and access educational content.
            </p>
            
            @if ($user->email_verified_at)
                <p style="color: #059669;">
                    🎉 Your account is fully set up and ready to use.
                </p>
            @else
                <p style="color: #dc2626;">
                    ⚠️ Please verify your email address to access all features.
                </p>
            @endif
        </div>

        <div class="card">
            <h3 style="margin-top: 0; color: #111827;">Account Information</h3>
            <ul style="color: #6b7280; line-height: 1.6;">
                <li><strong>Name:</strong> {{ $user->name }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>Member since:</strong> {{ $user->created_at->format('F j, Y') }}</li>
                @if ($user->email_verified_at)
                    <li><strong>Email verified:</strong> {{ $user->email_verified_at->format('F j, Y g:i A') }}</li>
                @endif
            </ul>
        </div>
    </div>
</body>
</html>