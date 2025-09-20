@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600;">Sign in to your account</h2>

<!-- Session Status -->
@if (session('status'))
<div class="success" style="margin-bottom: 1rem; text-align: center;">
    {{ session('status') }}
</div>
@endif

<!-- Demo Accounts Section -->
@if($demoAccountsEnabled && count($demoAccounts) > 0)
<div class="demo-accounts-section" style="margin-bottom: 1.5rem; padding: 1rem; background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 0.375rem;">
    <h3 style="margin-bottom: 0.75rem; font-size: 0.875rem; font-weight: 600; color: #374151;">
        🚀 Quick Demo Access
    </h3>
    <p style="margin-bottom: 1rem; font-size: 0.75rem; color: #6b7280;">
        Select a demo account to instantly test the system:
    </p>
    <div class="demo-accounts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
        @foreach($demoAccounts as $account)
        <button type="button"
            class="demo-account-btn"
            data-email="{{ $account['email'] }}"
            data-password="{{ $account['password'] }}"
            style="padding: 0.5rem; text-align: left; background-color: white; border: 1px solid #d1d5db; border-radius: 0.25rem; cursor: pointer; transition: all 0.2s; font-size: 0.75rem;">
            <div style="font-weight: 600; color: #374151;">{{ $account['name'] }}</div>
            <div style="color: #6b7280; font-size: 0.625rem;">{{ ucfirst($account['role']) }}</div>
            <div style="color: #9ca3af; font-size: 0.625rem;">{{ $account['email'] }}</div>
        </button>
        @endforeach
    </div>
    <p style="margin-top: 0.75rem; font-size: 0.625rem; color: #9ca3af; text-align: center;">
        Click any account above to auto-fill the login form
    </p>
</div>
@endif

<form method="POST" action="{{ route('login') }}" id="loginForm">
    @csrf

    <!-- Email Address -->
    <div class="form-group">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        @error('email')
        <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="form-group">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        @error('password')
        <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Remember Me -->
    <div class="form-group">
        <div class="checkbox-group">
            <input id="remember_me" type="checkbox" name="remember">
            <label for="remember_me" style="margin-bottom: 0;">Remember me</label>
        </div>
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn">
            Log in
        </button>
    </div>

    <div class="text-center mt-4">
        @if (Route::has('password.request'))
        <a class="link" href="{{ route('password.request') }}">
            Forgot your password?
        </a>
        @endif
    </div>

    <div class="text-center mt-4">
        <span style="color: #6b7280; font-size: 0.875rem;">Don't have an account?</span>
        <a class="link" href="{{ route('register') }}">
            Sign up
        </a>
    </div>
</form>

@if($demoAccountsEnabled && count($demoAccounts) > 0)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects and click handlers for demo account buttons
        const demoButtons = document.querySelectorAll('.demo-account-btn');

        demoButtons.forEach(button => {
            // Add hover effect
            button.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f3f4f6';
                this.style.borderColor = '#9ca3af';
                this.style.transform = 'translateY(-1px)';
            });

            button.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'white';
                this.style.borderColor = '#d1d5db';
                this.style.transform = 'translateY(0)';
            });

            // Add click handler to fill form
            button.addEventListener('click', function() {
                const email = this.getAttribute('data-email');
                const password = this.getAttribute('data-password');

                // Fill the form fields
                document.getElementById('email').value = email;
                document.getElementById('password').value = password;

                // Add visual feedback
                this.style.backgroundColor = '#dbeafe';
                this.style.borderColor = '#3b82f6';

                // Flash effect to show selection
                setTimeout(() => {
                    this.style.backgroundColor = '#f3f4f6';
                    this.style.borderColor = '#9ca3af';
                }, 300);

                // Optional: Auto-focus on submit button
                document.querySelector('button[type="submit"]').focus();
            });
        });
    });
</script>
@endif
@endsection