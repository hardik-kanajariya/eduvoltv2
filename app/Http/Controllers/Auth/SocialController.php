<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     */
    public function redirectToProvider(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the provider.
     */
    public function handleProviderCallback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['social' => 'Unable to authenticate with ' . ucfirst($provider) . '. Please try again.']);
        }

        // Check if user already exists with this email
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update social provider info if not already set
            $this->updateUserSocialInfo($user, $provider, $socialUser);
        } else {
            // Create new user
            $user = $this->createUserFromSocial($provider, $socialUser);
        }

        // Log in the user
        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }

    /**
     * Validate the social provider.
     */
    private function validateProvider(string $provider): void
    {
        $allowedProviders = ['google', 'microsoft'];

        if (!in_array($provider, $allowedProviders)) {
            abort(404, 'Provider not supported');
        }
    }

    /**
     * Create a new user from social provider data.
     */
    private function createUserFromSocial(string $provider, $socialUser): User
    {
        $user = User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(32)), // Random password for social users
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_token' => $socialUser->token,
            'avatar' => $socialUser->getAvatar(),
        ]);

        // Assign default role (Student) to new social users
        $defaultRole = Role::where('name', 'Student')->first();
        if ($defaultRole) {
            $user->assignRole($defaultRole);
        }

        return $user;
    }

    /**
     * Update existing user's social provider information.
     */
    private function updateUserSocialInfo(User $user, string $provider, $socialUser): void
    {
        // Only update if provider info is not already set
        if (empty($user->provider) || empty($user->provider_id)) {
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        }
    }
}
