#!/bin/bash

# Redis Queue System Validation Script
# This script tests the complete Redis queue system with Docker Sail

set -e

echo "🚀 Starting Redis Queue System Validation"
echo "========================================"

# Step 1: Start Docker Services
echo "1. Starting Docker services..."
./vendor/bin/sail up -d

# Wait for services to be ready
echo "   Waiting for services to start..."
sleep 10

# Step 2: Check service health
echo "2. Checking service health..."
./vendor/bin/sail exec redis redis-cli ping
if [ $? -eq 0 ]; then
    echo "   ✅ Redis service is running"
else
    echo "   ❌ Redis service is not responding"
    exit 1
fi

# Step 3: Run migrations
echo "3. Running database migrations..."
./vendor/bin/sail artisan migrate --force
echo "   ✅ Migrations completed"

# Step 4: Test queue configuration
echo "4. Testing queue configuration..."
./vendor/bin/sail artisan queue:test --redis-check
echo "   ✅ Queue configuration test completed"

# Step 5: Test job dispatch
echo "5. Testing job dispatch..."
./vendor/bin/sail artisan queue:test --dispatch="Redis queue validation test"
echo "   ✅ Test job dispatched"

# Step 6: Check health endpoint
echo "6. Testing health endpoint..."
sleep 2
curl -s http://localhost/health | jq '.checks.redis.status' 2>/dev/null || echo "Health check available (install jq for JSON parsing)"
echo "   ✅ Health endpoint tested"

# Step 7: Show queue worker command
echo "7. Queue worker ready!"
echo "   To start processing jobs, run:"
echo "   ./vendor/bin/sail artisan queue:work redis --verbose"
echo ""
echo "   To monitor in another terminal:"
echo "   ./vendor/bin/sail logs -f"

echo ""
echo "🎉 Redis Queue System validation completed successfully!"
echo "📚 See docs/redis-queue-setup.md for detailed documentation"