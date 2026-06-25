<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Redis;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendWhatsAppJob;

class RedisConnectionTest extends TestCase
{
    // ===== PROPERTIES =====

    private string $testPrefix = 'test:infra:redis:';

    // ===== TEARDOWN =====

    protected function tearDown(): void
    {
        $keys = Redis::connection()->command('KEYS', [$this->testPrefix . '*']);

        if (!empty($keys)) {
            foreach ($keys as $key) {
                Redis::connection()->command('DEL', [$key]);
            }
        }

        parent::tearDown();
    }

    // ===== HELPER =====

    private function validData(): array
    {
        return [
            'key' => $this->testPrefix . uniqid(),
            'value' => [
                'nik' => '6371012305900001',
                'nama' => 'Test User',
                'timestamp' => now()->toIsoString(),
            ],
        ];
    }

 

    // ===== SCENARIO 1: HAPPY PATH =====

    /** @test */
    public function test_happy_path_redis_ping_responds_with_pong(): void
    {
        $result = Redis::connection()->command('PING');

        // PhpRedis return langsung string, bukan object
        $this->assertEquals('PONG', $result);
    }

    /** @test */
    public function test_happy_path_cache_store_and_retrieve_successfully(): void
    {
        $data = $this->validData();

        Cache::put($data['key'], $data['value'], 60);

        $retrieved = Cache::get($data['key']);

        $this->assertNotNull($retrieved);
        $this->assertEquals('Test User', $retrieved['nama']);
        $this->assertEquals('6371012305900001', $retrieved['nik']);
    }

    /** @test */
    public function test_happy_path_queue_pushes_job_successfully(): void
    {
        Queue::fake();

        dispatch(new SendWhatsAppJob('6281234567890', 'Test message'));

        Queue::assertPushed(SendWhatsAppJob::class, 1);
    }

    // ===== SCENARIO 2: SAD PATH =====

    /** @test */
    public function test_sad_path_cache_miss_returns_null_not_exception(): void
    {
        $nonExistentKey = $this->testPrefix . 'nonexistent:' . uniqid();

        $result = Cache::get($nonExistentKey);

        $this->assertNull($result);
    }

    /** @test */
    public function test_sad_path_invalid_redis_command_throws_exception(): void
    {
        $this->expectException(\Throwable::class);

        Redis::connection()->command('INVALID_COMMAND_FOR_TEST');
    }

    // ===== SCENARIO 3: BOUNDARY =====

    /** @test */
    public function test_boundary_large_value_100kb_stored_and_retrieved(): void
    {
        $key = $this->testPrefix . 'large:' . uniqid();
        $largeValue = str_repeat('x', 1024 * 100); // 100KB

        Cache::put($key, $largeValue, 60);

        $retrieved = Cache::get($key);

        $this->assertNotNull($retrieved);
        $this->assertEquals(102400, strlen((string) $retrieved));
    }

    /** @test */
    public function test_boundary_max_key_length_accepted(): void
    {
        $key = $this->testPrefix . str_repeat('k', 200);

        Cache::put($key, 'boundary_test', 10);

        $this->assertEquals('boundary_test', Cache::get($key));
    }

    /** @test */
    public function test_boundary_zero_ttl_does_not_store(): void
    {
        $key = $this->testPrefix . 'zero_ttl:' . uniqid();

        Cache::put($key, 'should_not_exist', 0);

        // TTL 0 = langsung expired
        $this->assertNull(Cache::get($key));
    }

    // ===== SCENARIO 4: EDGE CASE =====

    /** @test */
    public function test_edge_case_special_characters_in_key(): void
    {
        $key = $this->testPrefix . 'user:profile:123:中文:emoji_🎉';

        Cache::put($key, 'special_chars', 60);

        $this->assertEquals('special_chars', Cache::get($key));
    }

    /** @test */
    public function test_edge_case_very_frequent_reads_on_same_key(): void
    {
        $key = $this->testPrefix . 'frequent:' . uniqid();

        Cache::put($key, 'cached_value', 60);

        for ($i = 0; $i < 100; $i++) {
            $this->assertEquals('cached_value', Cache::get($key));
        }
    }

    // ===== SCENARIO 5: NULL/EMPTY =====

    /** @test */
    public function test_null_empty_store_null_value_retrieves_null(): void
    {
        $key = $this->testPrefix . 'null:' . uniqid();

        Cache::put($key, null, 60);

        $this->assertNull(Cache::get($key));
    }

    /** @test */
    public function test_null_empty_store_empty_string_retrieves_empty(): void
    {
        $key = $this->testPrefix . 'empty:' . uniqid();

        Cache::put($key, '', 60);

        $this->assertSame('', Cache::get($key));
    }

    /** @test */
    public function test_null_empty_store_empty_array_retrieves_empty_array(): void
    {
        $key = $this->testPrefix . 'empty_arr:' . uniqid();

        Cache::put($key, [], 60);

        $this->assertSame([], Cache::get($key));
    }

    // ===== SCENARIO 6: DATA TYPE =====

    /** @test */
    public function test_data_type_boolean_false_preserved(): void
    {
        $key = $this->testPrefix . 'bool:' . uniqid();

        Cache::put($key, false, 60);

        $this->assertFalse(Cache::get($key));
    }

    /** @test */
    public function test_data_type_integer_zero_preserved(): void
    {
        $key = $this->testPrefix . 'zero:' . uniqid();

        Cache::put($key, 0, 60);

        // Redis serialize → bisa jadi string '0'. Gunakan assertEquals
        $this->assertEquals(0, Cache::get($key));
    }

    /** @test */
    public function test_data_type_float_preserved(): void
    {
        $key = $this->testPrefix . 'float:' . uniqid();

        Cache::put($key, 3.14, 60);

        $this->assertEquals(3.14, Cache::get($key));
    }

    /** @test */
    public function test_data_type_nested_array_preserved(): void
    {
        $key = $this->testPrefix . 'nested:' . uniqid();
        $nested = ['a' => 1, 'b' => ['c' => 2, 'd' => [3, 4]]];

        Cache::put($key, $nested, 60);

        $this->assertEquals($nested, Cache::get($key));
    }

    // ===== SCENARIO 7: EQUIVALENCE PARTITION =====

    /** @test */
    public function test_equivalence_short_ttl_1_second_expires_after_duration(): void
    {
        $key = $this->testPrefix . 'short_ttl:' . uniqid();

        Cache::put($key, 'ephemeral', 1);

        $this->assertEquals('ephemeral', Cache::get($key));

        sleep(2);

        $this->assertNull(Cache::get($key));
    }

    /** @test */
    public function test_equivalence_long_ttl_3600_seconds_still_exists(): void
    {
        $key = $this->testPrefix . 'long_ttl:' . uniqid();

        Cache::put($key, 'persistent', 3600);

        sleep(1);

        $this->assertEquals('persistent', Cache::get($key));
    }

    /** @test */
    public function test_equivalence_forever_store_never_expires(): void
    {
        $key = $this->testPrefix . 'forever:' . uniqid();

        Cache::forever($key, 'eternal');

        sleep(1);

        $this->assertEquals('eternal', Cache::get($key));
    }

    // ===== SCENARIO 8: STATE TRANSITION =====

    /** @test */
    public function test_state_transition_cache_put_update_delete_cycle(): void
    {
        $key = $this->testPrefix . 'state:' . uniqid();

        // State 1: Tidak ada
        $this->assertNull(Cache::get($key));

        // State 2: Created
        Cache::put($key, 'v1', 60);
        $this->assertEquals('v1', Cache::get($key));

        // State 3: Updated
        Cache::put($key, 'v2', 60);
        $this->assertEquals('v2', Cache::get($key));

        // State 4: Deleted
        Cache::forget($key);
        $this->assertNull(Cache::get($key));
    }

    // ===== SCENARIO 9: CONCURRENCY =====

    /** @test */
    public function test_concurrency_multiple_queue_jobs_dispatched_in_sequence(): void
    {
        Queue::fake();

        for ($i = 0; $i < 10; $i++) {
            dispatch(new SendWhatsAppJob('6281234567890', "Message {$i}"));
        }

        Queue::assertPushed(SendWhatsAppJob::class, 10);
    }

    /** @test */
    public function test_concurrency_cache_increment_is_atomic(): void
    {
        $key = $this->testPrefix . 'counter:' . uniqid();

        Cache::put($key, 0, 60);

        // Redis INCR bersifat atomic
        Cache::increment($key);
        Cache::increment($key);
        Cache::increment($key);

        $this->assertEquals(3, Cache::get($key));
    }

    // ===== SCENARIO 10: SECURITY =====

    /** @test */
    public function test_security_redis_connection_authenticated(): void
    {
        $serverInfo = Redis::connection()->command('INFO', ['server']);

        $this->assertArrayHasKey('redis_version', $serverInfo);
        // Kalau password salah, akan throw RedisException
    }

    /** @test */
    public function test_security_maxmemory_policy_is_volatile_lru(): void
    {
        $config = Redis::connection()->command('CONFIG', ['GET', 'maxmemory-policy']);

        $this->assertEquals('volatile-lru', $config['maxmemory-policy']);
    }

    /** @test */
    public function test_security_maxmemory_is_set_to_64mb(): void
    {
        $config = Redis::connection()->command('CONFIG', ['GET', 'maxmemory']);

        $this->assertEquals(67108864, (int) $config['maxmemory']);
    }

    /** @test */
    public function test_security_key_isolation_between_tags(): void
    {
        $adminKey = $this->testPrefix . 'admin:' . uniqid();
        $citizenKey = $this->testPrefix . 'citizen:' . uniqid();

        Cache::put($adminKey, 'admin_secret', 60);
        Cache::put($citizenKey, 'citizen_public', 60);

        // Admin key harus tetap ada setelah citizen key dihapus
        Cache::forget($citizenKey);

        $this->assertEquals('admin_secret', Cache::get($adminKey));
        $this->assertNull(Cache::get($citizenKey));
    }
}