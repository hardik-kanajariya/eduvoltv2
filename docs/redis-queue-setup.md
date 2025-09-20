# Redis Queue Configuration

This document outlines the Redis queue system configuration for EduVoltV2.

## Overview

The application is configured to use Redis as the queue backend for processing background jobs and notifications. This provides better performance and reliability compared to database-based queues.

## Configuration

### Docker Services

Redis has been added to the Docker Compose configuration:

```yaml
redis:
    image: 'redis:7-alpine'
    ports:
        - '${FORWARD_REDIS_PORT:-6379}:6379'
    volumes:
        - 'sail-redis:/data'
    networks:
        - sail
    healthcheck:
        test:
            - CMD
            - redis-cli
            - ping
        retries: 3
        timeout: 3s
```

### Environment Variables

The following environment variables configure the Redis queue system:

```env
# Queue Configuration
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Redis Queue Configuration
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90
REDIS_QUEUE_BLOCK_FOR=null
```

### Queue Settings

- **Driver**: Redis
- **Default Queue**: `default`
- **Retry After**: 90 seconds
- **Block For**: null (non-blocking mode)
- **Timeout**: 60 seconds (configurable per job)
- **Max Tries**: 3 (configurable per job)

## Starting the Queue System

### Development Environment

1. **Start Docker Services**:
   ```bash
   ./vendor/bin/sail up -d
   ```

2. **Run Database Migrations** (includes failed jobs table):
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

3. **Start Queue Worker**:
   ```bash
   ./vendor/bin/sail artisan queue:work redis --verbose
   ```

4. **Test Queue System**:
   ```bash
   # Test Redis connectivity
   ./vendor/bin/sail artisan queue:test --redis-check
   
   # Dispatch a test job
   ./vendor/bin/sail artisan queue:test --dispatch="Hello from Redis queue!"
   ```

### Production Environment

For production, use a process manager like Supervisor to manage queue workers:

```ini
[program:eduvolt-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/path/to/application
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/eduvolt-queue-worker.log
stopwaitsecs=3600
```

## Queue Worker Options

### Recommended Worker Command

```bash
php artisan queue:work redis \
    --sleep=3 \
    --tries=3 \
    --max-time=3600 \
    --memory=512 \
    --timeout=60
```

### Option Explanations

- `--sleep=3`: Wait 3 seconds when no jobs are available
- `--tries=3`: Maximum attempts for each job
- `--max-time=3600`: Worker runs for 1 hour before restarting
- `--memory=512`: Restart worker if memory usage exceeds 512MB
- `--timeout=60`: Kill jobs that run longer than 60 seconds

## Health Monitoring

The application includes Redis and queue health checks in the `/health` endpoint:

```json
{
  "status": "healthy",
  "checks": {
    "redis": {
      "status": "healthy",
      "message": "Redis connection successful"
    },
    "queue": {
      "status": "healthy",
      "message": "Queue system operational (driver: redis)",
      "connection": "redis"
    }
  }
}
```

## Job Monitoring

### Laravel Horizon (Recommended)

For advanced queue monitoring, consider installing Laravel Horizon:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon:publish
```

### Basic Monitoring Commands

```bash
# Check queue status
php artisan queue:work --help

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush
```

## Troubleshooting

### Common Issues

1. **Redis Connection Failed**
   - Ensure Redis service is running
   - Check Redis host configuration (`REDIS_HOST=redis` for Docker)
   - Verify network connectivity

2. **Jobs Not Processing**
   - Ensure queue worker is running
   - Check worker logs for errors
   - Verify job class exists and is properly namespaced

3. **Memory Issues**
   - Increase `--memory` limit for workers
   - Check for memory leaks in job code
   - Restart workers regularly

### Debugging Commands

```bash
# Test Redis connectivity
redis-cli -h redis ping

# Check queue configuration
php artisan config:show queue

# List active queue workers
ps aux | grep "queue:work"

# Monitor queue in real-time
php artisan queue:work --verbose
```

## Security Considerations

1. **Redis Access**: Ensure Redis is not exposed to public networks
2. **Authentication**: Use Redis password in production environments
3. **Network Security**: Use private networks for Redis communication
4. **Data Encryption**: Consider Redis AUTH and TLS for sensitive data

## Performance Tuning

1. **Multiple Workers**: Run multiple queue workers for better throughput
2. **Queue Priority**: Use different queues for different job priorities
3. **Redis Optimization**: Tune Redis configuration for your workload
4. **Job Batching**: Use job batching for related operations

## Failed Jobs

Failed jobs are stored in the `failed_jobs` table with the following information:
- Job payload
- Exception details
- Failed timestamp
- Unique identifier

Failed jobs can be retried, deleted, or analyzed for debugging purposes.