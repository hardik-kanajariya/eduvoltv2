<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates phone number format with international support.
 *
 * Supports various international phone number formats and can be configured
 * for specific country requirements.
 */
class PhoneNumber implements ValidationRule
{
    private array $allowedCountries;
    private bool $requireCountryCode;

    public function __construct(array $allowedCountries = [], bool $requireCountryCode = false)
    {
        $this->allowedCountries = $allowedCountries;
        $this->requireCountryCode = $requireCountryCode;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $phoneNumber = $this->sanitizePhoneNumber($value);

        if (!$this->isValidFormat($phoneNumber)) {
            $fail('The :attribute must be a valid phone number.');
            return;
        }

        if ($this->requireCountryCode && !$this->hasCountryCode($phoneNumber)) {
            $fail('The :attribute must include a country code (e.g., +1, +44).');
            return;
        }

        if (!empty($this->allowedCountries) && !$this->isAllowedCountry($phoneNumber)) {
            $countries = implode(', ', $this->allowedCountries);
            $fail("The :attribute must be from one of the following countries: {$countries}.");
        }
    }

    /**
     * Remove non-digit characters except + at the beginning.
     */
    private function sanitizePhoneNumber(string $phone): string
    {
        // Keep + at the beginning if present
        $hasPlus = str_starts_with($phone, '+');
        $cleaned = preg_replace('/[^\d]/', '', $phone);

        return $hasPlus ? '+' . $cleaned : $cleaned;
    }

    /**
     * Check if the phone number has a valid format.
     */
    private function isValidFormat(string $phone): bool
    {
        // Basic format validation - between 10 and 15 digits
        $digitsOnly = preg_replace('/[^\d]/', '', $phone);
        $length = strlen($digitsOnly);

        return $length >= 10 && $length <= 15;
    }

    /**
     * Check if the phone number has a country code.
     */
    private function hasCountryCode(string $phone): bool
    {
        return str_starts_with($phone, '+');
    }

    /**
     * Check if the phone number is from an allowed country.
     */
    private function isAllowedCountry(string $phone): bool
    {
        if (empty($this->allowedCountries)) {
            return true;
        }

        // Simple country code mapping (extend as needed)
        $countryCodes = [
            'US' => '+1',
            'UK' => '+44',
            'CA' => '+1',
            'AU' => '+61',
            'IN' => '+91',
            'DE' => '+49',
            'FR' => '+33',
            'ES' => '+34',
            'IT' => '+39',
            'JP' => '+81',
            'CN' => '+86',
            'BR' => '+55',
            'MX' => '+52',
            'RU' => '+7',
        ];

        foreach ($this->allowedCountries as $country) {
            if (isset($countryCodes[$country]) && str_starts_with($phone, $countryCodes[$country])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a rule that requires a country code.
     */
    public static function withCountryCode(array $allowedCountries = []): self
    {
        return new self($allowedCountries, true);
    }

    /**
     * Create a rule for specific countries.
     */
    public static function forCountries(array $countries): self
    {
        return new self($countries, false);
    }
}
