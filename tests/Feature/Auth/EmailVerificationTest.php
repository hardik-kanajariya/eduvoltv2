<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test email verification notice page is accessible.
     */
    public function test_email_verification_notice_page_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-email');
    }

    /**
     * Test verified users are redirected from verification notice.
     */
    public function test_verified_users_are_redirected_from_verification_notice(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertRedirect('/dashboard');
    }

    /**
     * Test email can be verified with valid hash.
     */
    public function test_email_can_be_verified_with_valid_hash(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/dashboard?verified=1');
    }

    /**
     * Test email is not verified with invalid hash.
     */
    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertStatus(403);
    }

    /**
     * Test email verification notice shows resend option.
     */
    public function test_email_verification_notice_shows_resend_option(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('Resend Verification Email');
    }

    /**
     * Test verification email can be resent.
     */
    public function test_verification_email_can_be_resent(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->post('/email/verification-notification');

        $response->assertStatus(302);
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    /**
     * Test resend verification is rate limited.
     */
    public function test_resend_verification_is_rate_limited(): void
    {
        $user = User::factory()->unverified()->create();

        // Send multiple requests
        for ($i = 0; $i < 7; $i++) {
            $response = $this->actingAs($user)
                ->post('/email/verification-notification');
        }

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test already verified users cannot verify again.
     */
    public function test_already_verified_users_cannot_verify_again(): void
    {
        $user = User::factory()->create(); // Already verified

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/dashboard?verified=1');
    }

    /**
     * Test guest users cannot access verification routes.
     */
    public function test_guest_users_cannot_access_verification_routes(): void
    {
        $response = $this->get('/email/verify');
        $response->assertRedirect('/login');

        $response = $this->post('/email/verification-notification');
        $response->assertRedirect('/login');
    }

    /**
     * Test verification requires signed URL.
     */
    public function test_verification_requires_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        // Try to access verification route without signature
        $response = $this->actingAs($user)
            ->get("/email/verify/{$user->id}/" . sha1($user->email));

        $response->assertStatus(403);
    }

    /**
     * Test verified users cannot resend verification email.
     */
    public function test_verified_users_cannot_resend_verification_email(): void
    {
        $user = User::factory()->create(); // Already verified

        $response = $this->actingAs($user)
            ->post('/email/verification-notification');

        $response->assertRedirect('/dashboard');
    }
}
