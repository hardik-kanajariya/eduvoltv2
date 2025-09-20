<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated users can access dashboard.
     */
    public function test_authenticated_users_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('user', $user);
    }

    /**
     * Test guest users cannot access dashboard.
     */
    public function test_guest_users_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test unverified users cannot access dashboard.
     */
    public function test_unverified_users_cannot_access_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/email/verify');
    }

    /**
     * Test dashboard shows user information.
     */
    public function test_dashboard_shows_user_information(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('John Doe');
        $response->assertSee('john@example.com');
        $response->assertSee('Email verified');
    }

    /**
     * Test dashboard shows verification status for unverified users.
     */
    public function test_dashboard_shows_verification_status_for_unverified_users(): void
    {
        // This test demonstrates what would happen if we removed the verified middleware
        $user = User::factory()->unverified()->create();

        // Temporarily remove the verified middleware for this test
        $response = $this->withoutMiddleware(['verified'])
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertSee('Email not verified');
        $response->assertSee('Please verify your email address');
    }

    /**
     * Test dashboard shows verified parameter in URL.
     */
    public function test_dashboard_shows_verified_parameter_in_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard?verified=1');

        $response->assertSee('Your email has been verified successfully!');
    }

    /**
     * Test dashboard logout functionality.
     */
    public function test_dashboard_logout_functionality(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * Test dashboard displays member since date.
     */
    public function test_dashboard_displays_member_since_date(): void
    {
        $user = User::factory()->create([
            'created_at' => now()->subDays(30),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Member since');
        $response->assertSee($user->created_at->format('F j, Y'));
    }

    /**
     * Test dashboard displays email verification date.
     */
    public function test_dashboard_displays_email_verification_date(): void
    {
        $verificationDate = now()->subDays(5);
        $user = User::factory()->create([
            'email_verified_at' => $verificationDate,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Email verified');
        $response->assertSee($verificationDate->format('F j, Y g:i A'));
    }
}
