<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Http\Requests\TenantScopedFormRequest;
use App\Rules\PhoneNumber;
use App\Rules\TenantExists;

/**
 * Form request for creating or updating student records.
 *
 * Automatically scoped to the current tenant and includes validation
 * for all student-specific fields including enrollment information.
 */
class StoreStudentRequest extends TenantScopedFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    protected function getTenantScopedRules(): array
    {
        return [
            'student_id' => ['required', 'string', 'max:20', 'unique:students,student_id'],
            'first_name' => $this->getRulesFor('name_rules'),
            'last_name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules', ['unique:students,email']),
            'phone' => ['required', new PhoneNumber()],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            'address' => ['required', 'string', 'max:500'],
            'emergency_contact_name' => $this->getRulesFor('name_rules'),
            'emergency_contact_phone' => ['required', new PhoneNumber()],
            'emergency_contact_relationship' => ['required', 'string', 'max:50'],
            'course_id' => ['required', 'integer', new TenantExists('courses', 'id', $this->getCurrentTenantId())],
            'enrollment_date' => ['required', 'date', 'after_or_equal:today'],
            'status' => ['required', 'in:active,inactive,suspended,graduated'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'student_id.unique' => 'This student ID is already in use.',
            'date_of_birth.before' => 'The date of birth must be before today.',
            'enrollment_date.after_or_equal' => 'The enrollment date must be today or in the future.',
            'gender.in' => 'Please select a valid gender option.',
            'status.in' => 'Please select a valid status.',
            'course_id.required' => 'A course must be selected for the student.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'student_id' => 'student ID',
            'course_id' => 'course',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
            'emergency_contact_relationship' => 'emergency contact relationship',
            'enrollment_date' => 'enrollment date',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Auto-generate student ID if not provided
        if (!$this->has('student_id') || empty($this->input('student_id'))) {
            $this->merge([
                'student_id' => $this->generateStudentId(),
            ]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }
    }

    /**
     * Generate a unique student ID.
     */
    private function generateStudentId(): string
    {
        $tenantId = $this->getCurrentTenantId();
        $year = date('Y');
        $sequence = str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        return "STU{$tenantId}{$year}{$sequence}";
    }
}
