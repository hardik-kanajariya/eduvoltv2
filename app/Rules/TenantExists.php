<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Validates that a resource exists within the specified tenant scope.
 *
 * This rule ensures that when referencing other resources (like courses, users, etc.),
 * they belong to the same tenant as the current request context.
 */
class TenantExists implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column = 'id',
        private ?int $tenantId = null
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $exists = DB::table($this->table)
            ->where($this->column, $value)
            ->where('tenant_id', $this->tenantId)
            ->exists();

        if (!$exists) {
            $fail("The selected {$attribute} does not exist in your organization.");
        }
    }

    /**
     * Set the tenant ID for scoping.
     */
    public function forTenant(?int $tenantId): self
    {
        $this->tenantId = $tenantId;

        return $this;
    }
}
