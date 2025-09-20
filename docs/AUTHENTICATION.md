# Email-Based User Authentication Implementation

This document provides a comprehensive guide to the email-based authentication system implemented for EduVoltV2.

## Overview

The authentication system provides secure user registration, login, and email verification with comprehensive security features including rate limiting, strong password validation, and brute force protection.

## Architecture

### Controllers

#### `LoginController`
- **Location**: `app/Http/Controllers/Auth/LoginController.php`
- **Features**:
  - Rate limiting (5 attempts per 5 minutes per email/IP)
  - Remember me functionality
  - Session regeneration on login
  - Secure logout with session invalidation

#### `RegisterController`
- **Location**: `app/Http/Controllers/Auth/RegisterController.php`
- **Features**:
  - Multi-field registration form
  - Automatic email verification trigger
  - Auto-login after successful registration
  - Strong password validation

#### `EmailVerificationController`
- **Location**: `app/Http/Controllers/Auth/EmailVerificationController.php`
- **Features**:
  - Email verification notice page
  - Signed URL verification
  - Resend verification with rate limiting
  - Automatic redirect for verified users

#### `PasswordResetController`
- **Location**: `app/Http/Controllers/Auth/PasswordResetController.php`
- **Features**:
  - Secure password reset link generation
  - Token-based password reset
  - Strong password validation on reset
  - Remember token invalidation

#### `DashboardController`
- **Location**: `app/Http/Controllers/DashboardController.php`
- **Features**:
  - Protected dashboard requiring authentication
  - Email verification enforcement
  - User information display

### Form Requests

#### `LoginRequest`
- **Location**: `app/Http/Requests/Auth/LoginRequest.php`
- **Validation**: Email format, required password, optional remember me

#### `RegisterRequest`
- **Location**: `app/Http/Requests/Auth/RegisterRequest.php`
- **Validation**: 
  - Name fields (first_name, last_name)
  - Email uniqueness
  - Phone number format
  - Date of birth (must be in past)
  - Strong password with confirmation
  - Terms acceptance requirement
  - Tenant ID for multi-tenant support

### Validation Rules

#### `StrongPassword`
- **Location**: `app/Rules/StrongPassword.php`
- **Configurations**:
  - **Basic**: 8+ chars, uppercase, lowercase, numbers
  - **Moderate**: 10+ chars, uppercase, lowercase, numbers, symbols
  - **Strong**: 12+ chars, uppercase, lowercase, numbers, symbols
- **Features**:
  - Common password detection
  - Customizable requirements
  - Clear error messages

## Routes

### Authentication Routes
```php
// Guest routes (login/register)
GET  /login                  -> LoginController@showLoginForm
POST /login                  -> LoginController@login
GET  /register               -> RegisterController@showRegistrationForm  
POST /register               -> RegisterController@register

// Password reset routes
GET  /forgot-password        -> PasswordResetController@showLinkRequestForm
POST /forgot-password        -> PasswordResetController@sendResetLink
GET  /reset-password/{token} -> PasswordResetController@showResetForm
POST /reset-password         -> PasswordResetController@reset

// Authenticated routes
POST /logout                 -> LoginController@logout
GET  /dashboard              -> DashboardController@index (requires verification)

// Email verification routes
GET  /email/verify           -> EmailVerificationController@notice
GET  /email/verify/{id}/{hash} -> EmailVerificationController@verify (signed)
POST /email/verification-notification -> EmailVerificationController@resend (throttled)
```

## Views

### Layout
- **Base Layout**: `resources/views/layouts/auth.blade.php`
  - Responsive design
  - Fallback styling when Vite unavailable
  - Clean, accessible forms
  - Consistent branding

### Authentication Views
- **Login**: `resources/views/auth/login.blade.php`
- **Register**: `resources/views/auth/register.blade.php`
- **Email Verification**: `resources/views/auth/verify-email.blade.php`
- **Forgot Password**: `resources/views/auth/forgot-password.blade.php`
- **Reset Password**: `resources/views/auth/reset-password.blade.php`
- **Dashboard**: `resources/views/dashboard.blade.php`

## Security Features

### Rate Limiting
- **Login attempts**: 5 per email/IP combination per 5 minutes
- **Email verification resend**: 6 per minute
- **Password reset**: Laravel's built-in throttling

### Password Security
- **Strength requirements**: Configurable via StrongPassword rule
- **Common password checking**: Prevents use of common weak passwords
- **Secure hashing**: Laravel's bcrypt with appropriate rounds

### Session Security
- **Session regeneration**: On successful login
- **Complete invalidation**: On logout
- **CSRF protection**: On all forms
- **Remember me**: Secure persistent sessions

### Email Verification
- **Required for dashboard**: Enforced via middleware
- **Signed URLs**: Prevent tampering
- **Rate limited resend**: Prevents spam

## Testing

### Feature Tests
- **LoginTest**: Login functionality, rate limiting, remember me
- **RegisterTest**: Registration validation, password requirements
- **EmailVerificationTest**: Verification flow, resend functionality
- **PasswordResetTest**: Reset flow, token validation
- **DashboardTest**: Access control, user display

### Unit Tests
- **StrongPasswordTest**: Password validation rule testing

### Test Database
- Uses RefreshDatabase trait for isolated tests
- UserFactory with verified/unverified states
- Comprehensive scenario coverage

## Configuration

### Environment Variables
```env
# Application
APP_NAME=EduVoltV2
APP_KEY=base64:...

# Database (SQLite for development/testing)
DB_CONNECTION=sqlite

# Session & Authentication
SESSION_DRIVER=database
AUTH_GUARD=web
AUTH_PASSWORD_BROKER=users
```

### User Model
- Implements `MustVerifyEmail` contract
- Mass assignable: name, email, password, tenant_id
- Proper attribute casting for email_verified_at
- Password hashing via mutator

## Multi-Tenant Support

The authentication system includes foundational multi-tenant support:
- `tenant_id` field in users table and model
- Tenant validation in registration
- BaseFormRequest includes tenant scoping helpers
- Ready for full multi-tenant implementation

## Usage Examples

### Basic Authentication Check
```php
// In controllers
$this->middleware('auth');
$this->middleware('verified'); // Requires email verification
```

### Custom Password Validation
```php
// In form requests
'password' => ['required', 'string', StrongPassword::moderate(), 'confirmed']
```

### Rate Limiting
```php
// Automatic via LoginController
// Custom implementation available via RateLimiter facade
```

## Deployment Considerations

1. **Database Migrations**: Run `php artisan migrate` to create auth tables
2. **Email Configuration**: Configure mail settings for verification emails
3. **SSL/HTTPS**: Required for secure session cookies
4. **Rate Limiting**: Consider Redis for distributed rate limiting
5. **Password Policies**: Review and adjust StrongPassword settings per requirements

## Future Enhancements

- Two-factor authentication (2FA)
- Social login integration
- Advanced password policies
- Account lockout policies
- Audit logging for security events
- OAuth2/API authentication

## Support

For questions or issues with the authentication system:
1. Check the comprehensive test suite for usage examples
2. Review Laravel's authentication documentation
3. Examine the existing BaseFormRequest patterns for multi-tenant scenarios