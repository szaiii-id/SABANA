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

        $this->citizenRepository->update($citizen, ['last_login_at' => now()]);

        $token = $citizen->createToken('Sabana Personal Access Client')->accessToken;

        return [
            'user'  => $citizen,
            'token' => $token,
        ];
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

        $this->citizenRepository->update($citizen, [
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

        $citizen->tokens()->update(['revoked' => true]); 

        $this->citizenRepository->update($citizen, [
            'pin' => Hash::make($data['new_pin']),
            'temporary_pin' => null,
            'temporary_pin_expired_at' => null,
        ]);

    }

    public function logout($user): void
    {
        $user->token()->revoke();
    }

    
}