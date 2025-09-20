<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The job should time out after this many seconds
     */
    public int $timeout = 60;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public string $message = 'Test queue job executed successfully'
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('TestQueueJob executed', [
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
            'queue_driver' => config('queue.default')
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TestQueueJob failed', [
            'message' => $this->message,
            'exception' => $exception->getMessage(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
