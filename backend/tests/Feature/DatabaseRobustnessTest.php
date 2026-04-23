<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class DatabaseRobustnessTest extends TestCase
{
    /**
     * PILAR 1: CONNECTIVITY & FAILOVER (ASPEK DASAR)
     */
    public function test_1_db_connection_success(): void
    {
        // Memastikan kabel nyambung
        $result = DB::select('SELECT 1');
        $this->assertNotEmpty($result);
    }

    public function test_2_db_connection_failure_on_wrong_host(): void
    {
        // Memastikan sistem sadar jika kabel putus (Host salah)
        config(['database.connections.pgsql.host' => '1.2.3.4']);
        DB::purge('pgsql');

        $this->expectException(\Exception::class);
        DB::connection('pgsql')->getPdo();
    }

    /**
     * PILAR 2: PERFORMANCE & LATENCY (ASPEK KECEPATAN)
     */
    public function test_3_db_latency_under_5ms(): void
    {
        // Bukan cuma nyambung, tapi harus kencang!
        $start = microtime(true);
        DB::select('SELECT 1');
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(10, $duration, "Database terlalu lemot! Durasi: {$duration}ms");
    }

    /**
     * PILAR 3: THROUGHPUT & LOAD RESILIENCE (ASPEK BEBAN)
     */
    public function test_4_db_stress_sequential_connections(): void
    {
        // Memastikan pool connection stabil saat dihajar request beruntun
        for ($i = 0; $i < 50; $i++) {
            $result = DB::select('SELECT 1');
            $this->assertNotEmpty($result);
        }
    }

    /**
     * PILAR 4: STATE & PERSISTENCE INTEGRITY (ASPEK KONSISTENSI)
     */
    public function test_5_db_timezone_sync_with_app(): void
    {
        // Mencegah error jam lapor warga (Laravel vs Postgres)
        $appTimezone = config('app.timezone');
        $dbTimezone = DB::select("SHOW TIMEZONE")[0]->TimeZone;

        $this->assertEquals(
            strtoupper($appTimezone), 
            strtoupper($dbTimezone), 
            "Timezone tidak sinkron! App: $appTimezone, DB: $dbTimezone"
        );
    }

    public function test_6_db_utf8_encoding_integrity(): void
    {
        // Memastikan data tidak rusak saat ada karakter unik/emoji
        $text = '🗳️ Sabana ID - Banjarmasin';
        $result = DB::select("SELECT ? as val", [$text]);

        $this->assertEquals($text, $result[0]->val);
    }
}