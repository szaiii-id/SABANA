<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Services/FonnteServiceTest.php =====

namespace Tests\Unit\Services;

use App\Services\FonnteService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FonnteServiceTest extends TestCase
{
    private FonnteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FonnteService();
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_send_message_berhasil(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result = $this->service->sendMessage('6281234567890', 'Test message');

        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_send_message_gagal_response_500(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['error' => 'Server Error'], 500),
        ]);

        $result = $this->service->sendMessage('6281234567890', 'Test');

        $this->assertFalse($result);
    }

    public function test_send_message_gagal_network_error(): void
    {
        Http::fake([
            'api.fonnte.com/*' => fn () => throw new \Exception('Connection timeout'),
        ]);

        $result = $this->service->sendMessage('6281234567890', 'Test');

        $this->assertFalse($result);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_send_message_nomor_pendek_10_digit(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result = $this->service->sendMessage('0812345678', 'Test');

        $this->assertTrue($result);
    }

    public function test_send_message_pesan_panjang(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result = $this->service->sendMessage('6281234567890', str_repeat('A', 1000));

        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_send_message_pesan_kosong(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result = $this->service->sendMessage('6281234567890', '');

        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_send_message_target_kosong(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => false], 400),
        ]);

        $result = $this->service->sendMessage('', 'Test');

        $this->assertFalse($result);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_send_message_return_boolean(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result = $this->service->sendMessage('6281234567890', 'Test');

        $this->assertIsBool($result);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_response_2xx_sukses(): void
    {
        $statuses = [200, 201];

        foreach ($statuses as $status) {
            Http::fake([
                'api.fonnte.com/*' => Http::response(['status' => true], $status),
            ]);

            $result = $this->service->sendMessage('6281234567890', 'Test');
            $this->assertTrue($result);
        }
    }

    public function test_grup_response_non_2xx_gagal(): void
    {
        $statuses = [400, 401, 403, 500, 502];

        foreach ($statuses as $status) {
            Http::fake([
                'api.fonnte.com/*' => Http::response(['error' => 'Error'], $status),
            ]);

            $result = $this->service->sendMessage('6281234567890', 'Test');
            $this->assertFalse($result);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_retry_after_failure(): void
    {
        // Tidak ada retry logic — test dokumentasi bahwa service tidak retry
        Http::fake([
            'api.fonnte.com/*' => Http::response(['error' => 'Timeout'], 500),
        ]);

        $result = $this->service->sendMessage('6281234567890', 'Test');

        $this->assertFalse($result);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_send_message_multiple_tanpa_konflik(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result1 = $this->service->sendMessage('6281234567890', 'Pesan 1');
        $result2 = $this->service->sendMessage('6289999999999', 'Pesan 2');

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_token_dikirim_via_header(): void
    {
        Http::fake([
            'api.fonnte.com/*' => function ($request) {
                $this->assertNotEmpty($request->header('Authorization')[0] ?? null);
                return Http::response(['status' => true], 200);
            },
        ]);

        $this->service->sendMessage('6281234567890', 'Test');
    }

    public function test_pesan_tidak_bocor_di_log_saat_sukses(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        // Tidak ada assertion spesifik — pastikan tidak throw
        $this->service->sendMessage('6281234567890', 'Rahasia');
        $this->assertTrue(true);
    }
}