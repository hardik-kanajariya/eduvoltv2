@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600;">Reset your password</h2>

<div style="margin-bottom: 1.5rem; text-align: center; color: #6b7280;">
    Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
</div>

<!-- Session Status -->
@if (session('status'))
    <div class="success" style="margin-bottom: 1rem; text-align: center;">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <!-- Email Address -->
    <div class="form-group">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn">
            Email Password Reset Link
        </button>
    </div>

    <div class="text-center mt-4">
        <a class="link" href="{{ route('login') }}">
            Back to login
        </a>
    </div>
</form>
@endsection