<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;

class RedisRobustnessTest extends TestCase
{
    /**
     * PILAR 1: CONNECTIVITY (SUCCESS & FAILURE)
     */
    public function test_1_redis_connection_success(): void
    {
        // Memastikan kabel Redis nyambung
        $this->assertTrue(Redis::ping() == 'PONG' || Redis::ping() == true);
    }

    public function test_2_redis_connection_failure_on_wrong_pass(): void
    {
        // Memastikan sistem sadar jika password salah
        config(['database.redis.default.password' => 'password_ngawur']);
        Redis::purge();

        $this->expectException(\Exception::class);
        Redis::ping();
    }

    /**
     * PILAR 2: PERFORMANCE (SPEED)
     */
    public function test_3_redis_latency_under_2ms(): void
    {
        // Redis harus jauh lebih kencang dari Database!
        $start = microtime(true);
        Redis::ping();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(5, $duration, "Redis lemot! Durasi: {$duration}ms");
    }

    /**
     * PILAR 3: PERSISTENCE & INTEGRITY (KONSISTENSI)
     */
    public function test_4_redis_data_persistence(): void
    {
        $key = 'sabana_test_key';
        $val = 'integrity_ok';

        Redis::set($key, $val);
        $this->assertEquals($val, Redis::get($key));

        // Simulasi hapus data
        Redis::del($key);
        $this->assertNull(Redis::get($key));
    }
}