<?php
namespace App\Services;

use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    protected CitizenRepositoryInterface $citizenRepository;
    protected FonnteService $fonnteService;

    public function __construct(CitizenRepositoryInterface $citizenRepository, FonnteService $fonnteService)
    {
        $this->citizenRepository = $citizenRepository;
        $this->fonnteService = $fonnteService;
    }

    public function login(array $credentials): array
    {
        $citizen = $this->citizenRepository->findByNik($credentials['nik']);

        if (!$citizen || !Hash::check($credentials['pin'], $citizen->pin)) {
            throw ValidationException::withMessages([
                'nik' => ['NIK atau PIN yang Anda masukkan salah.'],
            ]);
        }

        if (!$citizen->is_verified) {
            throw ValidationException::withMessages([
                'is_verified' => ['Akun belum aktif. Silakan verifikasi nomor WhatsApp Anda.'],
                'whatsapp_number' => $citizen->whatsapp_number 
            ]);
        }

        $this->citizenRepository->update($citizen->id, ['last_login_at' => now()]);

        $token = $citizen->createToken('Sabana App Token')->plainTextToken;

        return [
            'user'  => $citizen,
            'token' => $token,
        ];
    }

    public function verifyRegistrationOtp(array $data): void
    {
        $citizen = $this->citizenRepository->findByNikAndWhatsapp($data['nik'], $data['whatsapp_number']);

        if (!$citizen) {
            throw ValidationException::withMessages(['nik' => ['Data pendaftar tidak ditemukan.']]);
        }

        if ($citizen->is_verified) {
            throw ValidationException::withMessages(['otp' => ['Akun ini sudah terverifikasi. Silakan langsung login.']]);
        }

        if (!$citizen->temporary_pin || !Hash::check($data['otp'], $citizen->temporary_pin)) {
            throw ValidationException::withMessages(['otp' => ['Kode OTP salah atau tidak valid.']]);
        }

        if ($this->citizenRepository->isOtpExpired($citizen)) {
            throw ValidationException::withMessages(['otp' => ['Kode OTP telah kedaluwarsa. Silakan minta kode baru.']]);
        }

        $this->citizenRepository->update($citizen->id, [
            'is_verified' => true,
            'temporary_pin' => null,
            'temporary_pin_expired_at' => null,
        ]);
    }

    public function resendRegistrationOtp(array $data): void
    {
        $key = 'resend-otp:' . $data['nik'];

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $minutes = ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages([
                'otp' => ["Terlalu banyak permintaan. Silakan coba lagi dalam {$minutes} menit."],
            ]);
        }

        $citizen = $this->citizenRepository->findByNikAndWhatsapp($data['nik'], $data['whatsapp_number']);

        if (!$citizen || $citizen->is_verified) {
            throw ValidationException::withMessages(['nik' => ['Akun tidak valid atau sudah aktif.']]);
        }

        RateLimiter::hit($key, 60);

        $newOtp = (string) random_int(100000, 999999);
        $this->citizenRepository->update($citizen->id, [
            'temporary_pin' => Hash::make($newOtp),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $message = "*[SABANA KALSEL - KIRIM ULANG]*\n\nKode verifikasi baru Anda adalah:\n\n*{$newOtp}*\n\nBerlaku 10 menit.";
        $this->fonnteService->sendMessage($citizen->whatsapp_number, $message);
    }

    public function requestOtp(array $data): string
    {
        $key = 'opt-request:' . $data['nik'];

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            throw ValidationException::withMessages([
                'nik' => ["Terlalu banyak permintaan OTP. Silakan coba lagi dalam {$minutes} menit."],
            ]);
        }

        $citizen = $this->citizenRepository->findByNikAndWhatsapp($data['nik'], $data['whatsapp_number']);

        if (!$citizen) {
            throw ValidationException::withMessages([
                'nik' => ['NIK atau nomor WhatsApp yang Anda masukkan tidak ditemukan.'],
            ]);
        }

        $temporaryPin = (string) random_int(100000, 999999);

        RateLimiter::hit($key, 1800); 

        $this->citizenRepository->update($citizen->id, [
            'temporary_pin' => Hash::make($temporaryPin),
            'temporary_pin_expired_at' => Carbon::now()->addMinutes(10),
        ]);

        $message = "Hallo {$citizen->full_name}, ini adalah PIN sementara Anda untuk reset PIN di aplikasi Sabana: {$temporaryPin}. PIN ini berlaku selama 10 menit. Jangan bagikan PIN ini kepada siapapun.";
        $this->fonnteService->sendMessage($citizen->whatsapp_number, $message);

        return $temporaryPin;

    }

   
    public function resetPin(array $data): void
    {
        $citizen =$this->citizenRepository->findByNikAndWhatsapp($data['nik'], $data['whatsapp_number']);

        if (!$citizen) {
            throw ValidationException::withMessages([
                'nik' => ['NIK atau nomor WhatsApp yang Anda masukkan tidak valid.'],
            ]);
        }

        if(!$citizen->temporary_pin || !Hash::check($data['otp'], $citizen->temporary_pin)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP salah atau tidak valid.'],
            ]);
        }

        if ($this->citizenRepository->isOtpExpired($citizen)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP telah kedaluwarsa. Silakan minta kode baru.'],
            ]);
        }

        $citizen->tokens()->delete(); 

        $this->citizenRepository->update($citizen->id, [
            'pin' => Hash::make($data['new_pin']),
            'temporary_pin' => null,
            'temporary_pin_expired_at' => null,
        ]);

    }

    public function logout($user): void
    {
        $user->currentAccessToken()->delete();
    }

    
}