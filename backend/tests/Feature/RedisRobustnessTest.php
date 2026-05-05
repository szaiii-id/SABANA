<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Group;

#[Group('infrastructure')]
#[Group('redis')]
class RedisRobustnessTest extends TestCase
{
    /**
     * Retry helper untuk test yang bergantung pada network/stability.
     */
    private function retryAssertion(callable $callback, int $maxAttempts = 3, int $delayMs = 200): void
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $callback();
                return;
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                $lastException = $e;
                if ($attempt < $maxAttempts) {
                    usleep($delayMs * 1000);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Kembalikan konfigurasi Redis ke default setelah test.
     */
    protected function tearDown(): void
    {
        config(['database.redis.default.password' => env('REDIS_PASSWORD', null)]);
        Redis::purge();
        parent::tearDown();
    }

    // ========================================================================
    // PILAR 1: CONNECTIVITY
    // ========================================================================

    /**
     * @test
     * @group critical
     * 
     * Memastikan Redis dapat dijangkau dan merespon PING.
     */
    #[Group('critical')]
    public function test_1_redis_connection_success(): void
    {
        $this->retryAssertion(function () {
            $result = Redis::ping();

            $this->assertTrue(
                $result === 'PONG' || $result === true,
                sprintf(
                    "Redis PING gagal!\nPassword: %s\nHost: %s\nPort: %s",
                    config('database.redis.default.password') ? 'SET' : 'EMPTY',
                    config('database.redis.default.host'),
                    config('database.redis.default.port')
                )
            );
        }, 3, 200);
    }

    /**
     * @test
     * @group resilience
     * 
     * Memastikan sistem melempar exception ketika password Redis salah.
     */
    #[Group('resilience')]
    public function test_2_redis_connection_failure_on_wrong_pass(): void
    {
        config(['database.redis.default.password' => 'password_ngawur_xyz']);
        Redis::purge();

        $startTime = microtime(true);

        try {
            Redis::ping();
            $this->fail('Seharusnya exception terlempar untuk password salah.');
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            // Assert exception terjadi dalam waktu wajar
            $this->assertLessThan(
                5000,
                $duration,
                sprintf('Timeout detection terlalu lambat: %.2fms. Exception: %s', $duration, $e->getMessage())
            );

            $this->assertMatchesRegularExpression(
                '/(auth|password|invalid|wrong|denied)/i',
                $e->getMessage(),
                sprintf('Exception type tidak sesuai: %s', $e->getMessage())
            );
        }
    }

    // ========================================================================
    // PILAR 2: PERFORMANCE
    // ========================================================================

    /**
     * @test
     * @group performance
     * 
     * Mengukur latency dasar Redis.
     * Threshold: < 5ms untuk local Docker network.
     */
    #[Group('performance')]
    public function test_3_redis_latency_baseline(): void
    {
        $samples = [];

        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);
            Redis::ping();
            $samples[] = (microtime(true) - $start) * 1000;
        }

        $averageLatency = array_sum($samples) / count($samples);
        $maxLatency = max($samples);
        $minLatency = min($samples);
        $p95 = $samples[(int)(count($samples) * 0.95)] ?? $maxLatency;

        $this->assertLessThan(
            5,
            $averageLatency,
            sprintf(
                "Redis latency terlalu tinggi!\nAvg: %.2fms\nP95: %.2fms\nMax: %.2fms\nMin: %.2fms",
                $averageLatency,
                $p95,
                $maxLatency,
                $minLatency
            )
        );
    }

    // ========================================================================
    // PILAR 3: PERSISTENCE & INTEGRITY
    // ========================================================================

    /**
     * @test
     * @group integrity
     * 
     * Memastikan data dapat disimpan, dibaca, dan dihapus dengan benar.
     */
    #[Group('integrity')]
    public function test_4_redis_data_persistence(): void
    {
        $key = 'sabana:test:persistence_' . uniqid();
        $val = 'integrity_ok_' . date('YmdHis');

        // SET
        Redis::set($key, $val);
        $this->assertEquals($val, Redis::get($key), 'Redis SET/GET tidak konsisten.');

        // EXISTS
        $this->assertEquals(1, Redis::exists($key), 'Redis EXISTS gagal.');

        // DEL
        Redis::del($key);
        $this->assertNull(Redis::get($key), 'Redis DEL gagal - data masih ada setelah dihapus.');
        $this->assertEquals(0, Redis::exists($key), 'Redis EXISTS setelah DEL harus 0.');
    }

    /**
     * @test
     * @group integrity
     * 
     * Memastikan TTL (Time-To-Live) berfungsi dengan benar.
     */
    #[Group('integrity')]
    public function test_5_redis_ttl_functionality(): void
    {
        $key = 'sabana:test:ttl_' . uniqid();
        $val = 'expire_test';

        // SET dengan EXPIRE 2 detik
        Redis::setex($key, 2, $val);

        // Harus ada sebelum expired
        $this->assertEquals($val, Redis::get($key), 'Data harus ada sebelum TTL habis.');

        // Tunggu 3 detik
        sleep(3);

        // Harus hilang setelah expired
        $this->assertNull(Redis::get($key), 'Redis TTL tidak berfungsi - data masih ada setelah expired.');
    }

    /**
     * @test
     * @group performance
     * 
     * Memastikan Redis dapat menangani multiple keys sekaligus.
     */
    #[Group('performance')]
    public function test_6_redis_bulk_operations(): void
    {
        $prefix = 'sabana:test:bulk_';
        $data = [];

        // SET 50 keys
        for ($i = 0; $i < 50; $i++) {
            $key = $prefix . $i;
            $val = 'value_' . $i;
            Redis::set($key, $val);
            $data[$key] = $val;
        }

        // GET 50 keys
        $errors = [];
        foreach ($data as $key => $expectedVal) {
            $actualVal = Redis::get($key);
            if ($actualVal !== $expectedVal) {
                $errors[] = "{$key}: expected '{$expectedVal}', got '{$actualVal}'";
            }
        }

        // Cleanup
        foreach ($data as $key => $val) {
            Redis::del($key);
        }

        $this->assertEmpty(
            $errors,
            sprintf("Bulk operation gagal pada %d keys:\n%s", count($errors), implode("\n", array_slice($errors, 0, 5)))
        );
    }
}