<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates academic grade/score based on different grading systems.
 * 
 * Supports multiple grading systems:
 * - Percentage (0-100)
 * - GPA (0.0-4.0)
 * - Letter grades (A-F)
 * - Custom scale (configurable min/max)
 */
class AcademicGrade implements ValidationRule
{
    public const PERCENTAGE = 'percentage';
    public const GPA = 'gpa';
    public const LETTER = 'letter';
    public const CUSTOM = 'custom';

    private string $system;
    private float $min;
    private float $max;
    private array $allowedLetterGrades;

    public function __construct(
        string $system = self::PERCENTAGE,
        float $min = 0.0,
        float $max = 100.0,
        array $allowedLetterGrades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'F']
    ) {
        $this->system = $system;
        $this->min = $min;
        $this->max = $max;
        $this->allowedLetterGrades = $allowedLetterGrades;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        switch ($this->system) {
            case self::PERCENTAGE:
                $this->validatePercentage($attribute, $value, $fail);
                break;
            case self::GPA:
                $this->validateGPA($attribute, $value, $fail);
                break;
            case self::LETTER:
                $this->validateLetterGrade($attribute, $value, $fail);
                break;
            case self::CUSTOM:
                $this->validateCustomScale($attribute, $value, $fail);
                break;
            default:
                $fail("Unknown grading system: {$this->system}");
        }
    }

    /**
     * Validate percentage grade (0-100).
     */
    private function validatePercentage(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('The :attribute must be a numeric percentage.');
            return;
        }

        $numericValue = (float) $value;
        
        if ($numericValue < 0 || $numericValue > 100) {
            $fail('The :attribute must be between 0 and 100.');
        }
    }

    /**
     * Validate GPA grade (0.0-4.0).
     */
    private function validateGPA(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('The :attribute must be a numeric GPA value.');
            return;
        }

        $numericValue = (float) $value;
        
        if ($numericValue < 0.0 || $numericValue > 4.0) {
            $fail('The :attribute must be between 0.0 and 4.0.');
        }
    }

    /**
     * Validate letter grade.
     */
    private function validateLetterGrade(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a valid letter grade.');
            return;
        }

        $grade = strtoupper(trim($value));
        
        if (!in_array($grade, $this->allowedLetterGrades)) {
            $allowedGrades = implode(', ', $this->allowedLetterGrades);
            $fail("The :attribute must be one of the following grades: {$allowedGrades}.");
        }
    }

    /**
     * Validate custom scale grade.
     */
    private function validateCustomScale(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('The :attribute must be a numeric value.');
            return;
        }

        $numericValue = (float) $value;
        
        if ($numericValue < $this->min || $numericValue > $this->max) {
            $fail("The :attribute must be between {$this->min} and {$this->max}.");
        }
    }

    /**
     * Create a percentage grade validator.
     */
    public static function percentage(): self
    {
        return new self(self::PERCENTAGE);
    }

    /**
     * Create a GPA grade validator.
     */
    public static function gpa(): self
    {
        return new self(self::GPA, 0.0, 4.0);
    }

    /**
     * Create a letter grade validator.
     */
    public static function letterGrade(array $allowedGrades = null): self
    {
        $allowedGrades = $allowedGrades ?? ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'F'];
        return new self(self::LETTER, 0, 0, $allowedGrades);
    }

    /**
     * Create a custom scale grade validator.
     */
    public static function customScale(float $min, float $max): self
    {
        return new self(self::CUSTOM, $min, $max);
    }

    /**
     * Create a validator for specific educational systems.
     */
    public static function forSystem(string $system): self
    {
        return match($system) {
            'us_gpa' => self::gpa(),
            'uk_percentage' => self::percentage(),
            'canadian_percentage' => self::percentage(),
            'indian_percentage' => self::percentage(),
            'german_grade' => self::customScale(1.0, 6.0), // German grading system (1-6, 1 is best)
            'french_grade' => self::customScale(0.0, 20.0), // French grading system (0-20)
            'australian_gpa' => self::customScale(0.0, 7.0), // Australian GPA system
            default => self::percentage(),
        };
    }

    /**
     * Convert grade from one system to another (basic conversion).
     */
    public static function convertGrade(mixed $value, string $fromSystem, string $toSystem): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $numericValue = (float) $value;

        // Convert to percentage first
        $percentage = match($fromSystem) {
            'gpa' => ($numericValue / 4.0) * 100,
            'percentage' => $numericValue,
            'german_grade' => (6.0 - $numericValue) / 5.0 * 100, // Reverse scale
            'french_grade' => ($numericValue / 20.0) * 100,
            'australian_gpa' => ($numericValue / 7.0) * 100,
            default => $numericValue,
        };

        // Convert from percentage to target system
        return match($toSystem) {
            'gpa' => ($percentage / 100) * 4.0,
            'percentage' => $percentage,
            'german_grade' => 6.0 - (($percentage / 100) * 5.0),
            'french_grade' => ($percentage / 100) * 20.0,
            'australian_gpa' => ($percentage / 100) * 7.0,
            default => $percentage,
        };
    }
}