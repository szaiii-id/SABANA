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

final class AuthForgotPinTest extends TestCase
{
    private CitizenRepositoryInterface $citizenRepo;
    private AuthService $authService;
    private Citizen $citizen;
    private string $testIp = '127.0.0.1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->citizenRepo = Mockery::mock(CitizenRepositoryInterface::class);
        $this->authService = new AuthService($this->citizenRepo);

        // ✅ Gunakan UUID valid untuk id
        $uuid = '019e7c38-9783-7102-acd7-3e661698867a';

        $this->citizen = Citizen::factory()->make([
            'id'              => $uuid,
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
            'full_name'       => 'Akhmad Jainudin',
            'pin'             => Hash::make('123456'),
            'temporary_pin'   => Hash::make('654321'),
            'is_verified'     => true,
        ]);

        RateLimiter::clear('otp-request:6301012345678901');
        RateLimiter::clear('otp-request-ip:127.0.0.1');

        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== REQUEST OTP (LUPA PIN) =====

    /** @test */
    public function test_request_otp_successfully(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->with($data['nik'], $data['whatsapp_number'])
            ->andReturn($this->citizen);

        $this->citizenRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::any(), Mockery::type('array'));

        // ✅ requestOtp sekarang butuh 2 parameter
        $this->authService->requestOtp($data, $this->testIp);

        // ✅ Verify WhatsApp job dispatched
        Queue::assertPushed(SendWhatsAppJob::class, function ($job) {
            return $job->target === '08123456789';
        });
    }

    /** @test */
    public function test_request_otp_blocked_by_rate_limiter(): void
    {
        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
        ];
        $key = 'otp-request:6301012345678901';

        // Hit rate limiter 3x
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 1800);
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak permintaan OTP.');

        $this->authService->requestOtp($data, $this->testIp);
    }

    /** @test */
    public function test_request_otp_fails_if_citizen_not_found(): void
    {
        $data = [
            'nik'             => '9999999999999999',
            'whatsapp_number' => '08123456789',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau nomor WhatsApp yang Anda masukkan tidak ditemukan.');

        $this->authService->requestOtp($data, $this->testIp);
    }

    /** @test */
    public function test_request_otp_fails_if_unverified(): void
    {
        $unverified = Citizen::factory()->make([
            'id'              => 2,
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
            'is_verified'     => false,
        ]);

        $data = [
            'nik'             => '6301012345678901',
            'whatsapp_number' => '08123456789',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->andReturn($unverified);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun belum terverifikasi.');

        $this->authService->requestOtp($data, $this->testIp);
    }

    // ===== RESET PIN =====

    /** @test */
    public function test_reset_pin_successfully(): void
    {
        $data = [
            'nik'                  => '6301012345678901',
            'whatsapp_number'      => '08123456789',
            'otp'                  => '654321',
            'new_pin'              => '111222',
            'new_pin_confirmation' => '111222',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->with($data['nik'], $data['whatsapp_number'])
            ->andReturn($this->citizen);

        $this->citizenRepo->shouldReceive('isOtpExpired')
            ->once()
            ->andReturn(false);

        $this->citizenRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::any(), Mockery::on(function (array $updateData) {
                return isset($updateData['pin'])
                    && $updateData['temporary_pin'] === null
                    && $updateData['temporary_pin_expired_at'] === null;
            }));

        // ✅ Token revoke di-test via database assertion di integration test
        $this->authService->resetPin($data);

        $this->assertTrue(true);
    }

    /** @test */
    public function test_reset_pin_fails_if_otp_is_wrong(): void
    {
        $data = [
            'nik'                  => '6301012345678901',
            'whatsapp_number'      => '08123456789',
            'otp'                  => '000000',
            'new_pin'              => '111222',
            'new_pin_confirmation' => '111222',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->andReturn($this->citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah atau tidak valid.');

        $this->authService->resetPin($data);
    }

    /** @test */
    public function test_reset_pin_fails_if_otp_is_expired(): void
    {
        $data = [
            'nik'                  => '6301012345678901',
            'whatsapp_number'      => '08123456789',
            'otp'                  => '654321',
            'new_pin'              => '111222',
            'new_pin_confirmation' => '111222',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->andReturn($this->citizen);

        $this->citizenRepo->shouldReceive('isOtpExpired')
            ->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP telah kedaluwarsa.');

        $this->authService->resetPin($data);
    }

    /** @test */
    public function test_reset_pin_fails_if_not_found(): void
    {
        $data = [
            'nik'                  => '9999999999999999',
            'whatsapp_number'      => '08123456789',
            'otp'                  => '654321',
            'new_pin'              => '111222',
            'new_pin_confirmation' => '111222',
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau nomor WhatsApp yang Anda masukkan tidak valid.');

        $this->authService->resetPin($data);
    }
}