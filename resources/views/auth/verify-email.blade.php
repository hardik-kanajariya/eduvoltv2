@extends('layouts.auth')

@section('title', 'Verify Email')

@section('content')
<h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600;">Verify your email address</h2>

<div style="margin-bottom: 1.5rem; text-align: center; color: #6b7280;">
    Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
</div>

@if (session('status') == 'verification-link-sent')
    <div class="success" style="margin-bottom: 1rem; text-align: center;">
        A new verification link has been sent to the email address you provided during registration.
    </div>
@endif

<div style="display: flex; gap: 1rem; justify-content: center; align-items: center; margin-top: 1.5rem;">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf

        <button type="submit" class="btn" style="width: auto; padding: 0.5rem 1rem;">
            Resend Verification Email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="link" style="background: none; border: none; cursor: pointer; padding: 0.5rem 1rem;">
            Log Out
        </button>
    </form>
</div>
@endsection