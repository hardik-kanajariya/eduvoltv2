# Authentication System Setup Guide

This guide helps developers set up and test the email-based authentication system.

## Quick Setup

### 1. Environment Configuration
```bash
# Copy environment file (if not done)
cp .env.example .env

# Set database to SQLite for local development
# Edit .env:
DB_CONNECTION=sqlite
```

### 2. Database Setup
```bash
# Create SQLite database file
touch database/database.sqlite

# Run migrations to create auth tables
php artisan migrate

# Optional: Seed with test data
php artisan db:seed
```

### 3. Application Key
The `.env` file already includes a valid application key:
```env
APP_KEY=base64:nbnJMQbpUj4Vy0xnCcbDmcPsqRR8qKjiPhAeVirPvcw=
```

### 4. Mail Configuration (Optional)
For email verification to work, configure mail settings in `.env`:
```env
MAIL_MAILER=log  # For testing, emails logged to storage/logs/laravel.log
# Or configure actual mail service (SMTP, etc.)
```

## Testing the Authentication System

### Run Feature Tests
```bash
# Run all authentication tests
php artisan test tests/Feature/Auth/

# Run specific test classes
php artisan test tests/Feature/Auth/LoginTest.php
php artisan test tests/Feature/Auth/RegisterTest.php
php artisan test tests/Feature/Auth/EmailVerificationTest.php
php artisan test tests/Feature/Auth/PasswordResetTest.php
php artisan test tests/Feature/Auth/DashboardTest.php

# Run unit tests for password validation
php artisan test tests/Unit/Rules/StrongPasswordTest.php
```

### Manual Testing

1. **Start the development server**:
   ```bash
   php artisan serve
   ```

2. **Visit authentication pages**:
   - Login: `http://localhost:8000/login`
   - Register: `http://localhost:8000/register`
   - Dashboard: `http://localhost:8000/dashboard` (requires login)

3. **Test registration flow**:
   - Fill out registration form with strong password
   - Check for automatic login and redirect to email verification
   - Look for verification email in logs (if using log driver)

4. **Test login flow**:
   - Use registered credentials
   - Test "Remember Me" functionality
   - Test rate limiting (5 failed attempts)

## Authentication Routes Overview

### Public Routes (Guest Users)
- `GET /login` - Login form
- `POST /login` - Process login
- `GET /register` - Registration form  
- `POST /register` - Process registration
- `GET /forgot-password` - Password reset request form
- `POST /forgot-password` - Send reset link
- `GET /reset-password/{token}` - Password reset form
- `POST /reset-password` - Process password reset

### Protected Routes (Authenticated Users)
- `POST /logout` - Logout user
- `GET /email/verify` - Email verification notice
- `GET /email/verify/{id}/{hash}` - Verify email (signed URL)
- `POST /email/verification-notification` - Resend verification
- `GET /dashboard` - User dashboard (requires verified email)

## Password Requirements

### Default Settings (Moderate)
- Minimum 10 characters
- At least one uppercase letter
- At least one lowercase letter  
- At least one number
- At least one special character
- Cannot be a common password

### Available Strength Levels
```php
StrongPassword::basic()    // 8+ chars, upper, lower, numbers
StrongPassword::moderate() // 10+ chars, upper, lower, numbers, symbols
StrongPassword::strong()   // 12+ chars, upper, lower, numbers, symbols
```

## Security Features

### Rate Limiting
- **Login attempts**: 5 per email/IP per 5 minutes
- **Email verification resend**: 6 per minute
- **Automatic clearing**: On successful login

### Session Security
- Session regeneration on login
- Complete session invalidation on logout
- CSRF protection on all forms
- Secure remember me tokens

### Email Verification
- Required before accessing dashboard
- Signed URLs prevent tampering
- Rate limited resend functionality
- Automatic redirect for verified users

## Common Issues & Solutions

### 1. "Class not found" errors
```bash
# Regenerate autoload files
composer dump-autoload
```

### 2. Database connection issues
```bash
# Ensure SQLite file exists
touch database/database.sqlite

# Check database configuration in .env
DB_CONNECTION=sqlite
```

### 3. Application key errors
```bash
# Generate new application key
php artisan key:generate
```

### 4. Email verification not working
```bash
# Check mail configuration in .env
MAIL_MAILER=log  # For development

# Check logs for sent emails
tail -f storage/logs/laravel.log
```

### 5. Route not found errors
```bash
# Clear route cache
php artisan route:clear

# List all routes to verify
php artisan route:list
```

## Development Tips

### Creating Test Users
```php
// In tinker or tests
User::factory()->create([
    'email' => 'test@example.com',
    'password' => Hash::make('password')
]);

// Create unverified user
User::factory()->unverified()->create();
```

### Debugging Authentication
```php
// Check if user is authenticated
auth()->check()

// Get current user
auth()->user()

// Check if email is verified
auth()->user()->hasVerifiedEmail()
```

### Custom Password Validation
```php
// In form requests
'password' => [
    'required', 
    'string', 
    StrongPassword::custom(
        minLength: 8,
        requireUppercase: true,
        requireLowercase: true,
        requireNumbers: true,
        requireSymbols: false,
        checkCommonPasswords: true
    ), 
    'confirmed'
]
```

## Production Deployment

### Pre-deployment Checklist
- [ ] Configure production database
- [ ] Set up mail service (SMTP, SES, etc.)
- [ ] Configure SSL/HTTPS
- [ ] Set proper session configuration
- [ ] Review rate limiting settings
- [ ] Test password reset emails
- [ ] Verify email verification flow
- [ ] Run full test suite

### Performance Considerations
- Use Redis for session storage in production
- Configure queue driver for email sending
- Consider CDN for static assets
- Enable OPcache for PHP
- Monitor rate limiting effectiveness

## Contributing

When modifying the authentication system:
1. Update relevant tests
2. Follow existing patterns in BaseFormRequest
3. Maintain multi-tenant compatibility
4. Update this documentation
5. Test all authentication flows
6. Verify security features still work