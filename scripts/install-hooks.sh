#!/bin/sh

# Install Git hooks for EduVoltV2 project
# This script copies the pre-commit hook to your local Git hooks directory

echo "Installing Git hooks for EduVoltV2..."

# Create hooks directory if it doesn't exist
mkdir -p .git/hooks

# Copy pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/sh

# Laravel Pint pre-commit hook
# This hook runs Laravel Pint to check PSR-12 compliance before committing

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "${YELLOW}Running Laravel Pint (PSR-12 code style check)...${NC}"

# Run Pint test to check for violations
if ! php vendor/bin/pint --test; then
    echo "${RED}❌ Code style violations found!${NC}"
    echo "${YELLOW}Run 'composer format' to fix them automatically.${NC}"
    exit 1
fi

echo "${GREEN}✅ Code style check passed!${NC}"

# Run PHPUnit tests
echo "${YELLOW}Running PHPUnit tests...${NC}"

if ! php artisan test; then
    echo "${RED}❌ Tests failed!${NC}"
    exit 1
fi

echo "${GREEN}✅ All tests passed!${NC}"
echo "${GREEN}✅ Commit looks good!${NC}"

exit 0
EOF

# Make hooks executable
chmod +x .git/hooks/pre-commit

echo "✅ Git hooks installed successfully!"
echo ""
echo "Available hooks:"
echo "  - pre-commit: Runs Pint code style check and PHPUnit tests"
echo ""
echo "To run code quality checks manually:"
echo "  composer quality      # Run all quality checks"
echo "  composer format       # Fix code style issues"
echo "  composer format-test  # Check code style without fixing"
echo "  composer test         # Run tests only"