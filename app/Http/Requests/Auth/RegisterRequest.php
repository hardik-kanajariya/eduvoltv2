<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use App\Rules\StrongPassword;

/**
 * Form request for user registration.
 *
 * Handles validation for new user registration including
 * password strength, email uniqueness, and basic profile information.
 */
class RegisterRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Registration is open to all (no authentication required)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => $this->getRulesFor('name_rules'),
            'last_name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules', ['unique:users,email']),
            'password' => ['required', 'string', StrongPassword::moderate(), 'confirmed'],
            'phone' => $this->getRulesFor('phone_rules'),
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'date_of_birth.before' => 'The date of birth must be before today.',
            'date_of_birth.after' => 'The date of birth must be a valid date.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'terms_accepted' => 'terms and conditions',
        ]);
    }
}
