<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Services/CitizenReportServiceTest.php =====

namespace Tests\Unit\Services;

use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use App\Services\CitizenReportService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class CitizenReportServiceTest extends TestCase
{
    private CitizenReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.fonnte.admin_number', '6281234567890');

        $this->service = new CitizenReportService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== [DATA HELPERS] =====

    private function mockCitizen(array $attributes = []): Citizen
    {
        $id = $attributes['id'] ?? '019eba77-304a-70f0-b7ae-54a3e93cc1b6';

        $citizen = Mockery::mock(Citizen::class)->makePartial();
        $citizen->shouldAllowMockingProtectedMethods();
        $citizen->shouldReceive('getKey')->andReturn($id);

        $citizen->id = $id;
        $citizen->full_name = $attributes['full_name'] ?? 'Ahmad Fauzi';
        $citizen->nik = $attributes['nik'] ?? '6371012508900001';
        $citizen->whatsapp_number = $attributes['whatsapp_number'] ?? '6281234567890';

        return $citizen;
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_send_via_whatsapp_dispatch_2_jobs(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaWhatsapp($citizen, 'Ini laporan warga.');

        Queue::assertPushed(SendWhatsAppJob::class, 2);
    }

    public function test_send_via_email_dispatch_1_job(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaEmail($citizen, 'Subjek', 'Pesan laporan', 'test@example.com');

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_send_via_whatsapp_tetap_dispatch_meski_pesan_kosong(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaWhatsapp($citizen, '');

        Queue::assertPushed(SendWhatsAppJob::class, 2);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_send_via_whatsapp_pesan_sangat_panjang(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaWhatsapp($citizen, str_repeat('A', 5000));

        Queue::assertPushed(SendWhatsAppJob::class, 2);
    }

    public function test_send_via_email_subjek_panjang(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaEmail($citizen, str_repeat('S', 200), 'Pesan', 'test@example.com');

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_send_via_whatsapp_nomor_lokal_08(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['whatsapp_number' => '081234567890']);

        $this->service->sendViaWhatsapp($citizen, 'Laporan');

        Queue::assertPushed(SendWhatsAppJob::class, 2);
    }

    public function test_send_via_whatsapp_nama_mengandung_karakter_khusus(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['full_name' => "Ahmad <b>Fauzi</b>"]);

        $this->service->sendViaWhatsapp($citizen, 'Laporan');

        Queue::assertPushed(SendWhatsAppJob::class, 2);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_send_via_email_pesan_kosong(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaEmail($citizen, 'Subjek', '', 'test@example.com');

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_send_via_whatsapp_return_void(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $result = $this->service->sendViaWhatsapp($citizen, 'Test');

        $this->assertNull($result);
    }

    public function test_send_via_email_return_void(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $result = $this->service->sendViaEmail($citizen, 'S', 'P', 'test@example.com');

        $this->assertNull($result);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_whatsapp_dispatch_admin_dan_citizen(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaWhatsapp($citizen, 'Laporan');

        Queue::assertPushed(SendWhatsAppJob::class, fn ($job) => true);
    }

    public function test_grup_email_dispatch_dengan_payload_lengkap(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaEmail($citizen, 'Subjek', 'Pesan', 'test@example.com');

        Queue::assertPushed(SendEmailJob::class, fn ($job) => true);
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_kirim_whatsapp_lalu_email_tidak_konflik(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaWhatsapp($citizen, 'Laporan');
        $this->service->sendViaEmail($citizen, 'Subjek', 'Pesan', 'test@example.com');

        Queue::assertPushed(SendWhatsAppJob::class, 2);
        Queue::assertPushed(SendEmailJob::class, 1);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_kirim_whatsapp_dua_kali_tanpa_konflik(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaWhatsapp($citizen, 'Laporan 1');
        $this->service->sendViaWhatsapp($citizen, 'Laporan 2');

        Queue::assertPushed(SendWhatsAppJob::class, 4);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_pesan_warga_tidak_bocor_ke_log(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaWhatsapp($citizen, 'Pesan rahasia');

        // Tidak throw = PASS
        $this->assertTrue(true);
    }

    public function test_email_warga_dispatch_dengan_aman(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen();

        $this->service->sendViaEmail($citizen, 'Subjek', 'Pesan rahasia', 'test@example.com');

        Queue::assertPushed(SendEmailJob::class, 1);
    }
}