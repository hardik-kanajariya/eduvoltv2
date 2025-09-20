<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handle user authentication (login/logout)
 *
 * Provides secure login functionality with:
 * - Rate limiting for brute force protection
 * - Secure session management
 * - Remember me functionality
 * - Multi-tenant support
 */
class LoginController extends Controller
{
    /**
     * Display the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $this->checkTooManyFailedAttempts($request);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $this->clearLoginAttempts($request);

            return $this->authenticated($request, Auth::user())
                ?: redirect()->intended(route('dashboard'));
        }

        $this->incrementLoginAttempts($request);

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(Request $request, mixed $user): ?RedirectResponse
    {
        // Hook for post-authentication logic
        return null;
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Determine if the user has too many failed login attempts.
     *
     * @throws ValidationException
     */
    protected function checkTooManyFailedAttempts(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Increment the login attempts for the user.
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), 60 * 5); // 5 minutes
    }

    /**
     * Clear the login attempts for the user.
     */
    protected function clearLoginAttempts(Request $request): void
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    /**
     * Get the throttle key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        return strtolower($request->input('email')) . '|' . $request->ip();
    }
}
