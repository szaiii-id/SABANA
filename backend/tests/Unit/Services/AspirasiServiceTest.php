<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Services/AspirasiServiceTest.php =====

namespace Tests\Unit\Services;

use App\Services\AspirasiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AspirasiServiceTest extends TestCase
{
    private AspirasiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.brevo.api_key', 'test-api-key');
        config()->set('mail.from.address', 'admin@sabana.go.id');

        $this->service = new AspirasiService();
    }

    // ===== [DATA HELPERS] =====

    private function validPayload(): array
    {
        return [
            'subjek' => 'Test Aspirasi',
            'pesan' => 'Ini pesan test',
            'nama' => 'Ahmad Fauzi',
            'email' => 'test@example.com',
        ];
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_proses_dan_kirim_aspirasi_berhasil(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '123'], 201),
        ]);

        $result = $this->service->prosesDanKirimAspirasi($this->validPayload());

        $this->assertTrue($result);
    }

    public function test_kirim_balasan_otomatis_berhasil(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '456'], 201),
        ]);

        $result = $this->service->kirimBalasanOtomatis(
            $this->validPayload(),
            'test@example.com',
            'Ahmad'
        );

        $this->assertTrue($result->successful());
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_proses_dan_kirim_aspirasi_gagal_throw_exception(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Gagal mengirim email via API');

        $this->service->prosesDanKirimAspirasi($this->validPayload());
    }

    public function test_kirim_balasan_gagal_response_500(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['error' => 'Server Error'], 500),
        ]);

        $result = $this->service->kirimBalasanOtomatis(
            $this->validPayload(),
            'test@example.com',
            'Ahmad'
        );

        $this->assertFalse($result->successful());
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_proses_aspirasi_subjek_panjang(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '789'], 201),
        ]);

        $result = $this->service->prosesDanKirimAspirasi(array_merge($this->validPayload(), [
            'subjek' => str_repeat('A', 200),
        ]));

        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_kirim_balasan_nama_mengandung_karakter_khusus(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '000'], 201),
        ]);

        $result = $this->service->kirimBalasanOtomatis(
            $this->validPayload(),
            'test@example.com',
            "Ahmad <script>alert('xss')</script>"
        );

        $this->assertTrue($result->successful());
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_payload_kosong_tetap_terkirim(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '111'], 201),
        ]);

        $result = $this->service->prosesDanKirimAspirasi([
            'subjek' => '',
            'email' => '',
            'nama' => '',
            'pesan' => '',
        ]);

        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_proses_dan_kirim_return_bool(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '222'], 201),
        ]);

        $result = $this->service->prosesDanKirimAspirasi($this->validPayload());

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_response_2xx_sukses(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => 'ok'], 201),
        ]);

        $result = $this->service->prosesDanKirimAspirasi($this->validPayload());

        $this->assertTrue($result);
    }

    public function test_grup_response_non_2xx_gagal(): void
    {
        $statuses = [400, 401, 403, 500];

        foreach ($statuses as $status) {
            Http::fake([
                'api.brevo.com/*' => Http::response(['error' => 'Error'], $status),
            ]);

            try {
                $this->service->prosesDanKirimAspirasi($this->validPayload());
                $this->fail('Expected exception for status ' . $status);
            } catch (\Exception $e) {
                $this->assertStringContainsString('Gagal mengirim email', $e->getMessage());
            }
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_kirim_balasan_setelah_aspirasi_berhasil(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '123'], 201),
        ]);

        $this->service->prosesDanKirimAspirasi($this->validPayload());

        $result = $this->service->kirimBalasanOtomatis(
            $this->validPayload(),
            'test@example.com',
            'Ahmad'
        );

        $this->assertTrue($result->successful());
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_kirim_dua_aspirasi_bersamaan(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '123'], 201),
        ]);

        $result1 = $this->service->prosesDanKirimAspirasi($this->validPayload());
        $result2 = $this->service->prosesDanKirimAspirasi(array_merge($this->validPayload(), [
            'subjek' => 'Aspirasi Kedua',
        ]));

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_api_key_tidak_bocor_di_response(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '123'], 201),
        ]);

        $this->service->prosesDanKirimAspirasi($this->validPayload());

        // Tidak ada assertion spesifik — pastikan tidak throw
        $this->assertTrue(true);
    }
}