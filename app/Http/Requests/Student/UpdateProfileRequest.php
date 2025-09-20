<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\BaseFormRequest;
use App\Rules\PhoneNumber;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = Auth::user();
        $student = $this->route('student');

        // Base rules that apply to all users
        $baseRules = [
            // Contact Information
            'phone' => ['nullable', new PhoneNumber()],
            'address' => ['required', 'string', 'max:500'],

            // Emergency Contact
            'emergency_contact_name' => $this->getRulesFor('name_rules'),
            'emergency_contact_phone' => ['required', new PhoneNumber()],
            'emergency_contact_relationship' => ['required', 'string', 'max:50'],

            // Medical Information (JSON fields)
            'medical_conditions' => ['nullable', 'array'],
            'medical_conditions.*' => ['string', 'max:255'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'medications' => ['nullable', 'array'],
            'medications.*' => ['string', 'max:255'],
            'emergency_medical_info' => ['nullable', 'string', 'max:1000'],

            // Photo upload
            'photo' => $this->getImageUploadRules(2048), // 2MB max
        ];

        // Additional rules for parents
        if ($user && $user->hasRole('parent') && $user->email === $student->parent_email) {
            $baseRules = array_merge($baseRules, [
                // Parent/Guardian Information
                'parent_name' => $this->getRulesFor('name_rules'),
                'parent_phone' => ['required', new PhoneNumber()],
                'parent_email' => $this->getRulesFor('email_rules'),
            ]);
        }

        // Additional rules for admin/teachers (full profile editing)
        if ($user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('teacher'))) {
            $baseRules = array_merge($baseRules, [
                // Basic Information
                'first_name' => $this->getRulesFor('name_rules'),
                'last_name' => $this->getRulesFor('name_rules'),
                'email' => $this->getRulesFor('email_rules', ["unique:students,email,{$student->id}"]),
                'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
                'gender' => ['required', 'in:male,female,other'],
                'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],

                // Academic Information
                'admission_number' => ['required', 'string', 'max:20', "unique:students,admission_number,{$student->id}"],
                'grade_level' => ['required', 'string', 'max:10'],
                'class_section' => ['nullable', 'string', 'max:10'],
                'enrollment_date' => ['required', 'date'],
                'academic_year' => $this->getAcademicYearRules(),
                'previous_school' => ['nullable', 'string', 'max:255'],
                'status' => ['required', 'in:active,inactive,graduated,transferred,suspended'],

                // Parent/Guardian Information
                'parent_name' => $this->getRulesFor('name_rules'),
                'parent_phone' => ['required', new PhoneNumber()],
                'parent_email' => $this->getRulesFor('email_rules'),
                'parent_relationship' => ['required', 'string', 'in:father,mother,guardian,other'],
            ]);
        }

        return $baseRules;
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'medical_conditions.*' => 'medical condition',
            'allergies.*' => 'allergy',
            'medications.*' => 'medication',
            'parent_phone' => 'parent phone number',
            'parent_email' => 'parent email address',
            'emergency_contact_phone' => 'emergency contact phone',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'photo.image' => 'The profile photo must be a valid image file.',
            'photo.max' => 'The profile photo must not exceed 2MB.',
            'emergency_contact_name.required' => 'Emergency contact name is required.',
            'emergency_contact_phone.required' => 'Emergency contact phone is required.',
        ];
    }
}
