<?php

namespace App\Console\Commands;

use App\Jobs\TestQueueJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class TestQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:test 
                            {--redis-check : Test Redis connectivity}
                            {--dispatch= : Dispatch test jobs with message}';

    /**
     * The console command description.
     */
    protected $description = 'Test queue system connectivity and functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Queue System Configuration');
        $this->newLine();

        // Test Redis connectivity if requested
        if ($this->option('redis-check')) {
            $this->testRedisConnectivity();
        }

        // Test queue configuration
        $this->testQueueConfiguration();

        // Dispatch test job if requested
        if ($message = $this->option('dispatch')) {
            $this->dispatchTestJob($message);
        }

        return 0;
    }

    private function testRedisConnectivity(): void
    {
        $this->info('🔄 Testing Redis connectivity...');

        try {
            Redis::ping();
            $this->info('✅ Redis connection successful');

            // Test basic Redis operations
            Redis::set('queue_test', 'test_value', 'EX', 60);
            $value = Redis::get('queue_test');

            if ($value === 'test_value') {
                $this->info('✅ Redis read/write operations successful');
            } else {
                $this->warn('⚠️  Redis read/write operations failed');
            }
        } catch (\Exception $e) {
            $this->error('❌ Redis connection failed: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function testQueueConfiguration(): void
    {
        $this->info('📋 Queue Configuration Details:');

        $queueConnection = config('queue.default');
        $this->info("Default connection: {$queueConnection}");

        $queueConfig = config("queue.connections.{$queueConnection}");
        $this->info("Driver: {$queueConfig['driver']}");

        if ($queueConfig['driver'] === 'redis') {
            $this->info("Redis queue: {$queueConfig['queue']}");
            $this->info("Redis connection: {$queueConfig['connection']}");
            $this->info("Retry after: {$queueConfig['retry_after']} seconds");
        }

        $this->newLine();
    }

    private function dispatchTestJob(string $message): void
    {
        $this->info("🚀 Dispatching test job with message: {$message}");

        try {
            TestQueueJob::dispatch($message);
            $this->info('✅ Test job dispatched successfully');
            $this->info('💡 Monitor your logs to see when the job is processed');
        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch test job: ' . $e->getMessage());
        }
    }
}
