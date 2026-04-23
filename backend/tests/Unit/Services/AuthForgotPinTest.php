<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthService;
use App\Services\FonnteService;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;

class AuthForgotPinTest extends TestCase
{
    protected $citizenRepo;
    protected $fonnte;
    protected $authService;
    protected $citizen;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->citizenRepo = Mockery::mock(CitizenRepositoryInterface::class);
        $this->fonnte = Mockery::mock(FonnteService::class);
        
        $this->authService = new AuthService(
            $this->citizenRepo,
            $this->fonnte
        );

        // DATA STANDAR: Warga yang sudah aktif
        $this->citizen = Mockery::mock(Citizen::class)->makePartial();
        $this->citizen->id = 1;
        $this->citizen->nik = '6301012345678901';
        $this->citizen->whatsapp_number = '08123456789';
        $this->citizen->full_name = 'Akhmad Jainudin';
        $this->citizen->pin = Hash::make('123456');
        $this->citizen->temporary_pin = Hash::make('654321'); // OTP Aktif
        $this->citizen->is_verified = true;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SKENARIO: REQUEST OTP (LUPA PIN)
    // ==========================================

    /**
     * [SUCCESS] Berhasil meminta OTP untuk reset PIN
     */
    public function test_request_otp_successfully(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789'];
        $key = 'opt-request:' . $data['nik']; // Mengikuti typo 'opt' di kode asli Mas

        RateLimiter::shouldReceive('tooManyAttempts')->with($key, 3)->andReturn(false);
        RateLimiter::shouldReceive('hit')->with($key, 1800)->once();

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->with($data['nik'], $data['whatsapp_number'])->andReturn($this->citizen);
        $this->citizenRepo->shouldReceive('update')->once()->with(1, \Mockery::type('array'));
        
        // Memastikan Fonnte mengirim pesan berisi PIN sementara
        $this->fonnte->shouldReceive('sendMessage')->once()->with(
            $data['whatsapp_number'],
            \Mockery::pattern('/PIN ini berlaku selama 10 menit/')
        );

        $otp = $this->authService->requestOtp($data);

        $this->assertIsString($otp);
        $this->assertEquals(6, strlen($otp)); // Pastikan OTP 6 digit
    }

    /**
     * [SECURITY] Gagal minta OTP karena kena Rate Limiter (Spam)
     */
    public function test_request_otp_blocked_by_rate_limiter(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789'];
        $key = 'opt-request:' . $data['nik'];

        RateLimiter::shouldReceive('tooManyAttempts')->with($key, 3)->andReturn(true);
        RateLimiter::shouldReceive('availableIn')->with($key)->andReturn(600); // 10 menit

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak permintaan OTP.');

        $this->authService->requestOtp($data);
    }

    /**
     * [NEGATIVE] Gagal minta OTP karena NIK/WA tidak cocok
     */
    public function test_request_otp_fails_if_citizen_not_found(): void
    {
        $data = ['nik' => '9999999999999999', 'whatsapp_number' => '08123456789'];

        RateLimiter::shouldReceive('tooManyAttempts')->andReturn(false);
        
        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau nomor WhatsApp yang Anda masukkan tidak ditemukan.');

        $this->authService->requestOtp($data);
    }


    // ==========================================
    // SKENARIO: RESET PIN
    // ==========================================

    /**
     * [SUCCESS] Berhasil Reset PIN dan menghapus semua Token (Force Logout)
     */
    public function test_reset_pin_successfully(): void
    {
        $data = [
            'nik' => '6301012345678901', 
            'whatsapp_number' => '08123456789', 
            'otp' => '654321', 
            'new_pin' => '111222'
        ];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn($this->citizen);
        $this->citizenRepo->shouldReceive('isOtpExpired')->andReturn(false);
        
        // Memastikan update menyimpan PIN baru dan menghapus OTP
        $this->citizenRepo->shouldReceive('update')->once()->with(1, \Mockery::on(function ($updateData) {
            return isset($updateData['pin']) && 
                   $updateData['temporary_pin'] === null && 
                   $updateData['temporary_pin_expired_at'] === null;
        }));

        // INI PENTING: Memastikan tokens()->delete() dipanggil
        $this->citizen->shouldReceive('tokens->delete')->once();

        $this->authService->resetPin($data);

        $this->assertTrue(true);
    }

    /**
     * [NEGATIVE] Gagal Reset PIN karena OTP salah
     */
    public function test_reset_pin_fails_if_otp_is_wrong(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789', 'otp' => '000000', 'new_pin' => '111222'];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn($this->citizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah atau tidak valid.');

        $this->authService->resetPin($data);
    }

    /**
     * [EDGE CASE] Gagal Reset PIN karena OTP Kedaluwarsa
     */
    public function test_reset_pin_fails_if_otp_is_expired(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789', 'otp' => '654321', 'new_pin' => '111222'];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn($this->citizen);
        $this->citizenRepo->shouldReceive('isOtpExpired')->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP telah kedaluwarsa.');

        $this->authService->resetPin($data);
    }
}