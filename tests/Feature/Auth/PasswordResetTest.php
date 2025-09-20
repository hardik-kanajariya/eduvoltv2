<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the password reset request page is accessible.
     */
    public function test_reset_password_link_page_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertViewIs('auth.forgot-password');
    }

    /**
     * Test that password reset link can be requested.
     */
    public function test_reset_password_link_can_be_requested(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status');
    }

    /**
     * Test that password reset link request fails for invalid email.
     */
    public function test_reset_password_link_request_fails_for_invalid_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test that the password reset form is accessible.
     */
    public function test_reset_password_form_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->get("/reset-password/{$token}?email={$user->email}");

        $response->assertStatus(200);
        $response->assertViewIs('auth.reset-password');
    }

    /**
     * Test that password can be reset with valid token.
     */
    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        // Verify password was actually changed
        $this->assertTrue(Hash::check('NewSecurePass123!', $user->fresh()->password));
    }

    /**
     * Test that password reset fails with invalid token.
     */
    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertSessionHasErrors(['email']);

        // Verify password was not changed
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /**
     * Test that password reset fails with weak password.
     */
    public function test_password_reset_fails_with_weak_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors(['password']);

        // Verify password was not changed
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /**
     * Test that password reset fails with mismatched passwords.
     */
    public function test_password_reset_fails_with_mismatched_passwords(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertSessionHasErrors(['password']);

        // Verify password was not changed
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /**
     * Test that password reset fails for non-existent user.
     */
    public function test_password_reset_fails_for_nonexistent_user(): void
    {
        $response = $this->post('/reset-password', [
            'token' => 'some-token',
            'email' => 'nonexistent@example.com',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test that password reset invalidates remember tokens.
     */
    public function test_password_reset_invalidates_remember_tokens(): void
    {
        $user = User::factory()->create([
            'remember_token' => 'old-remember-token',
        ]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        // Verify remember token was changed
        $this->assertNotEquals('old-remember-token', $user->fresh()->remember_token);
    }

    /**
     * Test password reset request validates email format.
     */
    public function test_password_reset_request_validates_email_format(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test guest users can access password reset routes.
     */
    public function test_guest_users_can_access_password_reset_routes(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);

        $response = $this->get('/reset-password/token?email=test@example.com');
        $response->assertStatus(200);
    }

    /**
     * Test authenticated users are redirected from password reset routes.
     */
    public function test_authenticated_users_are_redirected_from_password_reset_routes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/forgot-password');
        $response->assertRedirect('/dashboard');

        $response = $this->actingAs($user)->get('/reset-password/token?email=test@example.com');
        $response->assertRedirect('/dashboard');
    }
}
