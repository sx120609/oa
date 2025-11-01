<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = false;
        $cache = false;
        $redis = false;

        try {
            DB::connection()->getPdo();
            $database = true;
        } catch (\Throwable $e) {
            $database = false;
        }

        $cacheStore = config('cache.default');

        try {
            $cache = Cache::store(is_string($cacheStore) ? $cacheStore : null)->set('healthcheck', 'ok', 1);
        } catch (\Throwable $e) {
            $cache = false;
        }

        if (class_exists(\Redis::class)) {
            try {
                $redis = Redis::connection()->ping() === 'PONG';
            } catch (\Throwable $e) {
                $redis = false;
            }
        }

        return response()->json([
            'status' => $database && $cache && $redis ? 'ok' : 'degraded',
            'checks' => [
                'database' => (bool) $database,
                'cache' => (bool) $cache,
                'redis' => (bool) $redis,
            ],
        ]);
    }
}
