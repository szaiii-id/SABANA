<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class CitizenProfileService
{
    public function __construct(
        private readonly CitizenRepositoryInterface $citizenRepository,
    ) {}

    public function updateProfile(Citizen $citizen, array $data): Citizen
    {
        if (isset($data['whatsapp_number'])) {
            $this->handleWhatsAppChange($citizen, $data);
        }

        $updateData = [];

        if (isset($data['full_name'])) {
            $updateData['full_name'] = $data['full_name'];
        }

        if (isset($data['whatsapp_number'])) {
            $updateData['whatsapp_number'] = $data['whatsapp_number'];
        }

        if (empty($updateData)) {
            return $citizen;
        }

        $this->citizenRepository->update($citizen->id, $updateData);
        return $citizen->refresh();
    }

    private function handleWhatsAppChange(Citizen $citizen, array $data): void
    {
        $key = 'change-wa:' . $citizen->id;

        if (RateLimiter::tooManyAttempts($key, 2)) {
            $minutes = ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages([
                'whatsapp_number' => ["Terlalu banyak perubahan nomor. Silakan coba lagi dalam {$minutes} menit."],
            ]);
        }

        $currentPhone = $this->normalizePhone($citizen->whatsapp_number);
        $newPhone = $this->normalizePhone($data['whatsapp_number']);

        if ($newPhone === $currentPhone) {
            return;
        }

        RateLimiter::hit($key, 3600);

        // Revoke semua token — force re-login
        $citizen->tokens()->delete();

        $plainOtp = (string) random_int(100000, 999999);

        $updateData = [
            'whatsapp_number'          => $data['whatsapp_number'],
            'is_verified'              => false,
            'temporary_pin'            => Hash::make($plainOtp),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ];

        if (isset($data['full_name'])) {
            $updateData['full_name'] = $data['full_name'];
        }

        $this->citizenRepository->update($citizen->id, $updateData);

        Log::info('WhatsApp number changed', [
            'citizen_id'   => $citizen->id,
            'old_whatsapp' => $citizen->whatsapp_number,
            'new_whatsapp' => $data['whatsapp_number'],
        ]);

        $name = $data['full_name'] ?? $citizen->full_name;
        $message = "*[SABANA KALSEL]*\n\nHalo {$name},\n\nKode verifikasi untuk nomor WhatsApp baru Anda adalah:\n\n*{$plainOtp}*\n\nBerlaku 10 menit. Jangan berikan kode ini kepada siapa pun.";
        
        SendWhatsAppJob::dispatch($data['whatsapp_number'], $message)
            ->afterCommit();

        throw ValidationException::withMessages([
            'whatsapp_number' => ['Nomor WhatsApp berhasil diubah. Silakan verifikasi nomor baru Anda melalui kode OTP yang telah dikirim.'],
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        }

        return $cleaned;
    }
}