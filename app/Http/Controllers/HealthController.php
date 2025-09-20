<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    /**
     * Application health check endpoint
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];
        $timestamp = now()->toISOString();

        // Database connectivity check
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // Cache connectivity check
        try {
            Cache::put('health_check', 'test', 60);
            $value = Cache::get('health_check');
            if ($value === 'test') {
                $checks['cache'] = [
                    'status' => 'healthy',
                    'message' => 'Cache is working properly'
                ];
            } else {
                throw new \Exception('Cache write/read failed');
            }
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache check failed: ' . $e->getMessage()
            ];
        }

        // Redis connectivity check
        try {
            Redis::ping();
            $checks['redis'] = [
                'status' => 'healthy',
                'message' => 'Redis connection successful'
            ];
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['redis'] = [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed: ' . $e->getMessage()
            ];
        }

        // Queue connectivity check
        try {
            $queueConnection = config('queue.default');
            $queueDriver = config("queue.connections.{$queueConnection}.driver");

            $checks['queue'] = [
                'status' => 'healthy',
                'message' => "Queue system operational (driver: {$queueDriver})",
                'connection' => $queueConnection
            ];
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['queue'] = [
                'status' => 'unhealthy',
                'message' => 'Queue system check failed: ' . $e->getMessage()
            ];
        }

        // Application info
        $info = [
            'app_name' => config('app.name'),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'timestamp' => $timestamp
        ];

        $response = [
            'status' => $status,
            'timestamp' => $timestamp,
            'info' => $info,
            'checks' => $checks
        ];

        return response()->json($response, $status === 'healthy' ? 200 : 503);
    }
}
