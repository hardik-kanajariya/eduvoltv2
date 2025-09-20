<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base Form Request class for EduVoltV2
 *
 * Provides common functionality for all form requests including:
 * - Tenant-scoped validation
 * - Consistent error message formatting
 * - Common validation patterns
 * - Authorization helpers
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Default authorization logic - can be overridden in child classes
        return $this->authorizeWithTenant();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'exists' => 'The selected :attribute is invalid.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'min' => 'The :attribute must be at least :min characters.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'date' => 'The :attribute is not a valid date.',
            'after' => 'The :attribute must be a date after :date.',
            'before' => 'The :attribute must be a date before :date.',
            'in' => 'The selected :attribute is invalid.',
            'regex' => 'The :attribute format is invalid.',
            'mimes' => 'The :attribute must be a file of type: :values.',
            'image' => 'The :attribute must be an image.',
            'size' => 'The :attribute must be :size kilobytes.',
            'tenant_id.required' => 'A valid tenant context is required.',
            'tenant_id.exists' => 'The specified tenant does not exist or you do not have access to it.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'phone' => 'phone number',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'tenant_id' => 'tenant',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $this->formatValidationErrors($validator);

        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Format validation errors consistently.
     */
    protected function formatValidationErrors(Validator $validator): array
    {
        $errors = [];

        foreach ($validator->errors()->messages() as $field => $messages) {
            $errors[$field] = [
                'messages' => $messages,
                'value' => $this->input($field),
            ];
        }

        return $errors;
    }

    /**
     * Check if the user is authorized within tenant context.
     */
    protected function authorizeWithTenant(): bool
    {
        // If tenant_id is in the request, validate user has access to that tenant
        if ($this->has('tenant_id')) {
            return $this->userCanAccessTenant($this->input('tenant_id'));
        }

        // If no tenant_id specified, check if user has a default tenant
        return $this->userHasDefaultTenant();
    }

    /**
     * Check if the authenticated user can access the specified tenant.
     */
    protected function userCanAccessTenant(mixed $tenantId): bool
    {
        if (!$this->user()) {
            return false;
        }

        // TODO: Implement actual tenant access checking logic
        // This would typically check against a user_tenants pivot table
        // or similar multi-tenant access control mechanism

        return true; // Placeholder - implement actual logic
    }

    /**
     * Check if the authenticated user has a default tenant.
     */
    protected function userHasDefaultTenant(): bool
    {
        if (!$this->user()) {
            return false;
        }

        // TODO: Implement actual default tenant checking logic

        return true; // Placeholder - implement actual logic
    }

    /**
     * Get the current tenant ID for the request.
     */
    protected function getCurrentTenantId(): ?int
    {
        // Try to get tenant_id from the request first
        if ($this->has('tenant_id')) {
            return (int) $this->input('tenant_id');
        }

        // Fall back to user's default tenant
        if ($this->user()) {
            // TODO: Implement getting user's default tenant
            return null; // Placeholder
        }

        return null;
    }

    /**
     * Add tenant scoping to validation rules.
     */
    protected function addTenantScoping(array $rules): array
    {
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            return $rules;
        }

        // Add tenant scoping to exists rules
        foreach ($rules as $field => $rule) {
            if (is_string($rule) && str_contains($rule, 'exists:')) {
                $rules[$field] = $this->addTenantToExistsRule($rule, $tenantId);
            } elseif (is_array($rule)) {
                foreach ($rule as $index => $singleRule) {
                    if (is_string($singleRule) && str_contains($singleRule, 'exists:')) {
                        $rules[$field][$index] = $this->addTenantToExistsRule($singleRule, $tenantId);
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Add tenant scoping to an exists validation rule.
     */
    protected function addTenantToExistsRule(string $rule, int $tenantId): string
    {
        // If the rule already has conditions, append tenant condition
        if (str_contains($rule, ',')) {
            return $rule . ",tenant_id,{$tenantId}";
        }

        // If it's a simple exists:table rule, add tenant condition
        if (preg_match('/^exists:([^,]+)$/', $rule, $matches)) {
            return "exists:{$matches[1]},id,tenant_id,{$tenantId}";
        }

        return $rule;
    }

    /**
     * Common validation rules for various field types.
     */
    protected function getCommonRules(): array
    {
        return [
            'email_rules' => ['required', 'email', 'max:255'],
            'phone_rules' => ['required', 'regex:/^\+?[\d\s\-\(\)]{10,20}$/'],
            'name_rules' => ['required', 'string', 'max:100', 'min:2'],
            'password_rules' => ['required', 'string', 'min:8', 'confirmed'],
            'date_rules' => ['required', 'date'],
            'optional_text_rules' => ['nullable', 'string', 'max:255'],
            'required_text_rules' => ['required', 'string', 'max:255'],
            'numeric_id_rules' => ['required', 'integer', 'min:1'],
            'optional_numeric_id_rules' => ['nullable', 'integer', 'min:1'],
            'tenant_id_rules' => ['required', 'integer', 'exists:tenants,id'],
        ];
    }

    /**
     * Get validation rules for common field patterns.
     */
    protected function getRulesFor(string $pattern, array $additional = []): array
    {
        $commonRules = $this->getCommonRules();

        if (!isset($commonRules[$pattern])) {
            throw new \InvalidArgumentException("Unknown validation pattern: {$pattern}");
        }

        return array_merge($commonRules[$pattern], $additional);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add tenant_id if not present and user has a default tenant
        if (!$this->has('tenant_id') && $this->user()) {
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $this->merge(['tenant_id' => $tenantId]);
            }
        }

        // Sanitize common fields
        $this->sanitizeInput();
    }

    /**
     * Sanitize input data.
     */
    protected function sanitizeInput(): void
    {
        $sanitized = [];

        // Trim string inputs
        foreach ($this->all() as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            }
        }

        if (!empty($sanitized)) {
            $this->merge($sanitized);
        }
    }
}
