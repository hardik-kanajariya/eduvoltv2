# Form Request Validation Documentation

This documentation covers the Form Request validation system implemented in EduVoltV2, including base classes, tenant scoping, and custom validation rules.

## Overview

The Form Request validation system provides:

- **BaseFormRequest**: Common functionality for all form requests
- **TenantScopedFormRequest**: Automatic tenant scoping for multi-tenant resources
- **Custom Validation Rules**: Business-specific validation logic
- **Consistent Error Messages**: Standardized error formatting across the application
- **Authorization Helpers**: Tenant-aware authorization checking

## Base Classes

### BaseFormRequest

The foundation class that all form requests should extend. Provides:

- Common validation patterns and rules
- Consistent error message formatting
- Tenant authorization helpers
- Input sanitization
- Validation rule utilities

```php
<?php

namespace App\Http\Requests\Example;

use App\Http\Requests\BaseFormRequest;

class ExampleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true; // or implement custom authorization
    }

    public function rules(): array
    {
        return [
            'name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules'),
            'phone' => $this->getRulesFor('phone_rules'),
        ];
    }
}
```

### TenantScopedFormRequest

Extends BaseFormRequest with automatic tenant scoping. Use for resources that belong to specific tenants:

```php
<?php

namespace App\Http\Requests\Example;

use App\Http\Requests\TenantScopedFormRequest;
use App\Rules\TenantExists;

class TenantResourceRequest extends TenantScopedFormRequest
{
    protected function getTenantScopedRules(): array
    {
        return [
            'name' => $this->getRulesFor('name_rules'),
            'category_id' => ['required', 'integer', new TenantExists('categories')],
        ];
    }
}
```

## Common Validation Patterns

The base classes provide pre-defined validation patterns:

### Available Patterns

- `email_rules`: `['required', 'email', 'max:255']`
- `phone_rules`: `['required', 'regex:/^\+?[\d\s\-\(\)]{10,20}$/']`
- `name_rules`: `['required', 'string', 'max:100', 'min:2']`
- `password_rules`: `['required', 'string', 'min:8', 'confirmed']`
- `date_rules`: `['required', 'date']`
- `optional_text_rules`: `['nullable', 'string', 'max:255']`
- `required_text_rules`: `['required', 'string', 'max:255']`
- `numeric_id_rules`: `['required', 'integer', 'min:1']`
- `optional_numeric_id_rules`: `['nullable', 'integer', 'min:1']`
- `tenant_id_rules`: `['required', 'integer', 'exists:tenants,id']`

### Usage

```php
public function rules(): array
{
    return [
        'first_name' => $this->getRulesFor('name_rules'),
        'email' => $this->getRulesFor('email_rules', ['unique:users,email']),
        'description' => $this->getRulesFor('optional_text_rules'),
    ];
}
```

## Custom Validation Rules

### TenantExists

Validates that a resource exists within the current tenant scope:

```php
use App\Rules\TenantExists;

'course_id' => ['required', 'integer', new TenantExists('courses', 'id', $tenantId)],
'user_id' => ['required', 'integer', new TenantExists('users')], // Uses 'id' by default
```

### PhoneNumber

Advanced phone number validation with international support:

```php
use App\Rules\PhoneNumber;

'phone' => ['required', new PhoneNumber()], // Basic validation
'mobile' => ['required', PhoneNumber::withCountryCode(['US', 'CA'])], // Requires country code
'contact' => ['required', PhoneNumber::forCountries(['US', 'UK', 'AU'])], // Specific countries
```

### StrongPassword

Configurable password strength validation:

```php
use App\Rules\StrongPassword;

'password' => ['required', 'string', StrongPassword::basic()], // 8+ chars, uppercase, lowercase, numbers
'password' => ['required', 'string', StrongPassword::moderate()], // 10+ chars, all character types
'password' => ['required', 'string', StrongPassword::strong()], // 12+ chars, all character types
'password' => ['required', 'string', StrongPassword::custom(16, true, true, true, true, true)], // Custom requirements
```

## Tenant Scoping

### Automatic Tenant Scoping

When using `TenantScopedFormRequest`, tenant scoping is automatically applied to `exists` rules:

```php
// Before scoping
'category_id' => ['required', 'exists:categories,id']

// After automatic scoping (if tenant_id = 5)
'category_id' => ['required', 'exists:categories,id,tenant_id,5']
```

### Manual Tenant Scoping

For more control, use the `TenantExists` rule:

```php
'course_id' => [
    'required', 
    'integer', 
    new TenantExists('courses', 'id', $this->getCurrentTenantId())
],
```

## Authorization

### Basic Authorization

```php
public function authorize(): bool
{
    return $this->user()->can('create', Student::class);
}
```

### Tenant-Aware Authorization

```php
public function authorize(): bool
{
    return $this->authorizeWithTenant() && 
           $this->user()->can('create', [Student::class, $this->getCurrentTenantId()]);
}
```

## Error Messages

### Default Messages

The base classes provide consistent error messages. You can override them:

```php
public function messages(): array
{
    return array_merge(parent::messages(), [
        'student_id.unique' => 'This student ID is already in use.',
        'enrollment_date.after_or_equal' => 'The enrollment date must be today or in the future.',
    ]);
}
```

### Custom Attributes

Customize field names in error messages:

```php
public function attributes(): array
{
    return array_merge(parent::attributes(), [
        'student_id' => 'student ID',
        'course_id' => 'course',
        'emergency_contact_name' => 'emergency contact name',
    ]);
}
```

## Data Preparation

### Input Sanitization

The base classes automatically:
- Trim string inputs
- Add tenant_id if missing and user has default tenant

### Custom Preparation

```php
protected function prepareForValidation(): void
{
    parent::prepareForValidation();

    // Auto-generate student ID if not provided
    if (!$this->has('student_id')) {
        $this->merge([
            'student_id' => $this->generateStudentId(),
        ]);
    }
}
```

## Example Implementation

### Complete Student Request Example

```php
<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\TenantScopedFormRequest;
use App\Rules\PhoneNumber;
use App\Rules\TenantExists;

class StoreStudentRequest extends TenantScopedFormRequest
{
    protected function getTenantScopedRules(): array
    {
        return [
            'student_id' => ['required', 'string', 'max:20', 'unique:students,student_id'],
            'first_name' => $this->getRulesFor('name_rules'),
            'last_name' => $this->getRulesFor('name_rules'),
            'email' => $this->getRulesFor('email_rules', ['unique:students,email']),
            'phone' => ['required', new PhoneNumber()],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'course_id' => ['required', 'integer', new TenantExists('courses')],
            'status' => ['required', 'in:active,inactive,suspended'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'student_id.unique' => 'This student ID is already in use.',
            'date_of_birth.before' => 'The date of birth must be before today.',
        ]);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }
    }
}
```

## Validation Helpers Trait

The `HasValidationHelpers` trait provides additional specialized validation patterns:

```php
<?php

namespace App\Http\Requests\Example;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Traits\HasValidationHelpers;

class ExampleRequest extends BaseFormRequest
{
    use HasValidationHelpers;

    public function rules(): array
    {
        return [
            'academic_year' => $this->getAcademicYearRules(),
            'enrollment_date' => $this->getEnrollmentDateRules(),
            'grade' => $this->getGradeRules(0, 100),
            'fee_amount' => $this->getCurrencyRules(),
            'course_code' => $this->getCourseCodeRules(),
            'profile_image' => $this->getImageUploadRules(),
            'document' => $this->getFileUploadRules(['pdf', 'doc']),
            'website' => $this->getUrlRules(),
            'status' => $this->getStatusRules(['active', 'inactive']),
            'gender' => $this->getGenderRules(),
            'priority' => $this->getPriorityRules(),
        ];
    }
}
```

### Available Helper Methods

- `getAcademicYearRules()`: Academic year validation (current year ±10/+5)
- `getStudentAgeRules($min, $max)`: Age validation with date constraints
- `getEnrollmentDateRules()`: Enrollment date (today to +1 year)
- `getGraduationDateRules()`: Graduation date (-10 to +10 years)
- `getFileUploadRules($types, $size)`: File upload validation
- `getImageUploadRules($size)`: Image upload validation
- `getGradeRules($min, $max)`: Grade/percentage validation
- `getCurrencyRules($min, $max)`: Currency amount validation
- `getCourseCodeRules()`: Course code format validation
- `getTimeRules()`: Time format validation (HH:MM)
- `getUrlRules($required)`: URL validation
- `getNationalIdRules()`: National ID validation
- `getPostalCodeRules()`: Postal/zip code validation
- `getTenantExistsRule($table, $column)`: Tenant-scoped existence
- `getPhoneRule($countries, $requireCode)`: Phone number validation
- `getPasswordRule($strength)`: Password strength validation
- `getStatusRules($statuses)`: Custom status validation
- `getGenderRules()`: Gender field validation
- `getPriorityRules()`: Priority field validation
- `getBooleanRules()`: Boolean field validation

## Best Practices

1. **Always extend BaseFormRequest or TenantScopedFormRequest**
2. **Use common validation patterns when possible**
3. **Leverage HasValidationHelpers trait for specialized patterns**
4. **Implement proper authorization logic**
5. **Provide clear error messages**
6. **Use custom validation rules for complex business logic**
7. **Prepare data consistently**
8. **Test validation rules thoroughly**

## Testing

When testing form requests, focus on:

- Validation rule coverage
- Authorization logic
- Error message accuracy
- Tenant scoping behavior
- Data preparation logic

```php
public function test_validation_rules()
{
    $request = new StoreStudentRequest();
    $validator = Validator::make([], $request->rules());
    
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('first_name', $validator->errors()->toArray());
}
```