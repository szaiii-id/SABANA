<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use App\Models\CitizenRegistrationLog;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    // Rate Limiter Constants
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 900; // 15 menit
    private const LOGIN_IP_MAX_ATTEMPTS = 10;
    private const LOGIN_IP_DECAY_SECONDS = 900;

    private const OTP_VERIFY_MAX_ATTEMPTS = 5;
    private const OTP_VERIFY_DECAY_SECONDS = 900;
    private const OTP_VERIFY_IP_MAX_ATTEMPTS = 8;
    private const OTP_VERIFY_IP_DECAY_SECONDS = 900;

    private const OTP_RESEND_MAX_ATTEMPTS = 3;
    private const OTP_RESEND_DECAY_SECONDS = 60;
    private const OTP_RESEND_COOLDOWN_SECONDS = 30;

    private const RESET_PIN_REQUEST_MAX_ATTEMPTS = 3;
    private const RESET_PIN_REQUEST_DECAY_SECONDS = 1800; // 30 menit

    private const OTP_EXPIRY_MINUTES = 10;
    private const OTP_MIN = 100000;
    private const OTP_MAX = 999999;

    public function __construct(
        private readonly CitizenRepositoryInterface $citizenRepository,
    ) {}

    // ===== LOGIN =====

    /**
     * Authenticate citizen with NIK + PIN.
     * 
     * Security:
     * - Rate limited by NIK + IP address (OWASP A07:2021)
     * - PIN verified via Hash::check()
     * - Only verified citizens can login
     * - Admin-registered citizens forced to change PIN
     */
    public function login(array $credentials, string $ipAddress): array
    {
        $nikKey = 'login:' . $credentials['nik'];
        $ipKey = 'login-ip:' . $ipAddress;

        // IP-based rate limit (brute force dari banyak NIK berbeda)
        $this->checkRateLimit($ipKey, self::LOGIN_IP_MAX_ATTEMPTS, self::LOGIN_IP_DECAY_SECONDS, 
            'Terlalu banyak percobaan login dari perangkat ini. Silakan coba lagi dalam :minutes menit.'
        );

        // NIK-based rate limit (brute force NIK spesifik)
        $this->checkRateLimit($nikKey, self::LOGIN_MAX_ATTEMPTS, self::LOGIN_DECAY_SECONDS,
            'Terlalu banyak percobaan login. Silakan coba lagi dalam :minutes menit.'
        );

        $citizen = $this->citizenRepository->findByNik($credentials['nik']);

        // Failed attempt
        if (!$citizen || !Hash::check($credentials['pin'], $citizen->pin)) {
            RateLimiter::hit($nikKey, self::LOGIN_DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::LOGIN_IP_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'nik' => ['NIK atau PIN yang Anda masukkan salah.'],
            ]);
        }

        // Not verified
        if (!$citizen->is_verified) {

            $this->sendOtpToCitizen($citizen);

            throw ValidationException::withMessages([
                'is_verified' => ['Akun belum aktif. Silakan verifikasi nomor WhatsApp Anda.'],
            ]);
        }

        // Success → clear all rate limiters
        RateLimiter::clear($nikKey);
        RateLimiter::clear($ipKey);

        // Update last login
        $this->citizenRepository->update($citizen->id, [
            'last_login_at' => now(),
        ]);

        // Issue token
        $token = $citizen->createToken('Sabana App Token')->plainTextToken;

        // Check if admin-registered (force PIN change)
        $isAdminRegistered = CitizenRegistrationLog::where('citizen_id', $citizen->id)->exists();

        return [
            'citizen'            => $citizen,
            'token'              => $token,
            'require_pin_change' => $isAdminRegistered,
        ];
    }

    // ===== VERIFY REGISTRATION OTP =====

    /**
     * Verify OTP for citizen registration.
     * 
     * Security:
     * - Row-level lock prevents concurrent double-verify
     * - Rate limited by NIK + IP
     * - OTP expiry checked after hash verification
     */
    public function verifyRegistrationOtp(array $data, string $ipAddress): void
    {
        $nikKey = 'verify-otp:' . $data['nik'];
        $ipKey = 'verify-otp-ip:' . $ipAddress;

        $this->checkRateLimit($ipKey, self::OTP_VERIFY_IP_MAX_ATTEMPTS, self::OTP_VERIFY_IP_DECAY_SECONDS,
            'Terlalu banyak percobaan verifikasi. Silakan coba lagi dalam :minutes menit.'
        );

        $this->checkRateLimit($nikKey, self::OTP_VERIFY_MAX_ATTEMPTS, self::OTP_VERIFY_DECAY_SECONDS,
            'Terlalu banyak percobaan. Silakan coba lagi dalam :minutes menit.'
        );

        // Gunakan lock untuk cegah race condition double-verify
        $citizen = $this->citizenRepository->findByNikWithLock($data['nik']);

        if (!$citizen || $citizen->whatsapp_number !== $data['whatsapp_number']) {
            RateLimiter::hit($nikKey, self::OTP_VERIFY_DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::OTP_VERIFY_IP_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'nik' => ['Data pendaftar tidak ditemukan.'],
            ]);
        }

        if ($citizen->is_verified) {
            throw ValidationException::withMessages([
                'otp' => ['Akun ini sudah terverifikasi. Silakan langsung login.'],
            ]);
        }

        if (!$citizen->temporary_pin || !Hash::check($data['otp'], $citizen->temporary_pin)) {
            RateLimiter::hit($nikKey, self::OTP_VERIFY_DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::OTP_VERIFY_IP_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'otp' => ['Kode OTP salah atau tidak valid.'],
            ]);
        }

        if ($this->citizenRepository->isOtpExpired($citizen)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP telah kedaluwarsa. Silakan minta kode baru.'],
            ]);
        }

        // Success
        RateLimiter::clear($nikKey);
        RateLimiter::clear($ipKey);

        $this->citizenRepository->update($citizen->id, [
            'is_verified'              => true,
            'temporary_pin'            => null,
            'temporary_pin_expired_at' => null,
        ]);
    }

    // ===== RESEND REGISTRATION OTP =====

    /**
     * Resend OTP for unverified citizen.
     * 
     * Security:
     * - Cooldown period between resends (30 detik)
     * - Max 3 resends per NIK per menit
     */
    public function resendRegistrationOtp(array $data): void
    {
        $key = 'resend-otp:' . $data['nik'];

        $this->checkRateLimit($key, self::OTP_RESEND_MAX_ATTEMPTS, self::OTP_RESEND_DECAY_SECONDS,
            'Terlalu banyak permintaan. Silakan coba lagi dalam :minutes menit.'
        );

        $citizen = $this->citizenRepository->findByNikAndWhatsapp($data['nik'], $data['whatsapp_number']);

        if (!$citizen || $citizen->is_verified) {
            throw ValidationException::withMessages([
                'nik' => ['Akun tidak valid atau sudah aktif.'],
            ]);
        }

        // Cooldown check — null guard untuk temporary_pin_expired_at
        if ($citizen->temporary_pin_expired_at !== null) {
            $otpCreatedAt = $citizen->temporary_pin_expired_at->copy()
                ->subMinutes(self::OTP_EXPIRY_MINUTES);
            $secondsSinceCreated = (int) $otpCreatedAt->diffInSeconds(now(), true);
            $remainingCooldown = self::OTP_RESEND_COOLDOWN_SECONDS - $secondsSinceCreated;

            if ($remainingCooldown > 0) {
                $waitMessage = $remainingCooldown > 60
                    ? (int) ceil($remainingCooldown / 60) . ' menit'
                    : $remainingCooldown . ' detik';

                throw ValidationException::withMessages([
                    'otp' => ["Silakan tunggu {$waitMessage} sebelum mengirim ulang. Gunakan kode OTP yang sudah dikirim sebelumnya."],
                ]);
            }
        }

        RateLimiter::hit($key, self::OTP_RESEND_DECAY_SECONDS);

        $this->sendOtpToCitizen($citizen);
    }
    // ===== REQUEST OTP (FORGOT PIN) =====

    /**
     * Request OTP for PIN reset.
     * 
     * Security:
     * - Only verified citizens can request
     * - Max 3 requests per 30 menit
     * - OTP tidak dikembalikan sebagai return value (dikirim via WA saja)
     */
    public function requestOtp(array $data, string $ipAddress): void
    {
        $nikKey = 'otp-request:' . $data['nik'];
        $ipKey = 'otp-request-ip:' . $ipAddress;

        $this->checkRateLimit($ipKey, self::RESET_PIN_REQUEST_MAX_ATTEMPTS, self::RESET_PIN_REQUEST_DECAY_SECONDS,
            'Terlalu banyak permintaan OTP dari perangkat ini. Silakan coba lagi dalam :minutes menit.'
        );

        $this->checkRateLimit($nikKey, self::RESET_PIN_REQUEST_MAX_ATTEMPTS, self::RESET_PIN_REQUEST_DECAY_SECONDS,
            'Terlalu banyak permintaan OTP. Silakan coba lagi dalam :minutes menit.'
        );

        $citizen = $this->citizenRepository->findByNikAndWhatsapp($data['nik'], $data['whatsapp_number']);

        if (!$citizen) {
            throw ValidationException::withMessages([
                'nik' => ['NIK atau nomor WhatsApp yang Anda masukkan tidak ditemukan.'],
            ]);
        }

        if (!$citizen->is_verified) {
            throw ValidationException::withMessages([
                'nik' => ['Akun belum terverifikasi. Silakan verifikasi terlebih dahulu.'],
            ]);
        }

        $temporaryPin = $this->generateOtp();

        RateLimiter::hit($nikKey, self::RESET_PIN_REQUEST_DECAY_SECONDS);
        RateLimiter::hit($ipKey, self::RESET_PIN_REQUEST_DECAY_SECONDS);

        $this->citizenRepository->update($citizen->id, [
            'temporary_pin'            => Hash::make($temporaryPin),
            'temporary_pin_expired_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        $message = $this->buildResetPinOtpMessage($citizen->full_name, $temporaryPin);

        SendWhatsAppJob::dispatch($citizen->whatsapp_number, $message)
            ->afterCommit();

        // OTP TIDAK dikembalikan — hanya via WhatsApp
    }

    // ===== RESET PIN =====

    /**
     * Reset PIN after OTP verification.
     * 
     * Security:
     * - All existing tokens revoked
     * - OTP must be valid & not expired
     * - New PIN hashed with bcrypt
     */
    public function resetPin(array $data): void
    {
        $citizen = $this->citizenRepository->findByNikAndWhatsapp($data['nik'], $data['whatsapp_number']);

        if (!$citizen) {
            throw ValidationException::withMessages([
                'nik' => ['NIK atau nomor WhatsApp yang Anda masukkan tidak valid.'],
            ]);
        }

        if (!$citizen->temporary_pin || !Hash::check($data['otp'], $citizen->temporary_pin)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP salah atau tidak valid.'],
            ]);
        }

        if ($this->citizenRepository->isOtpExpired($citizen)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP telah kedaluwarsa. Silakan minta kode baru.'],
            ]);
        }

        // Revoke semua token Sanctum
        PersonalAccessToken::where('tokenable_type', Citizen::class)
            ->where('tokenable_id', $citizen->id)
            ->delete();

        $this->citizenRepository->update($citizen->id, [
            'pin'                      => Hash::make($data['new_pin']),
            'temporary_pin'            => null,
            'temporary_pin_expired_at' => null,
        ]);
    }

    // ===== LOGOUT =====

    public function logout(Citizen $citizen): void
    {
        $citizen->currentAccessToken()?->delete();
    }

    // ===== PRIVATE HELPERS =====

    private function generateOtp(): string
    {
        return (string) random_int(self::OTP_MIN, self::OTP_MAX);
    }

    private function checkRateLimit(string $key, int $maxAttempts, int $decaySeconds, string $messageTemplate): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            $message = str_replace(':minutes', (string) $minutes, $messageTemplate);

            throw ValidationException::withMessages([
                'nik' => [$message],
            ]);
        }
    }

    private function buildOtpMessage(string $fullName, string $otp, string $label = 'KIRIM ULANG'): string
    {
        return "*[SABANA KALSEL - {$label}]*\n\n"
            . "Halo {$fullName},\n\n"
            . "Kode verifikasi baru Anda adalah:\n\n"
            . "*{$otp}*\n\n"
            . 'Berlaku ' . self::OTP_EXPIRY_MINUTES . " menit.\n"
            . 'Jangan berikan kode ini kepada siapa pun.';
    }

    private function buildResetPinOtpMessage(string $fullName, string $otp): string
    {
        return "Hallo {$fullName},\n\n"
            . "PIN sementara Anda untuk reset PIN di aplikasi SABANA:\n\n"
            . "*{$otp}*\n\n"
            . 'PIN ini berlaku selama ' . self::OTP_EXPIRY_MINUTES . " menit.\n"
            . 'Jangan bagikan PIN ini kepada siapa pun.';
    }

    /**
     * Send OTP to citizen without cooldown check.
     * Used internally when system auto-resends OTP.
     */
    private function sendOtpToCitizen(Citizen $citizen): void
    {
        $newOtp = $this->generateOtp();

        $this->citizenRepository->update($citizen->id, [
            'temporary_pin'            => Hash::make($newOtp),
            'temporary_pin_expired_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        $message = $this->buildOtpMessage($citizen->full_name, $newOtp);
        SendWhatsAppJob::dispatch($citizen->whatsapp_number, $message)->afterCommit();
    }
}