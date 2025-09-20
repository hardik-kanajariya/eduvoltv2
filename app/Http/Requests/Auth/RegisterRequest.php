<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use App\Rules\StrongPassword;
use Spatie\Permission\Models\Role;

class RegisterRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Registration is open
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedRoleIds = Role::whereNotIn('name', ['super_admin', 'admin'])
            ->pluck('id')
            ->toArray();

        return [
            'name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules', ['unique:users,email']),
            'password' => ['required', 'confirmed', new StrongPassword()],
            'password_confirmation' => ['required'],
            'role_id' => [
                'required',
                'integer',
                'in:' . implode(',', $allowedRoleIds)
            ],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'role_id.required' => 'Please select your role.',
            'role_id.in' => 'The selected role is not valid for registration.',
        ]);
    }
}
