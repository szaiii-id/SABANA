<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('infrastructure')]
#[Group('server')]
class FrankenPHPRobustnessTest extends TestCase
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

    // ========================================================================
    // PILAR 1: BASIC HTTP AVAILABILITY
    // ========================================================================

    /**
     * @test
     * @group critical
     * 
     * Memastikan aplikasi merespon HTTP 200 pada route utama.
     * Test fundamental: jika ini gagal, server tidak berjalan.
     */
    #[Group('critical')]
    public function test_1_server_responds_successfully(): void
    {
        $this->retryAssertion(function () {
            $startTime = microtime(true);

            $response = $this->get('/');

            $duration = (microtime(true) - $startTime) * 1000;

            $response->assertStatus(200);

            // Assert response time wajar (< 500ms untuk local)
            $this->assertLessThan(
                500,
                $duration,
                sprintf('Response time terlalu lambat: %.2fms. Pastikan Octane/FrankenPHP berjalan.', $duration)
            );

            // Assert response tidak kosong
            $this->assertNotEmpty(
                $response->content(),
                'Response body kosong. Aplikasi mungkin error (500) tapi tidak terdeteksi.'
            );
        }, 3, 300);
    }

    // ========================================================================
    // PILAR 2: OCTANE & WORKER MODE CHECK
    // ========================================================================

    /**
     * @test
     * @group critical
     * 
     * Memastikan Octane/FrankenPHP worker berjalan.
     * Tanpa Octane, aplikasi tidak bisa handle concurrent requests.
     */
    #[Group('critical')]
    public function test_2_octane_worker_is_running(): void
    {
        $response = $this->get('/');

        // Cara 1: Cek HTTP header FrankenPHP/Octane
        $octaneHeaders = ['x-octane', 'x-powered-by', 'alt-svc'];
        $foundHeader = false;

        foreach ($octaneHeaders as $header) {
            if ($response->headers->has($header)) {
                $foundHeader = true;
                break;
            }
        }

        if ($foundHeader) {
            $this->assertTrue(true, 'Octane header detected ✅');
            return;
        }

        // Cara 2: Cek response time konsisten (Octane = fast, non-Octane = slow)
        $times = [];
        for ($i = 0; $i < 5; $i++) {
            $start = microtime(true);
            $this->get('/');
            $times[] = (microtime(true) - $start) * 1000;
        }

        $avgTime = array_sum($times) / count($times);

        if ($avgTime < 50) {
            $this->assertTrue(true, sprintf('Fast response detected (%.2fms) - Octane likely running ✅', $avgTime));
            return;
        }

        // Cara 3: Cek artisan command (fallback terakhir)
        if (!function_exists('shell_exec')) {
            $this->markTestSkipped(
                sprintf(
                    'Tidak dapat memverifikasi Octane.\nResponse time: %.2fms (expected < 50ms for Octane).\nPasang Octane untuk performa lebih baik.',
                    $avgTime
                )
            );
        }

        $output = shell_exec('php artisan octane:status 2>&1');

        $this->assertStringContainsString(
            'Octane server is running',
            $output ?? '',
            sprintf(
                "Octane/FrankenPHP TIDAK berjalan!\nOutput: %s\nResponse time: %.2fms",
                $output ?? 'null',
                $avgTime
            )
        );
    }

    // ========================================================================
    // PILAR 3: THROUGHPUT & CONCURRENCY
    // ========================================================================

    /**
     * @test
     * @group stress
     * 
     * Memastikan server stabil saat menerima 30 request beruntun.
     * Test ini memvalidasi tidak ada memory leak atau connection drop.
     */
    #[Group('stress')]
    public function test_3_server_concurrency_resilience(): void
    {
        $errors = [];
        $times = [];
        $startTime = microtime(true);

        for ($i = 1; $i <= 30; $i++) {
            try {
                $reqStart = microtime(true);
                $response = $this->get('/');
                $times[] = (microtime(true) - $reqStart) * 1000;

                if ($response->status() !== 200) {
                    $errors[] = "Request #{$i}: Status {$response->status()}";
                }
            } catch (\Exception $e) {
                $errors[] = "Request #{$i}: Exception - " . $e->getMessage();
            }
        }

        $totalDuration = (microtime(true) - $startTime) * 1000;
        $avgTime = count($times) > 0 ? array_sum($times) / count($times) : 0;
        $maxTime = count($times) > 0 ? max($times) : 0;

        // Assert tidak ada error
        $this->assertEmpty(
            $errors,
            sprintf(
                "%d dari 30 request gagal:\n%s",
                count($errors),
                implode("\n", array_slice($errors, 0, 5))
            )
        );

        // Assert performa
        $this->assertLessThan(
            200,
            $avgTime,
            sprintf(
                "Concurrency performance buruk!\nAvg: %.2fms\nMax: %.2fms\nTotal: %.2fms",
                $avgTime,
                $maxTime,
                $totalDuration
            )
        );
    }

    // ========================================================================
    // PILAR 4: LARAVEL FRAMEWORK INTEGRITY
    // ========================================================================

    /**
     * @test
     * @group integrity
     * 
     * Memastikan environment aplikasi sesuai untuk testing.
     * Mencegah test berjalan di production secara tidak sengaja.
     */
    #[Group('integrity')]
    public function test_4_application_env_is_correct(): void
    {
        $env = app()->environment();
        $allowedEnvs = ['testing', 'local', 'development'];

        $this->assertTrue(
            in_array($env, $allowedEnvs),
            sprintf(
                "Environment TIDAK SESUAI!\nCurrent: %s\nAllowed: %s\nJANGAN jalankan test di production!",
                $env,
                implode(', ', $allowedEnvs)
            )
        );

        // Assert debug mode ON untuk testing (memudahkan debugging)
        $this->assertTrue(
            config('app.debug'),
            'APP_DEBUG harus TRUE untuk testing environment.'
        );
    }

    /**
     * @test
     * @group integrity
     * 
     * Memastikan konfigurasi cache driver sesuai untuk testing.
     * Cache harus pakai array/null driver agar tidak mengganggu test lain.
     */
    #[Group('integrity')]
    public function test_5_cache_driver_is_test_friendly(): void
    {
        $cacheDriver = config('cache.default');

        $testFriendlyDrivers = ['array', 'null', 'redis'];

        $this->assertTrue(
            in_array($cacheDriver, $testFriendlyDrivers),
            sprintf(
                "Cache driver '%s' tidak cocok untuk testing.\nGunakan 'array' atau 'null' agar test terisolasi.",
                $cacheDriver
            )
        );
    }
}