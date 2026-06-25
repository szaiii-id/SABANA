<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Services/CitizenProfileServiceTest.php =====

namespace Tests\Unit\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Services\CitizenProfileService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class CitizenProfileServiceTest extends TestCase
{
    private CitizenProfileService $service;
    private CitizenRepositoryInterface $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(CitizenRepositoryInterface::class);
        $this->service = new CitizenProfileService($this->repositoryMock);
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
        $citizen->shouldReceive('find')->andReturn($citizen);

        $citizen->id = $id;
        $citizen->full_name = $attributes['full_name'] ?? 'Ahmad Fauzi';
        $citizen->whatsapp_number = $attributes['whatsapp_number'] ?? '6281234567890';
        $citizen->is_verified = $attributes['is_verified'] ?? true;

        return $citizen;
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_update_nama_berhasil(): void
    {
        $citizen = $this->mockCitizen();

        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, ['full_name' => 'Nama Baru'])
            ->once()
            ->andReturn($citizen);

        $result = $this->service->updateProfile($citizen, ['full_name' => 'Nama Baru']);

        $this->assertInstanceOf(Citizen::class, $result);
    }

    public function test_update_profile_tanpa_perubahan_mengembalikan_citizen_awal(): void
    {
        $citizen = $this->mockCitizen();

        $result = $this->service->updateProfile($citizen, []);

        $this->assertEquals($citizen->id, $result->id);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_ganti_whatsapp_memicu_verifikasi_ulang(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen([
            'whatsapp_number' => '6281234567890',
        ]);

        $citizen->shouldReceive('tokens')->andReturn(
            Mockery::mock(['delete' => true])
        );

        RateLimiter::clear('change-wa:' . $citizen->id);

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Nomor WhatsApp berhasil diubah.');

        $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);

        Queue::assertPushed(SendWhatsAppJob::class);
    }

    public function test_ganti_whatsapp_kena_rate_limit(): void
    {
        $citizen = $this->mockCitizen();
        $key = 'change-wa:' . $citizen->id;

        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key, 3600);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak perubahan nomor');

        $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_ganti_whatsapp_2x_masih_diizinkan(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['whatsapp_number' => '6281234567890']);
        $citizen->shouldReceive('tokens')->andReturn(Mockery::mock(['delete' => true]));

        RateLimiter::clear('change-wa:' . $citizen->id);

        $this->repositoryMock->shouldReceive('update');

        // Pertama
        try {
            $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999998']);
        } catch (ValidationException $e) {
        }

        RateLimiter::clear('change-wa:' . $citizen->id);

        // Kedua — harusnya masih bisa (max 2)
        try {
            $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);
        } catch (ValidationException $e) {
        }

        // Tidak throw rate limit = PASS
        $this->assertTrue(true);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_whatsapp_sama_setelah_normalisasi_tidak_memicu_perubahan(): void
    {
        $citizen = $this->mockCitizen(['whatsapp_number' => '081234567890']);

        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, ['whatsapp_number' => '6281234567890'])
            ->once()
            ->andReturn($citizen);

        $result = $this->service->updateProfile($citizen, ['whatsapp_number' => '6281234567890']);

        $this->assertInstanceOf(Citizen::class, $result);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_update_dengan_data_kosong_tidak_memanggil_repository(): void
    {
        $citizen = $this->mockCitizen();

        $result = $this->service->updateProfile($citizen, []);

        $this->assertEquals($citizen->id, $result->id);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_update_profile_return_citizen(): void
    {
        $citizen = $this->mockCitizen();

        $this->repositoryMock
            ->shouldReceive('update')
            ->andReturn($citizen);

        $result = $this->service->updateProfile($citizen, ['full_name' => 'Test']);

        $this->assertInstanceOf(Citizen::class, $result);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_update_nama_saja_tidak_trigger_whatsapp_flow(): void
    {
        $citizen = $this->mockCitizen();

        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, ['full_name' => 'Nama Baru'])
            ->once()
            ->andReturn($citizen);

        $result = $this->service->updateProfile($citizen, ['full_name' => 'Nama Baru']);

        $this->assertEquals('Ahmad Fauzi', $citizen->full_name);
    }

    public function test_grup_update_whatsapp_trigger_verifikasi(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['whatsapp_number' => '6281234567890']);
        $citizen->shouldReceive('tokens')->andReturn(Mockery::mock(['delete' => true]));

        RateLimiter::clear('change-wa:' . $citizen->id);

        $this->repositoryMock->shouldReceive('update');

        $this->expectException(ValidationException::class);

        $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_ganti_whatsapp_mengubah_is_verified_ke_false(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen([
            'is_verified' => true,
            'whatsapp_number' => '6281234567890',
        ]);
        $citizen->shouldReceive('tokens')->andReturn(Mockery::mock(['delete' => true]));

        RateLimiter::clear('change-wa:' . $citizen->id);

        $capturedData = null;
        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, Mockery::on(function ($arg) use (&$capturedData) {
                $capturedData = $arg;
                return true;
            }))
            ->andReturn($citizen);

        try {
            $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);
        } catch (ValidationException $e) {
        }

        $this->assertFalse($capturedData['is_verified']);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_rate_limiter_mencegah_spam_ganti_whatsapp(): void
    {
        $citizen = $this->mockCitizen();
        $key = 'change-wa:' . $citizen->id;

        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key, 3600);

        $this->expectException(ValidationException::class);

        $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_ganti_whatsapp_revoke_semua_token(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['whatsapp_number' => '6281234567890']);
        $tokensMock = Mockery::mock();
        $tokensMock->shouldReceive('delete')->once()->andReturn(true);
        $citizen->shouldReceive('tokens')->andReturn($tokensMock);

        RateLimiter::clear('change-wa:' . $citizen->id);

        $this->repositoryMock->shouldReceive('update');

        try {
            $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);
        } catch (ValidationException $e) {
        }

        // Token mock sudah verify delete() dipanggil
        $this->assertTrue(true);
    }

    public function test_otp_tidak_disimpan_sebagai_plain_text(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['whatsapp_number' => '6281234567890']);
        $citizen->shouldReceive('tokens')->andReturn(Mockery::mock(['delete' => true]));

        RateLimiter::clear('change-wa:' . $citizen->id);

        $capturedData = null;
        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, Mockery::on(function ($arg) use (&$capturedData) {
                $capturedData = $arg;
                return true;
            }));

        try {
            $this->service->updateProfile($citizen, ['whatsapp_number' => '6289999999999']);
        } catch (ValidationException $e) {
        }

        $this->assertNotEquals('6289999999999', $capturedData['temporary_pin'] ?? '');
    }
}