#!/bin/bash

# Queue Monitoring and Management Script
# This script provides common queue monitoring and management commands

show_help() {
    echo "Queue Management Script"
    echo "======================"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  status      - Show queue status and worker information"
    echo "  failed      - List failed jobs"
    echo "  retry       - Retry all failed jobs"
    echo "  clear       - Clear all failed jobs"
    echo "  monitor     - Start real-time queue monitoring"
    echo "  workers     - Show running queue workers"
    echo "  test        - Dispatch test jobs"
    echo "  health      - Check Redis and queue health"
    echo "  help        - Show this help message"
    echo ""
}

show_status() {
    echo "📊 Queue System Status"
    echo "====================="
    echo ""
    
    echo "Queue Configuration:"
    ./vendor/bin/sail artisan config:show queue.default queue.connections.redis
    echo ""
    
    echo "Failed Jobs Count:"
    ./vendor/bin/sail artisan queue:failed | wc -l
    echo ""
}

show_failed() {
    echo "❌ Failed Jobs"
    echo "=============="
    ./vendor/bin/sail artisan queue:failed
}

retry_failed() {
    echo "🔄 Retrying Failed Jobs"
    echo "======================"
    ./vendor/bin/sail artisan queue:retry all
    echo "✅ All failed jobs have been queued for retry"
}

clear_failed() {
    echo "🗑️  Clearing Failed Jobs"
    echo "======================"
    read -p "Are you sure you want to clear all failed jobs? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        ./vendor/bin/sail artisan queue:flush
        echo "✅ All failed jobs have been cleared"
    else
        echo "❌ Operation cancelled"
    fi
}

monitor_queue() {
    echo "👀 Real-time Queue Monitoring"
    echo "============================"
    echo "Press Ctrl+C to stop monitoring"
    echo ""
    ./vendor/bin/sail artisan queue:work redis --verbose
}

show_workers() {
    echo "👷 Queue Workers"
    echo "==============="
    
    if command -v ps &> /dev/null; then
        echo "Local processes:"
        ps aux | grep "queue:work" | grep -v grep || echo "No local queue workers found"
    fi
    
    echo ""
    echo "Docker containers:"
    ./vendor/bin/sail ps
}

test_queue() {
    echo "🧪 Testing Queue System"
    echo "======================"
    
    echo "Testing Redis connectivity..."
    ./vendor/bin/sail artisan queue:test --redis-check
    echo ""
    
    echo "Dispatching test jobs..."
    for i in {1..3}; do
        ./vendor/bin/sail artisan queue:test --dispatch="Test job #$i - $(date)"
        echo "Dispatched test job #$i"
    done
    echo ""
    echo "✅ Test jobs dispatched. Check logs to see processing."
}

check_health() {
    echo "🏥 System Health Check"
    echo "===================="
    
    echo "Checking health endpoint..."
    curl -s http://localhost/health | python3 -m json.tool 2>/dev/null || curl -s http://localhost/health
    echo ""
}

case "$1" in
    status)
        show_status
        ;;
    failed)
        show_failed
        ;;
    retry)
        retry_failed
        ;;
    clear)
        clear_failed
        ;;
    monitor)
        monitor_queue
        ;;
    workers)
        show_workers
        ;;
    test)
        test_queue
        ;;
    health)
        check_health
        ;;
    help|--help|-h)
        show_help
        ;;
    "")
        show_help
        ;;
    *)
        echo "Unknown command: $1"
        echo ""
        show_help
        exit 1
        ;;
esac