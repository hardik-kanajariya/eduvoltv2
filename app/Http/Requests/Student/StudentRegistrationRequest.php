<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\BaseFormRequest;

class StudentRegistrationRequest extends BaseFormRequest
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
        return [
            // Medical Information
            'medical_conditions' => ['nullable', 'array'],
            'medical_conditions.*' => ['string', 'max:255'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'medications' => ['nullable', 'array'],
            'medications.*' => ['string', 'max:255'],
            'emergency_medical_info' => ['nullable', 'string', 'max:1000'],

            // Document uploads
            'photo' => $this->getImageUploadRules(2048), // 2MB max
            'documents' => ['nullable', 'array'],
            'documents.*' => $this->getFileUploadRules(['pdf', 'jpg', 'jpeg', 'png'], 5120), // 5MB max
        ];
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
            'documents.*' => 'document',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'photo.image' => 'The student photo must be a valid image file.',
            'photo.max' => 'The student photo must not exceed 2MB.',
            'documents.*.mimes' => 'Each document must be a PDF or image file.',
            'documents.*.max' => 'Each document must not exceed 5MB.',
        ];
    }
}
