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
            'student_id' => ['required', 'string', 'max:20', "unique:students,student_id,{$studentId}"],
            'first_name' => $this->getRulesFor('name_rules'),
            'last_name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules', ["unique:students,email,{$studentId}"]),
            'phone' => ['required', new PhoneNumber()],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['required', 'in:male,female,other'],
            'address' => ['required', 'string', 'max:500'],
            'enrollment_date' => ['required', 'date'],
            'grade' => ['required', 'string', 'max:10'],
            'section' => ['required', 'string', 'max:10'],
            'parent_name' => $this->getRulesFor('name_rules'),
            'parent_phone' => ['required', new PhoneNumber()],
            'parent_email' => $this->getRulesFor('email_rules'),
        ];
    }
}
