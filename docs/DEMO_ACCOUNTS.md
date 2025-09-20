# Demo Accounts System

## Overview
The EduVoltV2 platform includes a comprehensive demo accounts system that allows developers and testers to quickly access the system with pre-configured user accounts representing different roles.

## Configuration

### Environment Variables
Add the following to your `.env` file:
```
DEMO_ACCOUNTS_ENABLED=true
```

Set to `false` to disable demo accounts functionality.

### Application Configuration
The configuration is automatically loaded from `config/app.php`:
```php
'demo_accounts_enabled' => env('DEMO_ACCOUNTS_ENABLED', false),
```

## Demo Accounts

When enabled, the following demo accounts are available:

| Role | Name | Email | Password | Description |
|------|------|-------|----------|-------------|
| Admin | Demo Admin | admin@demo.eduvolt.com | DemoAdmin123! | System Administrator - Full access |
| Teacher | Demo Teacher | teacher@demo.eduvolt.com | DemoTeacher123! | Teacher Account - Manage classes |
| Student | Demo Student | student@demo.eduvolt.com | DemoStudent123! | Student Account - View assignments |
| Parent | Demo Parent | parent@demo.eduvolt.com | DemoParent123! | Parent Account - Monitor progress |

## Database Seeding

### Automatic Seeding
Demo accounts are automatically created when running:
```bash
php artisan migrate:fresh --seed
```

### Manual Seeding
To seed only demo accounts:
```bash
php artisan db:seed --class=DemoAccountsSeeder
```

### Conditional Seeding
The seeder only runs when `DEMO_ACCOUNTS_ENABLED=true`. When disabled, it will show:
```
Demo accounts are disabled. Set DEMO_ACCOUNTS_ENABLED=true to enable.
```

## Frontend Integration

### Login Page
When demo accounts are enabled, the login page displays:
- A "Quick Demo Access" section above the login form
- Clickable buttons for each demo account
- Auto-fill functionality when clicking demo account buttons
- Visual feedback with hover and click effects

### Auto-Fill Functionality
- Click any demo account button to automatically fill email and password fields
- Visual feedback shows which account was selected
- Form is ready for immediate submission

## Testing

### Test Command
Use the built-in test command to verify functionality:
```bash
php artisan test:demo-accounts
```

This command will:
- Check if demo accounts are enabled
- List available demo accounts
- Verify accounts exist in the database
- Show tenant associations

### Manual Testing
1. Ensure `DEMO_ACCOUNTS_ENABLED=true` in `.env`
2. Run database seeding: `php artisan migrate:fresh --seed`
3. Start the server: `php artisan serve`
4. Visit the login page at `http://localhost:8000/login`
5. Click any demo account button to test auto-fill
6. Login with the filled credentials

## Architecture

### Components
- **DemoAccountsSeeder**: Creates demo users in database
- **DemoAccountsService**: Handles demo account logic and data retrieval
- **LoginController**: Passes demo accounts data to login view
- **Login View**: Displays demo accounts UI with JavaScript functionality

### Security
- Demo accounts are only available when explicitly enabled
- All demo passwords follow strong password requirements
- Demo accounts are clearly marked as such in the interface
- Can be completely disabled for production environments

## Customization

### Adding New Demo Accounts
Edit the `$demoAccounts` array in `DemoAccountsSeeder.php`:
```php
private array $demoAccounts = [
    [
        'role' => 'new_role',
        'name' => 'Demo New Role',
        'email' => 'newrole@demo.eduvolt.com',
        'password' => 'DemoNewRole123!',
        'description' => 'New Role Description',
    ],
    // ... existing accounts
];
```

### Customizing UI
The demo accounts UI can be customized in `resources/views/auth/login.blade.php`:
- Modify the `.demo-accounts-section` styling
- Adjust the grid layout in `.demo-accounts-grid`
- Update button styles in `.demo-account-btn`

### Service Integration
Use the `DemoAccountsService` in other parts of the application:
```php
$demoService = app(DemoAccountsService::class);

// Check if enabled
if ($demoService->isEnabled()) {
    // Get all accounts
    $accounts = $demoService->getDemoAccounts();
    
    // Get specific account
    $account = $demoService->getDemoAccountByEmail('admin@demo.eduvolt.com');
    
    // Check if email is demo account
    $isDemo = $demoService->isDemoAccountEmail($email);
}
```

## Production Deployment

**IMPORTANT**: Always set `DEMO_ACCOUNTS_ENABLED=false` in production environments to:
- Hide demo account buttons from login page
- Prevent demo accounts from being created during seeding
- Maintain security and professionalism

The system is designed to gracefully handle being disabled, with no impact on existing users or functionality.