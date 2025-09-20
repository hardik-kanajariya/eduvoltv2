# Code Quality & Standards

EduVoltV2 follows PSR-12 coding standards and implements automated code quality checks.

## Code Formatting

### Laravel Pint

Laravel Pint is configured with PSR-12 preset for consistent code formatting.

```bash
# Check code style without fixing
composer format-test
# or
php vendor/bin/pint --test

# Fix code style issues automatically
composer format
# or
php vendor/bin/pint
```

### Configuration

- **Pint Config**: `pint.json` - Laravel Pint configuration with PSR-12 preset
- **PHP CS Fixer Config**: `.php-cs-fixer.php` - Additional PHP CS Fixer rules for advanced formatting
- **EditorConfig**: `.editorconfig` - Cross-editor formatting configuration

## IDE Configuration

### VSCode

The project includes VSCode configuration in `.vscode/`:

- **settings.json**: Workspace settings for PHP, Blade, and other file types
- **extensions.json**: Recommended extensions for Laravel development
- **launch.json**: PHP debugging configuration for Xdebug

Recommended VSCode extensions:
- PHP Intelephense
- Laravel Blade Formatter
- Prettier
- Tailwind CSS IntelliSense
- EditorConfig

### Other IDEs

The `.editorconfig` file provides basic formatting configuration for all editors that support EditorConfig.

## Git Hooks

### Pre-commit Hook

Automatically runs on every commit to ensure code quality:

1. **Code Style Check**: Runs Pint to verify PSR-12 compliance
2. **Test Suite**: Runs PHPUnit tests to ensure functionality

To install Git hooks for your local development:

```bash
./scripts/install-hooks.sh
```

### Manual Hook Management

If you need to skip hooks temporarily:

```bash
# Skip all hooks for a single commit
git commit --no-verify -m "Emergency fix"
```

## GitHub Actions CI/CD

The project includes automated quality checks in `.github/workflows/code-quality.yml`:

### Code Quality Workflow

- **PHP 8.2+** testing environment
- **Code Style**: Laravel Pint PSR-12 verification
- **Testing**: PHPUnit test suite with coverage reporting
- **Caching**: Composer dependency caching for faster builds

### Security Audit Workflow

- **Composer Audit**: Checks for known security vulnerabilities in dependencies

### Static Analysis (Optional)

- **PHPStan**: Static analysis at level 8 (when enabled)
- **Configuration**: `phpstan.neon`

## Quick Commands

```bash
# Run all quality checks
composer quality

# Individual commands
composer format-test    # Check code style
composer format        # Fix code style
composer test          # Run tests

# Git hooks
./scripts/install-hooks.sh  # Install pre-commit hooks
```

## Standards Enforced

- **PSR-12**: PHP coding standard
- **Laravel Conventions**: Framework-specific best practices
- **Strict Types**: PHP strict type declarations
- **Import Organization**: Alphabetical import sorting
- **Line Length**: 120 character maximum
- **Indentation**: 4 spaces for PHP, 2 for YAML/JSON

## Troubleshooting

### Common Issues

1. **Pre-commit hook fails**: Run `composer format` to fix style issues
2. **Tests fail during commit**: Fix failing tests before committing
3. **IDE not formatting**: Check EditorConfig plugin is installed
4. **Git hooks not working**: Ensure hooks are executable with `chmod +x .git/hooks/pre-commit`

### Disable Hooks Temporarily

If you need to commit with failing checks (not recommended):

```bash
git commit --no-verify -m "Your message"
```

### Reset Hooks

To reinstall hooks after issues:

```bash
./scripts/install-hooks.sh
```