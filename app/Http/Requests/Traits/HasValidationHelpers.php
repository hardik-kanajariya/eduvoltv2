<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

use App\Rules\AcademicGrade;
use App\Rules\PhoneNumber;
use App\Rules\StrongPassword;
use App\Rules\TenantExists;

/**
 * Trait providing common validation utilities for form requests.
 * 
 * This trait can be used to add standardized validation patterns
 * and helpers to any form request class.
 */
trait HasValidationHelpers
{
    /**
     * Get validation rules for academic year field.
     */
    protected function getAcademicYearRules(): array
    {
        $currentYear = (int) date('Y');
        $minYear = $currentYear - 10;
        $maxYear = $currentYear + 5;

        return [
            'required',
            'integer',
            "min:{$minYear}",
            "max:{$maxYear}",
        ];
    }

    /**
     * Get validation rules for student age.
     */
    protected function getStudentAgeRules(int $minAge = 5, int $maxAge = 100): array
    {
        $maxDate = now()->subYears($minAge)->format('Y-m-d');
        $minDate = now()->subYears($maxAge)->format('Y-m-d');

        return [
            'required',
            'date',
            "before:{$maxDate}",
            "after:{$minDate}",
        ];
    }

    /**
     * Get validation rules for enrollment date.
     */
    protected function getEnrollmentDateRules(): array
    {
        $maxFutureDate = now()->addYear()->format('Y-m-d');

        return [
            'required',
            'date',
            'after_or_equal:today',
            "before_or_equal:{$maxFutureDate}",
        ];
    }

    /**
     * Get validation rules for graduation date.
     */
    protected function getGraduationDateRules(): array
    {
        $minDate = now()->subYears(10)->format('Y-m-d');
        $maxDate = now()->addYears(10)->format('Y-m-d');

        return [
            'nullable',
            'date',
            "after:{$minDate}",
            "before:{$maxDate}",
        ];
    }

    /**
     * Get validation rules for file uploads.
     */
    protected function getFileUploadRules(array $mimeTypes = ['pdf', 'doc', 'docx'], int $maxSizeKb = 5120): array
    {
        $mimeString = implode(',', $mimeTypes);

        return [
            'nullable',
            'file',
            "mimes:{$mimeString}",
            "max:{$maxSizeKb}",
        ];
    }

    /**
     * Get validation rules for image uploads.
     */
    protected function getImageUploadRules(int $maxSizeKb = 2048): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg,gif',
            "max:{$maxSizeKb}",
        ];
    }

    /**
     * Get validation rules for grade/percentage fields.
     */
    protected function getGradeRules(float $min = 0.0, float $max = 100.0): array
    {
        return [
            'nullable',
            'numeric',
            "min:{$min}",
            "max:{$max}",
        ];
    }

    /**
     * Get validation rules for currency amounts.
     */
    protected function getCurrencyRules(float $min = 0.0, float $max = 999999.99): array
    {
        return [
            'nullable',
            'numeric',
            "min:{$min}",
            "max:{$max}",
            'regex:/^\d+(\.\d{1,2})?$/', // Up to 2 decimal places
        ];
    }

    /**
     * Get validation rules for course codes.
     */
    protected function getCourseCodeRules(): array
    {
        return [
            'required',
            'string',
            'max:20',
            'regex:/^[A-Z0-9\-\_]+$/', // Uppercase letters, numbers, hyphens, underscores
        ];
    }

    /**
     * Get validation rules for time fields (HH:MM format).
     */
    protected function getTimeRules(): array
    {
        return [
            'nullable',
            'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', // HH:MM format
        ];
    }

    /**
     * Get validation rules for URLs.
     */
    protected function getUrlRules(bool $required = false): array
    {
        $rules = $required ? ['required'] : ['nullable'];
        
        return array_merge($rules, [
            'url',
            'max:500',
        ]);
    }

    /**
     * Get validation rules for social security or national ID numbers.
     */
    protected function getNationalIdRules(): array
    {
        return [
            'nullable',
            'string',
            'max:50',
            'regex:/^[A-Za-z0-9\-]+$/', // Alphanumeric with hyphens
        ];
    }

    /**
     * Get validation rules for postal/zip codes.
     */
    protected function getPostalCodeRules(): array
    {
        return [
            'nullable',
            'string',
            'max:20',
            'regex:/^[A-Za-z0-9\s\-]+$/', // Alphanumeric with spaces and hyphens
        ];
    }

    /**
     * Get tenant-scoped existence rule for a specific table.
     */
    protected function getTenantExistsRule(string $table, string $column = 'id'): TenantExists
    {
        $tenantId = method_exists($this, 'getCurrentTenantId') ? $this->getCurrentTenantId() : null;
        return new TenantExists($table, $column, $tenantId);
    }

    /**
     * Get phone number validation rule with optional country restrictions.
     */
    protected function getPhoneRule(array $allowedCountries = [], bool $requireCountryCode = false): PhoneNumber
    {
        return $requireCountryCode 
            ? PhoneNumber::withCountryCode($allowedCountries)
            : new PhoneNumber($allowedCountries, false);
    }

    /**
     * Get password validation rule based on security level.
     */
    protected function getPasswordRule(string $strength = 'moderate'): StrongPassword
    {
        return match($strength) {
            'basic' => StrongPassword::basic(),
            'strong' => StrongPassword::strong(),
            'moderate' => StrongPassword::moderate(),
            default => StrongPassword::moderate(),
        };
    }

    /**
     * Get validation rules for status fields with custom values.
     */
    protected function getStatusRules(array $allowedStatuses): array
    {
        $statusString = implode(',', $allowedStatuses);
        
        return [
            'required',
            'string',
            "in:{$statusString}",
        ];
    }

    /**
     * Get validation rules for priority fields.
     */
    protected function getPriorityRules(): array
    {
        return $this->getStatusRules(['low', 'medium', 'high', 'urgent']);
    }

    /**
     * Get validation rules for gender fields.
     */
    protected function getGenderRules(): array
    {
        return $this->getStatusRules(['male', 'female', 'other', 'prefer_not_to_say']);
    }

    /**
     * Get validation rules for boolean fields that accept various formats.
     */
    protected function getBooleanRules(): array
    {
        return [
            'nullable',
            'in:0,1,true,false,yes,no',
        ];
    }

    /**
     * Get academic grade validation rule for different grading systems.
     */
    protected function getAcademicGradeRule(string $system = 'percentage'): AcademicGrade
    {
        return match($system) {
            'percentage' => AcademicGrade::percentage(),
            'gpa' => AcademicGrade::gpa(),
            'letter' => AcademicGrade::letterGrade(),
            'german' => AcademicGrade::forSystem('german_grade'),
            'french' => AcademicGrade::forSystem('french_grade'),
            'australian' => AcademicGrade::forSystem('australian_gpa'),
            default => AcademicGrade::percentage(),
        };
    }

    /**
     * Get validation rules for different grading scales.
     */
    protected function getGradingScaleRules(string $scale = 'percentage'): array
    {
        return [
            'required',
            $this->getAcademicGradeRule($scale),
        ];
    }

    /**
     * Normalize boolean input values.
     */
    protected function normalizeBooleanInput(string $field): void
    {
        if ($this->has($field)) {
            $value = $this->input($field);
            $normalized = match(strtolower((string) $value)) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => $value,
            };
            
            $this->merge([$field => $normalized]);
        }
    }
}