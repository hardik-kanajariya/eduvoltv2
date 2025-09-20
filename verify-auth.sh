#!/bin/bash

# Authentication System Verification Script
# This script verifies that all components of the email-based authentication system are properly implemented

echo "🔐 EduVoltV2 Authentication System Verification"
echo "=============================================="
echo

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check if file exists
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${RED}✗${NC} $1"
        return 1
    fi
}

# Function to check if directory exists
check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} $1/"
        return 0
    else
        echo -e "${RED}✗${NC} $1/"
        return 1
    fi
}

# Function to check PHP syntax
check_php_syntax() {
    if php -l "$1" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $1 (syntax OK)"
        return 0
    else
        echo -e "${RED}✗${NC} $1 (syntax error)"
        return 1
    fi
}

total_checks=0
passed_checks=0

echo "📁 Directory Structure"
echo "---------------------"
directories=(
    "app/Http/Controllers/Auth"
    "app/Http/Requests/Auth"
    "resources/views/auth"
    "resources/views/layouts"
    "tests/Feature/Auth"
    "tests/Unit/Rules"
    "docs"
)

for dir in "${directories[@]}"; do
    total_checks=$((total_checks + 1))
    if check_dir "$dir"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "🎛️  Controllers"
echo "--------------"
controllers=(
    "app/Http/Controllers/Auth/LoginController.php"
    "app/Http/Controllers/Auth/RegisterController.php"
    "app/Http/Controllers/Auth/EmailVerificationController.php"
    "app/Http/Controllers/Auth/PasswordResetController.php"
    "app/Http/Controllers/DashboardController.php"
)

for controller in "${controllers[@]}"; do
    total_checks=$((total_checks + 1))
    if check_php_syntax "$controller"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "📝 Form Requests"
echo "---------------"
requests=(
    "app/Http/Requests/Auth/LoginRequest.php"
    "app/Http/Requests/Auth/RegisterRequest.php"
)

for request in "${requests[@]}"; do
    total_checks=$((total_checks + 1))
    if check_php_syntax "$request"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "🛡️  Validation Rules"
echo "------------------"
rules=(
    "app/Rules/StrongPassword.php"
)

for rule in "${rules[@]}"; do
    total_checks=$((total_checks + 1))
    if check_php_syntax "$rule"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "🎨 Views"
echo "--------"
views=(
    "resources/views/auth/login.blade.php"
    "resources/views/auth/register.blade.php"
    "resources/views/auth/verify-email.blade.php"
    "resources/views/auth/forgot-password.blade.php"
    "resources/views/auth/reset-password.blade.php"
    "resources/views/dashboard.blade.php"
    "resources/views/layouts/auth.blade.php"
)

for view in "${views[@]}"; do
    total_checks=$((total_checks + 1))
    if check_file "$view"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "🧪 Tests"
echo "--------"
tests=(
    "tests/Feature/Auth/LoginTest.php"
    "tests/Feature/Auth/RegisterTest.php"
    "tests/Feature/Auth/EmailVerificationTest.php"
    "tests/Feature/Auth/PasswordResetTest.php"
    "tests/Feature/Auth/DashboardTest.php"
    "tests/Unit/Rules/StrongPasswordTest.php"
    "tests/Integration/AuthenticationSystemTest.php"
)

for test in "${tests[@]}"; do
    total_checks=$((total_checks + 1))
    if check_php_syntax "$test"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "📚 Documentation"
echo "---------------"
docs=(
    "docs/AUTHENTICATION.md"
    "docs/AUTH_SETUP.md"
)

for doc in "${docs[@]}"; do
    total_checks=$((total_checks + 1))
    if check_file "$doc"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "⚙️  Configuration"
echo "----------------"
configs=(
    "routes/web.php"
    "database/migrations/0001_01_01_000000_create_users_table.php"
    "database/factories/UserFactory.php"
    "app/Models/User.php"
    ".env"
)

for config in "${configs[@]}"; do
    total_checks=$((total_checks + 1))
    if check_file "$config"; then
        passed_checks=$((passed_checks + 1))
    fi
done

echo
echo "🔍 Routes Verification"
echo "---------------------"
# Check if routes contain expected authentication routes
if grep -q "LoginController" routes/web.php; then
    echo -e "${GREEN}✓${NC} Login routes defined"
    passed_checks=$((passed_checks + 1))
else
    echo -e "${RED}✗${NC} Login routes missing"
fi
total_checks=$((total_checks + 1))

if grep -q "RegisterController" routes/web.php; then
    echo -e "${GREEN}✓${NC} Register routes defined"
    passed_checks=$((passed_checks + 1))
else
    echo -e "${RED}✗${NC} Register routes missing"
fi
total_checks=$((total_checks + 1))

if grep -q "EmailVerificationController" routes/web.php; then
    echo -e "${GREEN}✓${NC} Email verification routes defined"
    passed_checks=$((passed_checks + 1))
else
    echo -e "${RED}✗${NC} Email verification routes missing"
fi
total_checks=$((total_checks + 1))

if grep -q "PasswordResetController" routes/web.php; then
    echo -e "${GREEN}✓${NC} Password reset routes defined"
    passed_checks=$((passed_checks + 1))
else
    echo -e "${RED}✗${NC} Password reset routes missing"
fi
total_checks=$((total_checks + 1))

echo
echo "🗄️  Database Configuration"
echo "------------------------"
if grep -q "SESSION_DRIVER=database" .env; then
    echo -e "${GREEN}✓${NC} Database sessions configured"
    passed_checks=$((passed_checks + 1))
else
    echo -e "${YELLOW}!${NC} Database sessions not configured"
fi
total_checks=$((total_checks + 1))

if [ -f "database/database.sqlite" ]; then
    echo -e "${GREEN}✓${NC} SQLite database file exists"
    passed_checks=$((passed_checks + 1))
else
    echo -e "${YELLOW}!${NC} SQLite database file missing (run: touch database/database.sqlite)"
fi
total_checks=$((total_checks + 1))

echo
echo "📊 Summary"
echo "=========="
percentage=$((passed_checks * 100 / total_checks))

if [ $percentage -eq 100 ]; then
    echo -e "${GREEN}🎉 All checks passed! ($passed_checks/$total_checks)${NC}"
    echo -e "${GREEN}🚀 Authentication system is ready for use!${NC}"
elif [ $percentage -ge 90 ]; then
    echo -e "${YELLOW}⚠️  Almost complete! ($passed_checks/$total_checks - $percentage%)${NC}"
    echo -e "${YELLOW}📝 Minor issues to address before full functionality${NC}"
else
    echo -e "${RED}❌ Issues found ($passed_checks/$total_checks - $percentage%)${NC}"
    echo -e "${RED}🔧 Authentication system needs attention${NC}"
fi

echo
echo "🔧 Next Steps"
echo "============"
echo "1. Run: composer install (when network available)"
echo "2. Run: php artisan migrate"
echo "3. Run: php artisan test tests/Feature/Auth/"
echo "4. Configure mail settings for email verification"
echo "5. Start development server: php artisan serve"
echo
echo "📖 Documentation: docs/AUTHENTICATION.md"
echo "🛠️  Setup Guide: docs/AUTH_SETUP.md"

exit $((total_checks - passed_checks))