<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;

#[Group('infrastructure')]
#[Group('database')]
class DatabaseRobustnessTest extends TestCase
{
    /**
     * Retry helper: mengulang assertion beberapa kali sebelum menyerah.
     * Berguna untuk test yang bergantung pada network/stability.
     */
    private function retryAssertion(callable $callback, int $maxAttempts = 3, int $delayMs = 200): void
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $callback();
                return; // Sukses, keluar
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                $lastException = $e;
                if ($attempt < $maxAttempts) {
                    usleep($delayMs * 1000); // Tunggu sebelum retry
                }
            }
        }

        // Jika semua attempt gagal, lempar exception terakhir
        throw $lastException;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.pgsql.host' => env('DB_HOST', 'postgres')]);
        DB::purge('pgsql');
        DB::reconnect('pgsql');
    }

    protected function tearDown(): void
    {
        config(['database.connections.pgsql.host' => env('DB_HOST', 'postgres')]);
        DB::purge('pgsql');
        DB::reconnect('pgsql');
        parent::tearDown();
    }

    // ========================================================================
    // PILAR 1: CONNECTIVITY & FAILOVER
    // ========================================================================

    #[Group('critical')]
    public function test_1_db_connection_success(): void
    {
        $this->retryAssertion(function () {
            $result = DB::select('SELECT 1 AS connected');
            $this->assertNotEmpty($result, 'Database tidak mengembalikan response.');
            $this->assertEquals(1, $result[0]->connected);
        }, 3, 200);
    }

    #[Group('resilience')]
    public function test_2_db_connection_failure_on_wrong_host(): void
    {
        // Gunakan host yang pasti tidak ada di network Docker
        config(['database.connections.pgsql.host' => 'host-tidak-ada.internal']);
        DB::purge('pgsql');

        $startTime = microtime(true);

        try {
            // Timeout eksplisit di level koneksi
            DB::connection('pgsql')->getPdo();
            $this->fail('Seharusnya exception terlempar untuk host invalid.');
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->assertLessThan(
                5000,
                $duration,
                sprintf(
                    'Timeout detection: %.2fms. Exception: %s',
                    $duration,
                    $e->getMessage()
                )
            );

            // Assert bahwa exception yang dilempar adalah connection error
            $this->assertMatchesRegularExpression(
                '/(could not be reached|could not translate|not known|Name or service)/i',
                $e->getMessage(),
                sprintf('Exception message tidak sesuai: %s', $e->getMessage())
            );
        }
    }
    

    // ========================================================================
    // PILAR 2: PERFORMANCE & LATENCY
    // ========================================================================

    #[Group('performance')]
    public function test_3_db_latency_baseline(): void
    {
        $samples = [];

        for ($i = 0; $i < 5; $i++) {
            $start = microtime(true);
            DB::select('SELECT 1');
            $samples[] = (microtime(true) - $start) * 1000;
        }

        $averageLatency = array_sum($samples) / count($samples);
        $maxLatency = max($samples);
        $minLatency = min($samples);

        $this->assertLessThan(
            10,
            $averageLatency,
            sprintf(
                "Database latency terlalu tinggi!\nRata-rata: %.2fms\nMax: %.2fms\nMin: %.2fms",
                $averageLatency,
                $maxLatency,
                $minLatency
            )
        );
    }

    // ========================================================================
    // PILAR 3: THROUGHPUT & LOAD RESILIENCE
    // ========================================================================

    #[Group('stress')]
    public function test_4_db_connection_pool_stability(): void
    {
        $errors = [];
        $startTime = microtime(true);

        for ($i = 1; $i <= 50; $i++) {
            try {
                $result = DB::select('SELECT 1');
                $this->assertNotEmpty($result);
            } catch (\Exception $e) {
                $errors[] = "Request #{$i} gagal: " . $e->getMessage();
            }
        }

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertEmpty(
            $errors,
            sprintf("%d dari 50 request gagal:\n%s", count($errors), implode("\n", array_slice($errors, 0, 5)))
        );

        $this->assertLessThan(
            5000,
            $duration,
            sprintf("50 sequential requests terlalu lambat: %.2fms", $duration)
        );
    }

    // ========================================================================
    // PILAR 4: STATE & PERSISTENCE INTEGRITY
    // ========================================================================

    #[Group('integrity')]
    public function test_5_db_timezone_sync_with_app(): void
    {
        $appTimezone = config('app.timezone');

        $dbResult = DB::select("SHOW TIMEZONE");
        $this->assertNotEmpty($dbResult, 'Query SHOW TIMEZONE tidak mengembalikan hasil.');

        $dbTimezone = $dbResult[0]->TimeZone;

        $this->assertEquals(
            strtoupper($appTimezone),
            strtoupper($dbTimezone),
            sprintf(
                "Timezone MISMATCH!\nApp: %s\nDB:  %s\nFix: Set APP_TIMEZONE=%s di .env",
                $appTimezone,
                $dbTimezone,
                $dbTimezone
            )
        );
    }

    #[Group('integrity')]
    public function test_6_db_utf8_encoding_integrity(): void
    {
        $testCases = [
            'emoji'   => '🗳️ Sabana ID - Banjarmasin 🏠',
            'arabic'  => 'بسم الله الرحمن الرحيم',
            'banjar'  => 'Kada tapi ulun handak mendaftar bantuan',
            'special' => 'NIK: 63°01\'23" N, KK: <>&"\'',
        ];

        $errors = [];

        foreach ($testCases as $type => $text) {
            try {
                $result = DB::select("SELECT ? AS val", [$text]);
                if ($result[0]->val !== $text) {
                    $errors[] = "{$type}: Encoding RUSAK!\nExpected: {$text}\nGot: {$result[0]->val}";
                }
            } catch (\Exception $e) {
                $errors[] = "{$type}: Exception - " . $e->getMessage();
            }
        }

        $this->assertEmpty(
            $errors,
            sprintf("UTF-8 Encoding Test GAGAL:\n%s", implode("\n\n", $errors))
        );
    }
}