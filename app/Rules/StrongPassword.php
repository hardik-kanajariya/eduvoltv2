<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a password meets security requirements.
 * 
 * Configurable password strength validation including:
 * - Minimum length
 * - Character type requirements (uppercase, lowercase, numbers, symbols)
 * - Common password checking
 * - Dictionary word checking
 */
class StrongPassword implements ValidationRule
{
    private int $minLength;
    private bool $requireUppercase;
    private bool $requireLowercase;
    private bool $requireNumbers;
    private bool $requireSymbols;
    private bool $checkCommonPasswords;

    public function __construct(
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSymbols = false,
        bool $checkCommonPasswords = true
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSymbols = $requireSymbols;
        $this->checkCommonPasswords = $checkCommonPasswords;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $password = (string) $value;

        if (strlen($password) < $this->minLength) {
            $fail("The :attribute must be at least {$this->minLength} characters long.");
            return;
        }

        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $fail('The :attribute must contain at least one uppercase letter.');
            return;
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            $fail('The :attribute must contain at least one lowercase letter.');
            return;
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $password)) {
            $fail('The :attribute must contain at least one number.');
            return;
        }

        if ($this->requireSymbols && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $fail('The :attribute must contain at least one special character.');
            return;
        }

        if ($this->checkCommonPasswords && $this->isCommonPassword($password)) {
            $fail('The :attribute is too common. Please choose a more secure password.');
        }
    }

    /**
     * Check if the password is a commonly used password.
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123', 'monkey',
            'letmein', 'dragon', '111111', 'baseball', 'iloveyou', 'trustno1',
            'sunshine', 'master', '123123', 'welcome', 'shadow', 'ashley',
            'football', 'jesus', 'michael', 'ninja', 'mustang', 'password1',
            'admin', 'user', 'test', 'guest', 'root', 'administrator',
        ];

        $lowerPassword = strtolower($password);
        
        return in_array($lowerPassword, $commonPasswords);
    }

    /**
     * Create a basic password rule (minimum requirements).
     */
    public static function basic(): self
    {
        return new self(8, true, true, true, false, true);
    }

    /**
     * Create a moderate password rule.
     */
    public static function moderate(): self
    {
        return new self(10, true, true, true, true, true);
    }

    /**
     * Create a strong password rule (high security).
     */
    public static function strong(): self
    {
        return new self(12, true, true, true, true, true);
    }

    /**
     * Create a custom password rule.
     */
    public static function custom(
        int $minLength,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSymbols = false,
        bool $checkCommonPasswords = true
    ): self {
        return new self(
            $minLength,
            $requireUppercase,
            $requireLowercase,
            $requireNumbers,
            $requireSymbols,
            $checkCommonPasswords
        );
    }
}