@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600;">Reset your password</h2>

<form method="POST" action="{{ route('password.update') }}">
    @csrf

    <!-- Password Reset Token -->
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <!-- Email Address -->
    <div class="form-group">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
        @error('email')
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

    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn">
            Reset Password
        </button>
    </div>
</form>
@endsection