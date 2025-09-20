@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600;">Create your account</h2>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <!-- First Name -->
    <div class="form-group">
        <label for="first_name">First Name</label>
        <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name">
        @error('first_name')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Last Name -->
    <div class="form-group">
        <label for="last_name">Last Name</label>
        <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
        @error('last_name')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Email Address -->
    <div class="form-group">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        @error('email')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Phone -->
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required autocomplete="tel">
        @error('phone')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Date of Birth -->
    <div class="form-group">
        <label for="date_of_birth">Date of Birth</label>
        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required>
        @error('date_of_birth')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="form-group">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        @error('password')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Confirm Password -->
    <div class="form-group">
        <label for="password_confirmation">Confirm Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        @error('password_confirmation')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Terms and Conditions -->
    <div class="form-group">
        <div class="checkbox-group">
            <input id="terms_accepted" type="checkbox" name="terms_accepted" required>
            <label for="terms_accepted" style="margin-bottom: 0; font-size: 0.875rem;">
                I agree to the <a href="#" class="link">Terms and Conditions</a>
            </label>
        </div>
        @error('terms_accepted')
            <div class="error">{{ $message }}</div>
        @enderror
    </div>

    <!-- Hidden Tenant ID (for now, we'll set a default) -->
    <input type="hidden" name="tenant_id" value="1">

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn">
            Register
        </button>
    </div>

    <div class="text-center mt-4">
        <span style="color: #6b7280; font-size: 0.875rem;">Already have an account?</span>
        <a class="link" href="{{ route('login') }}">
            Sign in
        </a>
    </div>
</form>
@endsection