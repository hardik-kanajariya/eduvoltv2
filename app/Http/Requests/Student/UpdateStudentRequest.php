<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Http\Requests\TenantScopedFormRequest;
use App\Http\Requests\Traits\HasValidationHelpers;
use App\Rules\PhoneNumber;

/**
 * Form request for updating student records.
 * 
 * Demonstrates comprehensive usage of all validation components:
 * - Base tenant-scoped functionality
 * - Custom validation rules
 * - Validation helper traits
 * - Proper authorization and error handling
 */
class UpdateStudentRequest extends TenantScopedFormRequest
{
    use HasValidationHelpers;

    /**
     * Get the validation rules that apply to the request.
     */
    protected function getTenantScopedRules(): array
    {
        $studentId = $this->route('student'); // Assuming route parameter

        return [
            'student_id' => [
                'required', 
                'string', 
                'max:20', 
                "unique:students,student_id,{$studentId},id"
            ],
            'first_name' => $this->getRulesFor('name_rules'),
            'last_name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules', ["unique:students,email,{$studentId},id"]),
            'phone' => ['required', $this->getPhoneRule(['US', 'CA'], false)],
            'date_of_birth' => $this->getStudentAgeRules(5, 80),
            'gender' => $this->getGenderRules(),
            'address' => $this->getRulesFor('required_text_rules', ['max:500']),
            'postal_code' => $this->getPostalCodeRules(),
            'emergency_contact_name' => $this->getRulesFor('name_rules'),
            'emergency_contact_phone' => ['required', new PhoneNumber()],
            'emergency_contact_relationship' => $this->getRulesFor('required_text_rules', ['max:50']),
            'course_id' => ['required', 'integer', $this->getTenantExistsRule('courses')],
            'enrollment_date' => $this->getEnrollmentDateRules(),
            'graduation_date' => $this->getGraduationDateRules(),
            'academic_year' => $this->getAcademicYearRules(),
            'status' => $this->getStatusRules(['active', 'inactive', 'suspended', 'graduated']),
            'gpa' => $this->getGradeRules(0.0, 4.0),
            'profile_image' => $this->getImageUploadRules(1024), // 1MB max
            'documents.*' => $this->getFileUploadRules(['pdf', 'doc', 'docx'], 5120), // 5MB max
            'personal_website' => $this->getUrlRules(false),
            'notes' => $this->getRulesFor('optional_text_rules', ['max:1000']),
            'is_international' => $this->getBooleanRules(),
            'national_id' => $this->getNationalIdRules(),
            'fee_balance' => $this->getCurrencyRules(0, 50000),
        ];
    }

    /**
     * Authorize the request with additional student-specific checks.
     */
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        // Additional authorization: user can only update students they have access to
        $student = $this->route('student');
        
        return $this->user()->can('update', $student);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'student_id.unique' => 'This student ID is already in use by another student.',
            'email.unique' => 'This email address is already registered to another student.',
            'enrollment_date.after_or_equal' => 'The enrollment date cannot be in the past.',
            'graduation_date.after' => 'The graduation date must be after the enrollment date.',
            'gpa.max' => 'The GPA cannot exceed 4.0.',
            'gpa.min' => 'The GPA cannot be negative.',
            'profile_image.max' => 'The profile image must not be larger than 1MB.',
            'documents.*.max' => 'Each document must not be larger than 5MB.',
            'fee_balance.regex' => 'The fee balance must be a valid currency amount (up to 2 decimal places).',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'gpa' => 'grade point average',
            'is_international' => 'international student status',
            'national_id' => 'national ID number',
            'fee_balance' => 'outstanding fee balance',
            'personal_website' => 'personal website URL',
            'documents.*' => 'document',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Normalize boolean inputs
        $this->normalizeBooleanInput('is_international');

        // Format phone numbers
        if ($this->has('phone')) {
            $this->merge([
                'phone' => $this->formatPhoneNumber($this->input('phone')),
            ]);
        }

        if ($this->has('emergency_contact_phone')) {
            $this->merge([
                'emergency_contact_phone' => $this->formatPhoneNumber($this->input('emergency_contact_phone')),
            ]);
        }

        // Ensure graduation date is after enrollment date if both are provided
        if ($this->has('graduation_date') && $this->has('enrollment_date')) {
            $this->validateDateSequence();
        }
    }

    /**
     * Format phone number consistently.
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Add + if it starts with a digit and looks like international format
        if (preg_match('/^\d{10,}$/', $cleaned) && strlen($cleaned) > 10) {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Add validation to ensure graduation date is after enrollment date.
     */
    private function validateDateSequence(): void
    {
        $enrollmentDate = $this->input('enrollment_date');
        $graduationDate = $this->input('graduation_date');

        if ($enrollmentDate && $graduationDate && $graduationDate <= $enrollmentDate) {
            // We'll let the validator handle this with a custom rule
            $this->merge([
                'graduation_date' => null, // This will trigger validation error
            ]);
        }
    }

    /**
     * Get additional validation rules after base rules are applied.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: graduation date must be after enrollment date
            if ($this->has('graduation_date') && $this->has('enrollment_date')) {
                $enrollmentDate = $this->input('enrollment_date');
                $graduationDate = $this->input('graduation_date');

                if ($graduationDate && $enrollmentDate && $graduationDate <= $enrollmentDate) {
                    $validator->errors()->add(
                        'graduation_date',
                        'The graduation date must be after the enrollment date.'
                    );
                }
            }

            // Custom validation: international students must have national ID
            if ($this->input('is_international') && !$this->input('national_id')) {
                $validator->errors()->add(
                    'national_id',
                    'International students must provide a national ID number.'
                );
            }
        });
    }
}