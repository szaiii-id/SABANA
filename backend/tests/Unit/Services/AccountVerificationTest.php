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

class AccountVerificationTest extends TestCase
{
    protected $citizenRepo;
    protected $fonnte;
    protected $authService;
    protected $unverifiedCitizen;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->citizenRepo = Mockery::mock(CitizenRepositoryInterface::class);
        $this->fonnte = Mockery::mock(FonnteService::class);
        
        $this->authService = new AuthService(
            $this->citizenRepo,
            $this->fonnte
        );

        // DATA STANDAR: Warga yang baru mendaftar tapi belum verifikasi OTP
        $this->unverifiedCitizen = new Citizen([
            'id' => 1,
            'nik' => '6301012345678901',
            'whatsapp_number' => '08123456789',
            'full_name' => 'Akhmad Jainudin',
            'temporary_pin' => Hash::make('123456'), // OTP 123456
            'is_verified' => false,
        ]);
        // Anggap OTP kadaluarsa 10 menit lagi
        $this->unverifiedCitizen->temporary_pin_expired_at = now()->addMinutes(10); 
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SKENARIO: VERIFIKASI OTP
    // ==========================================

    /**
     * [SUCCESS] Berhasil verifikasi dengan OTP yang benar.
     */
    public function test_verify_otp_successfully(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789', 'otp' => '123456'];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')
            ->with($data['nik'], $data['whatsapp_number'])
            ->andReturn($this->unverifiedCitizen);
            
        $this->citizenRepo->shouldReceive('isOtpExpired')->andReturn(false);
        
        // Memastikan update data mengubah is_verified jadi true dan menghapus OTP
        $this->citizenRepo->shouldReceive('update')->once()->with(\Mockery::any(), [
            'is_verified' => true,
            'temporary_pin' => null,
            'temporary_pin_expired_at' => null,
        ])->andReturn(true);

        $this->authService->verifyRegistrationOtp($data);
        
        $this->assertTrue(true); // Jika tidak throw Exception, test sukses.
    }

    /**
     * [NEGATIVE] Gagal verifikasi karena OTP salah/typo.
     */
    public function test_verify_otp_fails_if_otp_is_wrong(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789', 'otp' => '999999']; // OTP Salah

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn($this->unverifiedCitizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP salah atau tidak valid.');

        $this->authService->verifyRegistrationOtp($data);
    }

    /**
     * [EDGE CASE] Gagal verifikasi karena waktu OTP sudah lewat (Expired).
     */
    public function test_verify_otp_fails_if_otp_is_expired(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789', 'otp' => '123456'];
        
        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn($this->unverifiedCitizen);
        
        // Memaksa sistem menganggap OTP sudah expired
        $this->citizenRepo->shouldReceive('isOtpExpired')->once()->andReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kode OTP telah kedaluwarsa.');

        $this->authService->verifyRegistrationOtp($data);
    }

    /**
     * [NEGATIVE] Gagal verifikasi karena akun sebenarnya sudah aktif (Mencegah Bypass).
     */
    public function test_verify_otp_fails_if_account_already_verified(): void
    {
        $verifiedCitizen = clone $this->unverifiedCitizen;
        $verifiedCitizen->is_verified = true;

        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789', 'otp' => '123456'];

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn($verifiedCitizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun ini sudah terverifikasi.');

        $this->authService->verifyRegistrationOtp($data);
    }


    // ==========================================
    // SKENARIO: KIRIM ULANG (RESEND) OTP
    // ==========================================

    /**
     * [SUCCESS] Berhasil kirim ulang OTP dan mengupdate database.
     */
    public function test_resend_otp_successfully(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789'];
        $key = 'resend-otp:' . $data['nik'];

        // Mock RateLimiter agar tidak terkena limit
        RateLimiter::shouldReceive('tooManyAttempts')->with($key, 3)->andReturn(false);
        RateLimiter::shouldReceive('hit')->with($key, 60)->once();

        $this->citizenRepo->shouldReceive('findByNikAndWhatsapp')->andReturn($this->unverifiedCitizen);
        
        // Memastikan update database dengan PIN sementara yang baru
        $this->citizenRepo->shouldReceive('update')->once();
        
        // Memastikan Fonnte dipanggil untuk mengirim pesan WA baru
        $this->fonnte->shouldReceive('sendMessage')->once();

        $this->authService->resendRegistrationOtp($data);
        
        $this->assertTrue(true);
    }

    /**
     * [SECURITY] Gagal kirim ulang karena kena sistem Anti-Spam (Rate Limiter).
     */
    public function test_resend_otp_blocked_by_rate_limiter(): void
    {
        $data = ['nik' => '6301012345678901', 'whatsapp_number' => '08123456789'];
        $key = 'resend-otp:' . $data['nik'];

        // Mock RateLimiter agar mendeteksi spam (lebih dari 3 kali)
        RateLimiter::shouldReceive('tooManyAttempts')->with($key, 3)->andReturn(true);
        RateLimiter::shouldReceive('availableIn')->with($key)->andReturn(120); // 2 menit

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak permintaan.');

        $this->authService->resendRegistrationOtp($data);
    }
}