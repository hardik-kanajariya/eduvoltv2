<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Base Form Request for tenant-scoped resources
 *
 * Automatically applies tenant scoping to all validation rules
 * and ensures the current user has access to the specified tenant.
 */
abstract class TenantScopedFormRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Require tenant authorization for all tenant-scoped requests
        return $this->authorizeWithTenant() && $this->authorizeTenantAccess();
    }

    /**
     * Get the validation rules with automatic tenant scoping.
     */
    public function rules(): array
    {
        $rules = $this->getTenantScopedRules();

        // Automatically add tenant scoping to all rules
        return $this->addTenantScoping($rules);
    }

    /**
     * Get the base validation rules before tenant scoping is applied.
     */
    abstract protected function getTenantScopedRules(): array;

    /**
     * Authorize access to the specified tenant.
     */
    protected function authorizeTenantAccess(): bool
    {
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            return false;
        }

        // Check if user can access this tenant
        return $this->userCanAccessTenant($tenantId);
    }

    /**
     * Prepare the data for validation with tenant requirements.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Ensure tenant_id is always present for tenant-scoped requests
        if (!$this->has('tenant_id')) {
            $this->merge(['tenant_id' => $this->getCurrentTenantId()]);
        }
    }

    /**
     * Get additional validation messages for tenant-scoped requests.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'tenant_id.required' => 'Tenant context is required for this operation.',
            'tenant_id.exists' => 'The specified tenant does not exist or you do not have access to it.',
            '*.exists' => 'The selected :attribute does not exist in your tenant.',
        ]);
    }
}
