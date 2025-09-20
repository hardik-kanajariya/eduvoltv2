<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\StrongPassword;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for StrongPassword validation rule.
 */
class StrongPasswordTest extends TestCase
{
    public function test_validates_basic_password_requirements(): void
    {
        $rule = StrongPassword::basic();
        $passes = true;

        $rule->validate('password', 'Password123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes);
    }

    public function test_rejects_short_passwords(): void
    {
        $rule = StrongPassword::basic();
        $passes = true;

        $rule->validate('password', 'Pass1', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes);
    }

    public function test_rejects_passwords_without_uppercase(): void
    {
        $rule = StrongPassword::basic();
        $passes = true;

        $rule->validate('password', 'password123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes);
    }

    public function test_rejects_passwords_without_lowercase(): void
    {
        $rule = StrongPassword::basic();
        $passes = true;

        $rule->validate('password', 'PASSWORD123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes);
    }

    public function test_rejects_passwords_without_numbers(): void
    {
        $rule = StrongPassword::basic();
        $passes = true;

        $rule->validate('password', 'Password', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes);
    }

    public function test_validates_moderate_password_with_symbols(): void
    {
        $rule = StrongPassword::moderate();
        $passes = true;

        $rule->validate('password', 'Password123!', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes);
    }

    public function test_rejects_moderate_password_without_symbols(): void
    {
        $rule = StrongPassword::moderate();
        $passes = true;

        $rule->validate('password', 'Password123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes);
    }

    public function test_rejects_common_passwords(): void
    {
        $rule = StrongPassword::basic();
        $passes = true;

        $rule->validate('password', 'Password123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes); // This should pass as it's not in common list

        $passes = true;
        $rule->validate('password', 'password', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes); // This should fail as it's common
    }

    public function test_validates_strong_password_requirements(): void
    {
        $rule = StrongPassword::strong();
        $passes = true;

        $rule->validate('password', 'MyStrongP@ssw0rd123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes);
    }

    public function test_rejects_strong_password_too_short(): void
    {
        $rule = StrongPassword::strong();
        $passes = true;

        $rule->validate('password', 'Short1!', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes);
    }

    public function test_handles_empty_values(): void
    {
        $rule = StrongPassword::basic();
        $passes = true;

        $rule->validate('password', '', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes); // Empty values should pass (handled by required rule)
    }

    public function test_custom_password_requirements(): void
    {
        $rule = StrongPassword::custom(6, false, true, true, false, false);
        $passes = true;

        // Should pass - 6 chars, lowercase, numbers, no uppercase required
        $rule->validate('password', 'test123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes);
    }
}