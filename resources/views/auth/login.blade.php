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

<form method="POST" action="{{ route('login') }}">
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
@endsection