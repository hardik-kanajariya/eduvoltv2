<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the registration page is accessible.
     */
    public function test_registration_page_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    /**
     * Test that new users can register.
     */
    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/email/verify');

        // Check user was created
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));
    }

    /**
     * Test registration with invalid email.
     */
    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test registration with weak password.
     */
    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /**
     * Test registration with mismatched password confirmation.
     */
    public function test_registration_fails_with_mismatched_passwords(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass123!',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /**
     * Test registration without accepting terms.
     */
    public function test_registration_fails_without_accepting_terms(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms_accepted' => false,
            'tenant_id' => 1,
        ]);

        $response->assertSessionHasErrors(['terms_accepted']);
        $this->assertGuest();
    }

    /**
     * Test registration with duplicate email.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        // Create a user with the email first
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test registration with invalid phone number.
     */
    public function test_registration_fails_with_invalid_phone(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => 'invalid-phone',
            'date_of_birth' => '1990-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertGuest();
    }

    /**
     * Test registration with future date of birth.
     */
    public function test_registration_fails_with_future_date_of_birth(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '2030-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $response->assertSessionHasErrors(['date_of_birth']);
        $this->assertGuest();
    }

    /**
     * Test that registration automatically logs in the user.
     */
    public function test_registration_automatically_logs_in_user(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms_accepted' => true,
            'tenant_id' => 1,
        ]);

        $this->assertAuthenticated();
        $this->assertEquals('john@example.com', auth()->user()->email);
    }
}
