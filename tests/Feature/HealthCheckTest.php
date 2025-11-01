<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_ok_status_when_dependencies_healthy(): void
    {
        if (! class_exists(\Redis::class)) {
            $this->markTestSkipped('Redis extension not installed.');
        }

        DB::shouldReceive('connection->getPdo')->once()->andReturn(true);
        Cache::shouldReceive('store')->once()->with(config('cache.default'))->andReturnSelf();
        Cache::shouldReceive('set')->once()->with('healthcheck', 'ok', 1)->andReturnTrue();
        Redis::shouldReceive('connection')->once()->andReturnSelf();
        Redis::shouldReceive('ping')->once()->andReturn('PONG');

        $response = $this->getJson('/api/healthz');

        $response->assertOk()->assertJson([
            'status' => 'ok',
            'checks' => [
                'database' => true,
                'cache' => true,
                'redis' => true,
            ],
        ]);
    }

    public function test_health_endpoint_handles_missing_dependencies(): void
    {
        DB::shouldReceive('connection->getPdo')->once()->andReturn(true);
        Cache::shouldReceive('store')->once()->with(config('cache.default'))->andReturnSelf();
        Cache::shouldReceive('set')->once()->with('healthcheck', 'ok', 1)->andReturnTrue();

        if (class_exists(\Redis::class)) {
            Redis::shouldReceive('connection')->once()->andThrow(new RuntimeException('redis down'));
        }

        $response = $this->getJson('/api/healthz');

        $response->assertOk()->assertJson([
            'status' => 'degraded',
            'checks' => [
                'database' => true,
                'cache' => true,
                'redis' => false,
            ],
        ]);
    }
}
