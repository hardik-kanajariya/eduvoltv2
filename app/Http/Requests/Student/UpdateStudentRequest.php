<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\BaseFormRequest;
use App\Rules\PhoneNumber;

class UpdateStudentRequest extends BaseFormRequest
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
        $studentId = $this->route('student');

        return [
            // Basic Information
            'admission_number' => ['required', 'string', 'max:20', "unique:students,admission_number,{$studentId}"],
            'first_name' => $this->getRulesFor('name_rules'),
            'last_name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules', ["unique:students,email,{$studentId}"]),
            'phone' => ['nullable', new PhoneNumber()],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['required', 'in:male,female,other'],
            'address' => ['required', 'string', 'max:500'],
            'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'photo' => $this->getImageUploadRules(2048), // 2MB max
            
            // Academic Information
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
        ];
    }
}
