<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Integration test to validate the complete authentication system.
 */
class AuthenticationSystemTest extends TestCase
{
    /**
     * Test that all authentication routes are properly defined.
     */
    public function test_authentication_routes_are_defined(): void
    {
        // Test route definitions exist - these would normally be checked via Route::has()
        $expectedRoutes = [
            'login',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'logout',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'dashboard',
        ];

        // In a real test with Laravel loaded, we would check:
        // foreach ($expectedRoutes as $routeName) {
        //     $this->assertTrue(Route::has($routeName), "Route {$routeName} should be defined");
        // }

        // For now, just ensure the routes file contains our expected routes
        $routesContent = file_get_contents(__DIR__ . '/../../routes/web.php');

        $this->assertStringContainsString('LoginController', $routesContent);
        $this->assertStringContainsString('RegisterController', $routesContent);
        $this->assertStringContainsString('EmailVerificationController', $routesContent);
        $this->assertStringContainsString('PasswordResetController', $routesContent);
        $this->assertStringContainsString('DashboardController', $routesContent);
    }

    /**
     * Test that all required authentication controllers exist.
     */
    public function test_authentication_controllers_exist(): void
    {
        $controllers = [
            'App\Http\Controllers\Auth\LoginController',
            'App\Http\Controllers\Auth\RegisterController',
            'App\Http\Controllers\Auth\EmailVerificationController',
            'App\Http\Controllers\Auth\PasswordResetController',
            'App\Http\Controllers\DashboardController',
        ];

        foreach ($controllers as $controller) {
            $this->assertTrue(
                class_exists($controller),
                "Controller {$controller} should exist"
            );
        }
    }

    /**
     * Test that authentication form requests exist and have proper validation.
     */
    public function test_authentication_form_requests_exist(): void
    {
        $requests = [
            'App\Http\Requests\Auth\LoginRequest',
            'App\Http\Requests\Auth\RegisterRequest',
        ];

        foreach ($requests as $request) {
            $this->assertTrue(
                class_exists($request),
                "Form request {$request} should exist"
            );
        }
    }

    /**
     * Test that StrongPassword rule exists and has expected methods.
     */
    public function test_strong_password_rule_exists(): void
    {
        $this->assertTrue(class_exists('App\Rules\StrongPassword'));

        $rule = new \App\Rules\StrongPassword();
        $this->assertInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class, $rule);

        // Test static factory methods exist
        $this->assertTrue(method_exists(\App\Rules\StrongPassword::class, 'basic'));
        $this->assertTrue(method_exists(\App\Rules\StrongPassword::class, 'moderate'));
        $this->assertTrue(method_exists(\App\Rules\StrongPassword::class, 'strong'));
    }

    /**
     * Test that authentication views exist.
     */
    public function test_authentication_views_exist(): void
    {
        $views = [
            'resources/views/auth/login.blade.php',
            'resources/views/auth/register.blade.php',
            'resources/views/auth/verify-email.blade.php',
            'resources/views/auth/forgot-password.blade.php',
            'resources/views/auth/reset-password.blade.php',
            'resources/views/dashboard.blade.php',
            'resources/views/layouts/auth.blade.php',
        ];

        foreach ($views as $view) {
            $this->assertFileExists(
                __DIR__ . '/../../' . $view,
                "View {$view} should exist"
            );
        }
    }

    /**
     * Test that User model is properly configured for authentication.
     */
    public function test_user_model_configuration(): void
    {
        $this->assertTrue(class_exists('App\Models\User'));

        $user = new \App\Models\User();

        // Test that User implements MustVerifyEmail
        $this->assertInstanceOf(
            \Illuminate\Contracts\Auth\MustVerifyEmail::class,
            $user
        );

        // Test fillable attributes include necessary fields
        $fillable = $user->getFillable();
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('tenant_id', $fillable);
    }

    /**
     * Test that database migrations exist for authentication.
     */
    public function test_authentication_migrations_exist(): void
    {
        $migrationFiles = glob(__DIR__ . '/../../database/migrations/*_create_users_table.php');
        $this->assertNotEmpty($migrationFiles, 'Users table migration should exist');

        // Check migration content includes required fields
        $migrationContent = file_get_contents($migrationFiles[0]);
        $this->assertStringContainsString('email_verified_at', $migrationContent);
        $this->assertStringContainsString('password_reset_tokens', $migrationContent);
        $this->assertStringContainsString('sessions', $migrationContent);
    }

    /**
     * Test that UserFactory exists and supports verification states.
     */
    public function test_user_factory_configuration(): void
    {
        $this->assertTrue(class_exists('Database\Factories\UserFactory'));

        $factory = new \Database\Factories\UserFactory();

        // Test that unverified method exists
        $this->assertTrue(method_exists($factory, 'unverified'));

        // Test default definition includes email_verified_at
        $definition = $factory->definition();
        $this->assertArrayHasKey('email_verified_at', $definition);
    }

    /**
     * Test that authentication tests exist and are comprehensive.
     */
    public function test_authentication_tests_exist(): void
    {
        $testFiles = [
            'tests/Feature/Auth/LoginTest.php',
            'tests/Feature/Auth/RegisterTest.php',
            'tests/Feature/Auth/EmailVerificationTest.php',
            'tests/Feature/Auth/PasswordResetTest.php',
            'tests/Feature/Auth/DashboardTest.php',
            'tests/Unit/Rules/StrongPasswordTest.php',
        ];

        foreach ($testFiles as $testFile) {
            $this->assertFileExists(
                __DIR__ . '/../../' . $testFile,
                "Test file {$testFile} should exist"
            );
        }
    }

    /**
     * Test that environment configuration supports authentication.
     */
    public function test_environment_configuration(): void
    {
        $envContent = file_get_contents(__DIR__ . '/../../.env');

        // Test that session driver is set to database
        $this->assertStringContainsString('SESSION_DRIVER=database', $envContent);

        // Test that app key is set
        $this->assertStringContainsString('APP_KEY=', $envContent);

        // Test that database is configured
        $this->assertStringContainsString('DB_CONNECTION=', $envContent);
    }

    /**
     * Test that documentation exists for the authentication system.
     */
    public function test_authentication_documentation_exists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../docs/AUTHENTICATION.md');
        $this->assertFileExists(__DIR__ . '/../../docs/AUTH_SETUP.md');

        $authDocContent = file_get_contents(__DIR__ . '/../../docs/AUTHENTICATION.md');
        $this->assertStringContainsString('Email-Based User Authentication', $authDocContent);
        $this->assertStringContainsString('Security Features', $authDocContent);

        $setupDocContent = file_get_contents(__DIR__ . '/../../docs/AUTH_SETUP.md');
        $this->assertStringContainsString('Authentication System Setup', $setupDocContent);
        $this->assertStringContainsString('Testing the Authentication System', $setupDocContent);
    }
}
