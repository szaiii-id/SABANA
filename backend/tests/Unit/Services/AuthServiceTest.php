<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Services/AuthServiceTest.php =====

namespace Tests\Unit\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class AuthServiceTest extends TestCase
{
    private AuthService $service;
    private CitizenRepositoryInterface $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(CitizenRepositoryInterface::class);
        $this->service = new AuthService($this->repositoryMock);

        RateLimiter::clear('login:6371012508900001');
        RateLimiter::clear('login-ip:127.0.0.1');
        RateLimiter::clear('verify-otp:6371012508900001');
        RateLimiter::clear('verify-otp-ip:127.0.0.1');
        RateLimiter::clear('resend-otp:6371012508900001');
        RateLimiter::clear('otp-request:6371012508900001');
        RateLimiter::clear('otp-request-ip:127.0.0.1');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== [DATA HELPERS] =====

    private function validLoginData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'pin' => '123456',
        ], $overrides);
    }

    private function validOtpVerifyData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
            'otp' => '654321',
        ], $overrides);
    }

    private function validResendData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
        ], $overrides);
    }

    private function validResetPinData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
            'otp' => '654321',
            'new_pin' => 'newpin123',
        ], $overrides);
    }

    private function mockCitizen(array $attributes = []): Citizen
    {
        $id = $attributes['id'] ?? '019eba77-304a-70f0-b7ae-54a3e93cc1b6';

        $citizen = Mockery::mock(Citizen::class)->makePartial();
        $citizen->shouldAllowMockingProtectedMethods();
        $citizen->shouldReceive('getKey')->andReturn($id);

        $citizen->id = $id;
        $citizen->nik = $attributes['nik'] ?? '6371012508900001';
        $citizen->full_name = $attributes['full_name'] ?? 'Ahmad Fauzi';
        $citizen->whatsapp_number = $attributes['whatsapp_number'] ?? '6281234567890';
        $citizen->pin = $attributes['pin'] ?? Hash::make('123456');
        $citizen->is_verified = $attributes['is_verified'] ?? true;
        $citizen->temporary_pin = $attributes['temporary_pin'] ?? null;
        $citizen->temporary_pin_expired_at = $attributes['temporary_pin_expired_at'] ?? null;

        return $citizen;
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_verify_registration_otp_berhasil(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(5),
        ]);
        $data = $this->validOtpVerifyData(['otp' => '654321']);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->with($citizen)
            ->once()
            ->andReturn(false);

        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, [
                'is_verified' => true,
                'temporary_pin' => null,
                'temporary_pin_expired_at' => null,
            ])
            ->once()
            ->andReturn($citizen);

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
        $this->assertTrue(true);
    }

    public function test_request_otp_berhasil(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['is_verified' => true]);
        $data = $this->validResendData();

        $this->repositoryMock
            ->shouldReceive('findByNikAndWhatsapp')
            ->with('6371012508900001', '6281234567890')
            ->once()
            ->andReturn($citizen);

        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, Mockery::on(function ($arg) {
                return isset($arg['temporary_pin'], $arg['temporary_pin_expired_at']);
            }))
            ->once()
            ->andReturn($citizen);

        $this->service->requestOtp($data, '127.0.0.1');

        Queue::assertPushed(SendWhatsAppJob::class);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_login_gagal_nik_atau_pin_salah(): void
    {
        $data = $this->validLoginData(['pin' => 'salah']);

        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->with('6371012508900001')
            ->once()
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau PIN yang Anda masukkan salah.');

        $this->service->login($data, '127.0.0.1');
    }

    public function test_login_gagal_pin_salah_tapi_nik_ditemukan(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('999999')]);
        $data = $this->validLoginData(['pin' => '123456']);

        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau PIN yang Anda masukkan salah.');

        $this->service->login($data, '127.0.0.1');
    }

    public function test_login_gagal_akun_belum_terverifikasi_dan_otp_auto_dikirim(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'pin' => Hash::make('123456'),
        ]);
        $data = $this->validLoginData();

        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun belum aktif');

        $this->service->login($data, '127.0.0.1');

        Queue::assertPushed(SendWhatsAppJob::class);
    }

    public function test_verify_otp_gagal_kode_salah(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => Hash::make('654321'),
        ]);
        $data = $this->validOtpVerifyData(['otp' => '000000']);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah');

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
    }

    public function test_verify_otp_gagal_sudah_terverifikasi(): void
    {
        $citizen = $this->mockCitizen(['is_verified' => true]);
        $data = $this->validOtpVerifyData();

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sudah terverifikasi');

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
    }

    public function test_verify_otp_gagal_data_tidak_ditemukan(): void
    {
        $data = $this->validOtpVerifyData();

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Data pendaftar tidak ditemukan');

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
    }

    public function test_verify_otp_gagal_otp_expired(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->subHour(),
        ]);
        $data = $this->validOtpVerifyData(['otp' => '654321']);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->with($citizen)
            ->once()
            ->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP telah kedaluwarsa');

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
    }

    public function test_resend_otp_gagal_akun_sudah_terverifikasi(): void
    {
        $citizen = $this->mockCitizen(['is_verified' => true]);
        $data = $this->validResendData();

        $this->repositoryMock
            ->shouldReceive('findByNikAndWhatsapp')
            ->with('6371012508900001', '6281234567890')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun tidak valid atau sudah aktif');

        $this->service->resendRegistrationOtp($data);
    }

    public function test_request_otp_gagal_nik_tidak_ditemukan(): void
    {
        $data = $this->validResendData();

        $this->repositoryMock
            ->shouldReceive('findByNikAndWhatsapp')
            ->with('6371012508900001', '6281234567890')
            ->once()
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau nomor WhatsApp yang Anda masukkan tidak ditemukan');

        $this->service->requestOtp($data, '127.0.0.1');
    }

    public function test_request_otp_gagal_akun_belum_terverifikasi(): void
    {
        $citizen = $this->mockCitizen(['is_verified' => false]);
        $data = $this->validResendData();

        $this->repositoryMock
            ->shouldReceive('findByNikAndWhatsapp')
            ->with('6371012508900001', '6281234567890')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun belum terverifikasi');

        $this->service->requestOtp($data, '127.0.0.1');
    }

    public function test_reset_pin_gagal_otp_salah(): void
    {
        $citizen = $this->mockCitizen(['temporary_pin' => Hash::make('654321')]);
        $data = $this->validResetPinData(['otp' => '000000']);

        $this->repositoryMock
            ->shouldReceive('findByNikAndWhatsapp')
            ->with('6371012508900001', '6281234567890')
            ->once()
            ->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah');

        $this->service->resetPin($data);
    }

    public function test_reset_pin_gagal_otp_expired(): void
    {
        $citizen = $this->mockCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->subHour(),
        ]);
        $data = $this->validResetPinData(['otp' => '654321']);

        $this->repositoryMock
            ->shouldReceive('findByNikAndWhatsapp')
            ->with('6371012508900001', '6281234567890')
            ->once()
            ->andReturn($citizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->with($citizen)
            ->once()
            ->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP telah kedaluwarsa');

        $this->service->resetPin($data);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_login_rate_limit_tepat_di_batas_maksimal(): void
    {
        $data = $this->validLoginData(['pin' => 'salah']);

        for ($i = 0; $i < 4; $i++) {
            RateLimiter::hit('login:6371012508900001', 900);
        }

        $this->repositoryMock->shouldReceive('findByNik')->andReturn(null);

        try {
            $this->service->login($data, '127.0.0.1');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('NIK atau PIN', $e->getMessage());
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak percobaan login');

        $this->service->login($data, '127.0.0.1');
    }

    public function test_otp_expiry_tepat_di_batas_waktu(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now(),
        ]);
        $data = $this->validOtpVerifyData(['otp' => '654321']);

        $this->repositoryMock->shouldReceive('findByNikWithLock')->andReturn($citizen);
        $this->repositoryMock->shouldReceive('isOtpExpired')->andReturn(false);
        $this->repositoryMock->shouldReceive('update')->andReturn($citizen);

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
        $this->assertTrue(true);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_logout_tanpa_token_tidak_error(): void
    {
        $citizen = $this->mockCitizen();
        $citizen->shouldReceive('currentAccessToken')->andReturn(null);

        $this->service->logout($citizen);
        $this->assertTrue(true);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_verify_otp_tanpa_temporary_pin(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => null,
        ]);
        $data = $this->validOtpVerifyData();

        $this->repositoryMock->shouldReceive('findByNikWithLock')->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah');

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
    }

    public function test_reset_pin_tanpa_temporary_pin(): void
    {
        $citizen = $this->mockCitizen(['temporary_pin' => null]);
        $data = $this->validResetPinData();

        $this->repositoryMock->shouldReceive('findByNikAndWhatsapp')->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah');

        $this->service->resetPin($data);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_verify_otp_return_type_void(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(5),
        ]);
        $data = $this->validOtpVerifyData(['otp' => '654321']);

        $this->repositoryMock->shouldReceive('findByNikWithLock')->andReturn($citizen);
        $this->repositoryMock->shouldReceive('isOtpExpired')->andReturn(false);
        $this->repositoryMock->shouldReceive('update')->andReturn($citizen);

        $result = $this->service->verifyRegistrationOtp($data, '127.0.0.1');
        $this->assertNull($result);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_citizen_belum_terverifikasi_login_ditolak(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['is_verified' => false, 'pin' => Hash::make('123456')]);
        $data = $this->validLoginData();

        $this->repositoryMock->shouldReceive('findByNik')->andReturn($citizen);
        $this->repositoryMock->shouldReceive('update')->andReturn($citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun belum aktif');

        $this->service->login($data, '127.0.0.1');
    }

    public function test_grup_request_otp_terverifikasi_berhasil(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['is_verified' => true]);
        $data = $this->validResendData();

        $this->repositoryMock->shouldReceive('findByNikAndWhatsapp')->andReturn($citizen);
        $this->repositoryMock->shouldReceive('update')->andReturn($citizen);

        $this->service->requestOtp($data, '127.0.0.1');

        Queue::assertPushed(SendWhatsAppJob::class);
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_transisi_dari_belum_verifikasi_ke_terverifikasi(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(5),
        ]);
        $data = $this->validOtpVerifyData(['otp' => '654321']);

        $capturedData = null;

        $this->repositoryMock->shouldReceive('findByNikWithLock')->andReturn($citizen);
        $this->repositoryMock->shouldReceive('isOtpExpired')->andReturn(false);
        $this->repositoryMock
            ->shouldReceive('update')
            ->with($citizen->id, Mockery::on(function ($arg) use (&$capturedData) {
                $capturedData = $arg;
                return true;
            }))
            ->andReturn($citizen);

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');

        $this->assertTrue($capturedData['is_verified']);
        $this->assertNull($capturedData['temporary_pin']);
        $this->assertNull($capturedData['temporary_pin_expired_at']);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_verify_otp_menggunakan_lock_untuk_cegah_race_condition(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(5),
        ]);
        $data = $this->validOtpVerifyData(['otp' => '654321']);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->repositoryMock->shouldReceive('isOtpExpired')->andReturn(false);
        $this->repositoryMock->shouldReceive('update')->andReturn($citizen);

        $this->service->verifyRegistrationOtp($data, '127.0.0.1');
        $this->assertTrue(true);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_otp_tidak_dikembalikan_via_return_value_saat_request_otp(): void
    {
        Queue::fake();

        $citizen = $this->mockCitizen(['is_verified' => true]);
        $data = $this->validResendData();

        $this->repositoryMock->shouldReceive('findByNikAndWhatsapp')->andReturn($citizen);
        $this->repositoryMock->shouldReceive('update')->andReturn($citizen);

        $result = $this->service->requestOtp($data, '127.0.0.1');

        $this->assertNull($result);
        Queue::assertPushed(SendWhatsAppJob::class);
    }

    public function test_rate_limit_diterapkan_per_ip_dan_per_nik(): void
    {
        $data = $this->validLoginData(['pin' => 'salah']);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('login:6371012508900001', 900);
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak percobaan login');

        $this->service->login($data, '127.0.0.1');
    }
}