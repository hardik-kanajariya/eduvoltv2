<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handle user registration
 *
 * Provides secure user registration with:
 * - Strong password validation
 * - Email verification flow
 * - Automatic login after registration
 * - Multi-tenant support
 */
class RegisterController extends Controller
{
    /**
     * Display the registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data): User
    {
        return User::create([
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'tenant_id' => $data['tenant_id'] ?? null,
        ]);
    }
}