<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\PhoneNumber;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for PhoneNumber validation rule.
 */
class PhoneNumberTest extends TestCase
{
    public function test_validates_basic_phone_numbers(): void
    {
        $rule = new PhoneNumber();
        $passes = true;

        $rule->validate('phone', '+1234567890', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes);
    }

    public function test_rejects_invalid_phone_numbers(): void
    {
        $rule = new PhoneNumber();
        $passes = true;

        $rule->validate('phone', '123', function () use (&$passes) {
            $passes = false;
        });

        $this->assertFalse($passes);
    }

    public function test_validates_phone_with_country_code_requirement(): void
    {
        $rule = PhoneNumber::withCountryCode();
        $passes = true;

        // Should pass with country code
        $rule->validate('phone', '+1234567890', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        // Should fail without country code
        $passes = true;
        $rule->validate('phone', '1234567890', function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);
    }

    public function test_validates_specific_countries(): void
    {
        $rule = PhoneNumber::forCountries(['US']);
        $passes = true;

        // Should pass for US number
        $rule->validate('phone', '+1234567890', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        // Should fail for non-US number
        $passes = true;
        $rule->validate('phone', '+441234567890', function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);
    }

    public function test_handles_empty_values(): void
    {
        $rule = new PhoneNumber();
        $passes = true;

        $rule->validate('phone', '', function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes); // Empty values should pass (handled by required rule)
    }

    public function test_handles_null_values(): void
    {
        $rule = new PhoneNumber();
        $passes = true;

        $rule->validate('phone', null, function () use (&$passes) {
            $passes = false;
        });

        $this->assertTrue($passes); // Null values should pass (handled by required rule)
    }
}