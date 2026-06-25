<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthService;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Jobs\SendWhatsAppJob;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Exception;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;


final class AccountVerificationTest extends TestCase
{
    use MockeryPHPUnitIntegration; 
    private CitizenRepositoryInterface $citizenRepo;
    private AuthService $authService;
    private Citizen $unverifiedCitizen;
    private string $testIp = '127.0.0.1';
    
    protected function setUp(): void
    {
        parent::setUp();

        // ✅ Init Mockery manual
        $this->citizenRepo = Mockery::mock(CitizenRepositoryInterface::class);

        $this->authService = new AuthService($this->citizenRepo);

        $this->unverifiedCitizen = Citizen::factory()->make([
            'id'                       => 1,
            'nik'                      => '6301012345678901',
            'whatsapp_number'          => '08123456789',
            'full_name'                => 'Akhmad Jainudin',
            'temporary_pin'            => Hash::make('123456'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
            'is_verified'              => false,
        ]);

        RateLimiter::clear('verify-otp:6301012345678901');
        RateLimiter::clear('verify-otp-ip:127.0.0.1');
        RateLimiter::clear('resend-otp:6301012345678901');

        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    // ===== VERIFIKASI OTP =====

    /** @test */
    public function test_verify_otp_successfully(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
            'otp'             => '123456',
        ];

        // AuthService pakai findByNikWithLock (bukan findByNikAndWhatsapp)
        $this->citizenRepo->shouldReceive('findByNikWithLock')
            ->with($data['nik'])
            ->andReturn($this->unverifiedCitizen);

        $this->citizenRepo->shouldReceive('isOtpExpired')
            ->once()
            ->andReturn(false);

        $this->citizenRepo->shouldReceive('update')
            ->once()
            ->with(1, [
                'is_verified'              => true,
                'temporary_pin'            => null,
                'temporary_pin_expired_at' => null,
            ])
            ->andReturn(true);

        // ✅ Passing IP address
        $this->authService->verifyRegistrationOtp($data, $this->testIp);

        $this->assertTrue(true);
    }

    /** @test */
    public function test_verify_otp_fails_if_otp_is_wrong(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
            'otp'             => '999999',
        ];

        $this->citizenRepo->shouldReceive('findByNikWithLock')
            ->andReturn($this->unverifiedCitizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah atau tidak valid.');

        $this->authService->verifyRegistrationOtp($data, $this->testIp);
    }

    /** @test */
    public function test_verify_otp_fails_if_otp_is_expired(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
            'otp'             => '123456',
        ];

        $this->citizenRepo->shouldReceive('findByNikWithLock')
            ->andReturn($this->unverifiedCitizen);

        $this->citizenRepo->shouldReceive('isOtpExpired')
            ->once()
            ->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP telah kedaluwarsa.');

        $this->authService->verifyRegistrationOtp($data, $this->testIp);
    }

    /** @test */
    public function test_verify_otp_fails_if_account_already_verified(): void
    {
        $verifiedCitizen = Citizen::factory()->make([
            'id'              => 2,
            'nik'             => '6301012345678901',
            'is_verified'     => true,
            'whatsapp_number' => '08123456789',
        ]);

        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
            'otp'             => '123456',
        ];

        $this->citizenRepo->shouldReceive('findByNikWithLock')
            ->andReturn($verifiedCitizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun ini sudah terverifikasi.');

        $this->authService->verifyRegistrationOtp($data, $this->testIp);
    }

    /** @test */
    public function test_verify_otp_fails_when_citizen_not_found(): void
    {
        $data = [
            'nik'             => '6309999999999999',
            'whatsapp_number' => '08123456789',
            'otp'             => '123456',
        ];

        $this->citizenRepo->shouldReceive('findByNikWithLock')
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Data pendaftar tidak ditemukan.');

        $this->authService->verifyRegistrationOtp($data, $this->testIp);
    }

    // ===== RESEND OTP =====

    /** @test */
    public function test_resend_otp_successfully(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->with($data['nik'], $data['whatsapp_number'])
            ->andReturn($this->unverifiedCitizen);

        // Cooldown check — OTP expired, boleh resend
        $this->citizenRepo->shouldReceive('isOtpExpired')
            ->once()
            ->andReturn(true);

        $this->citizenRepo->shouldReceive('update')
            ->once();

        $this->authService->resendRegistrationOtp($data);

        // ✅ Verify WhatsApp job dispatched (bukan Fonnte)
        Queue::assertPushed(SendWhatsAppJob::class);
    }

    /** @test */
    public function test_resend_otp_blocked_by_rate_limiter(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
        ];
        $key = 'resend-otp:' . $data['nik'];

        // Hit rate limiter 3x
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 60);
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak permintaan.');

        $this->authService->resendRegistrationOtp($data);
    }

    /** @test */
    public function test_resend_otp_blocked_when_otp_still_active(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->andReturn($this->unverifiedCitizen);

        // OTP belum expired → tolak resend
        $this->citizenRepo->shouldReceive('isOtpExpired')
            ->once()
            ->andReturn(false);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode verifikasi masih berlaku.');

        $this->authService->resendRegistrationOtp($data);
    }

    /** @test */
    public function test_resend_otp_fails_when_already_verified(): void
    {
        $verifiedCitizen = Citizen::factory()->make([
            'id'              => 2,
            'nik'             => '6301012345678901',
            'is_verified'     => true,
            'whatsapp_number' => '08123456789',
        ]);

        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->andReturn($verifiedCitizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun tidak valid atau sudah aktif.');

        $this->authService->resendRegistrationOtp($data);
    }
}