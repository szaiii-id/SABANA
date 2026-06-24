<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SearchEngineInterface;
use App\Jobs\IndexCitizenJob;
use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

final class CitizenService
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const OTP_LENGTH = 6;
    private const OTP_MIN = 100000;
    private const OTP_MAX = 999999;

    public function __construct(
        private readonly CitizenRepositoryInterface $citizenRepository,
        private readonly SearchEngineInterface $elasticsearch,
    ) {}

    /**
     * Register a new citizen or re-register an unverified one.
     * 
     * Flow:
     * 1. Lock row by NIK (prevents race condition)
     * 2. Validate state (verified / OTP still active)
     * 3. Create or update citizen
     * 4. Commit transaction
     * 5. Dispatch async jobs (WhatsApp + Elasticsearch indexing)
     *
     * @param array<string, string> $data Validated request data
     * @return Citizen
     * @throws Exception
     */
    public function registerCitizen(array $data): Citizen
    {
        $plainOtp = $this->generateOtp();
        $hashedPin = Hash::make($data['pin']);
        $hashedOtp = Hash::make($plainOtp);
        $otpExpiry = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        $citizen = DB::transaction(function () use ($data, $hashedPin, $hashedOtp, $otpExpiry, $plainOtp) {
            $existingCitizen = $this->citizenRepository->findByNikWithLock($data['nik']);

            if ($existingCitizen) {
                $this->validateExistingCitizen($existingCitizen);
                
                $this->reRegisterExpiredCitizen($existingCitizen, $data, $hashedPin, $hashedOtp, $otpExpiry);
                
                return $existingCitizen->refresh();
            }

            return $this->createNewCitizen($data, $hashedPin, $hashedOtp, $otpExpiry);
        });

        // === POST-COMMIT: Async jobs (tidak blocking transaksi) ===

        $this->dispatchWhatsAppMessage($citizen, $plainOtp);
        $this->dispatchElasticsearchIndexing($citizen);

        return $citizen;
    }

    /**
     * Get registration prefill data for unverified citizen.
     * Used when user wants to fix incorrect WhatsApp number.
     *
     * @param string $nik
     * @return array<string, string>
     * @throws Exception
     */
    public function getRegistrationData(string $nik): array
    {
        $citizen = $this->citizenRepository->findByNik($nik);

        if (!$citizen) {
            throw new Exception('Data pendaftar tidak ditemukan.');
        }

        if ($citizen->is_verified) {
            throw new Exception('Akun ini sudah terverifikasi. Tidak dapat mengubah data.');
        }

        return [
            'nik'                => $citizen->nik,
            'family_card_number' => $citizen->family_card_number,
            'full_name'          => $citizen->full_name,
            'whatsapp_number'    => $citizen->whatsapp_number,
        ];
    }

    // ===== PRIVATE HELPERS =====

    private function generateOtp(): string
    {
        return (string) random_int(self::OTP_MIN, self::OTP_MAX);
    }

    private function validateExistingCitizen(Citizen $citizen): void
    {
        if ($citizen->is_verified) {
            throw new Exception(
                'NIK ini sudah terdaftar. Gunakan fitur Lupa PIN jika Anda pemilik akun.'
            );
        }

        if (!$this->citizenRepository->isOtpExpired($citizen)) {
            $waitSeconds = (int) now()->diffInSeconds($citizen->temporary_pin_expired_at);
            
            if ($waitSeconds > 60) {
                $waitMinutes = (int) ceil($waitSeconds / 60);
                $waitMessage = "{$waitMinutes} menit";
            } else {
                $waitMessage = "{$waitSeconds} detik";
            }
            
            throw new Exception(
                "Kode verifikasi masih berlaku. Cek WhatsApp Anda atau tunggu {$waitMessage} untuk kirim ulang."
            );
        }
    }

    private function reRegisterExpiredCitizen(
        Citizen $citizen,
        array $data,
        string $hashedPin,
        string $hashedOtp,
        $otpExpiry
    ): void {
        Log::info('Citizen re-registration', [
            'nik'            => $data['nik'],
            'old_whatsapp'   => $citizen->whatsapp_number,
            'new_whatsapp'   => $data['whatsapp_number'],
            'reason'         => 'OTP expired, re-register with new data',
        ]);

        $this->citizenRepository->update($citizen->id, [
            'family_card_number'       => $data['family_card_number'],
            'full_name'                => $data['full_name'],
            'whatsapp_number'          => $data['whatsapp_number'],
            'pin'                      => $hashedPin,
            'temporary_pin'            => $hashedOtp,
            'temporary_pin_expired_at' => $otpExpiry,
        ]);
    }

    private function createNewCitizen(
        array $data,
        string $hashedPin,
        string $hashedOtp,
        $otpExpiry
    ): Citizen {
        return $this->citizenRepository->create([
            'nik'                      => $data['nik'],
            'family_card_number'       => $data['family_card_number'],
            'full_name'                => $data['full_name'],
            'whatsapp_number'          => $data['whatsapp_number'],
            'pin'                      => $hashedPin,
            'temporary_pin'            => $hashedOtp,
            'temporary_pin_expired_at' => $otpExpiry,
            'is_verified'              => false,
        ]);
    }

    private function dispatchWhatsAppMessage(Citizen $citizen, string $plainOtp): void
    {
        $message = $this->buildOtpMessage($citizen->full_name, $plainOtp);
        
        SendWhatsAppJob::dispatch($citizen->whatsapp_number, $message)
            ->afterCommit();
    }

    private function buildOtpMessage(string $fullName, string $plainOtp): string
    {
        return "*[SABANA KALSEL]*\n\n"
            . "Halo {$fullName},\n\n"
            . "Kode verifikasi akun Anda adalah:\n\n"
            . "*{$plainOtp}*\n\n"
            . 'Kode berlaku ' . self::OTP_EXPIRY_MINUTES . " menit. Jangan berikan kode ini kepada siapa pun.";
    }

    private function dispatchElasticsearchIndexing(Citizen $citizen): void
    {
        IndexCitizenJob::dispatch($citizen->id)
            ->afterCommit();
    }
}