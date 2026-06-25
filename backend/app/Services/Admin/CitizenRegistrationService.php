<?php

namespace App\Services\Admin;

use App\Jobs\IndexCitizenJob;
use App\Jobs\SendWhatsAppJob;
use App\Models\Admin;
use App\Models\Citizen;
use App\Models\CitizenRegistrationLog;
use App\Repositories\Contracts\CitizenRegistrationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class CitizenRegistrationService
{
    public function __construct(
        private CitizenRegistrationRepositoryInterface $repository,
    ) {}

    // ===== REGISTER WITH PIN =====

    public function registerWithPin(array $data, string $adminId): array
    {
        $result = DB::transaction(function () use ($data, $adminId) {
            
            $existing = $this->repository->findByNikWithLock($data['nik']);

            if ($existing && $existing->is_verified) {
                throw new Exception('NIK ini sudah terdaftar dan aktif.');
            }

            if (empty($data['whatsapp_number'])) {
                throw new Exception('Nomor WhatsApp wajib diisi untuk mengirim PIN akses.');
            }

            $accessPin = (string) random_int(100000, 999999);
            $admin = Admin::find($adminId);

            $citizenData = [
                'nik'                       => $data['nik'],
                'full_name'                 => $data['full_name'],
                'family_card_number'        => $data['family_card_number'],
                'whatsapp_number'           => $data['whatsapp_number'],
                'is_verified'               => true,
                'pin'                       => Hash::make($accessPin),
                'temporary_pin'             => null,
                'temporary_pin_expired_at'  => null,
            ];

            if ($existing) {
                $citizen = $this->repository->update($existing, $citizenData);
            } else {
                $citizen = $this->repository->create($citizenData);
            }

            $message = "*[SABANA KALSEL]*\n\n"
                    . "Halo {$citizen->full_name},\n\n"
                    . "Akun Anda telah didaftarkan oleh Admin.\n\n"
                    . "PIN Akses: *{$accessPin}*\n\n"
                    . "Gunakan PIN ini untuk login ke aplikasi SABANA.\n"
                    . "Segera ganti PIN Anda setelah login.";

            CitizenRegistrationLog::create([
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
                'admin_name' => $admin?->name,
                'admin_role' => $admin?->role,
                'action'     => 'register_with_pin',
                'metadata'   => [
                    'nik'             => $data['nik'],
                    'whatsapp_number' => $data['whatsapp_number'],
                ],
            ]);

            Log::info('CitizenRegistration: Registered with PIN', [
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
            ]);

            return [
                'citizen'     => $citizen,
                'access_pin'  => $accessPin,
                'whatsapp'    => $citizen->whatsapp_number,
                'message'     => $message,
            ];
        });

        // === POST-COMMIT: Async jobs ===
        SendWhatsAppJob::dispatch($result['whatsapp'], $result['message'])->afterCommit();
        IndexCitizenJob::dispatch($result['citizen']->id)->afterCommit();

        return [
            'citizen'    => $result['citizen'],
            'access_pin' => $result['access_pin'],
        ];
    }

    // ===== REGISTER WITHOUT PIN =====

    public function registerWithoutPin(array $data, string $adminId): array
    {
        $result = DB::transaction(function () use ($data, $adminId) {
            
            $existing = $this->repository->findByNikWithLock($data['nik']);

            if ($existing && $existing->is_verified) {
                throw new Exception('NIK ini sudah terdaftar dan aktif.');
            }

            $randomPin = (string) random_int(100000, 999999);
            $admin = Admin::find($adminId);

            $citizenData = [
                'nik'                       => $data['nik'],
                'full_name'                 => $data['full_name'],
                'family_card_number'        => $data['family_card_number'],
                'whatsapp_number'           => $data['whatsapp_number'] ?? null,
                'is_verified'               => true,
                'pin'                       => Hash::make($randomPin),
                'temporary_pin'             => null,
                'temporary_pin_expired_at'  => null,
            ];

            if ($existing) {
                $citizen = $this->repository->update($existing, $citizenData);
            } else {
                $citizen = $this->repository->create($citizenData);
            }

            CitizenRegistrationLog::create([
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
                'admin_name' => $admin?->name,
                'admin_role' => $admin?->role,
                'action'     => 'register_without_pin',
                'metadata'   => [
                    'nik'             => $data['nik'],
                    'whatsapp_number' => $data['whatsapp_number'] ?? null,
                ],
            ]);

            Log::info('CitizenRegistration: Registered without PIN', [
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
            ]);

            return [
                'citizen'    => $citizen,
                'access_pin' => $randomPin,
            ];
        });

        // === POST-COMMIT: Async indexing ===
        IndexCitizenJob::dispatch($result['citizen']->id)->afterCommit();

        return [
            'citizen'    => $result['citizen'],
            'access_pin' => $result['access_pin'],
        ];
    }

    // ===== RESEND ACCESS PIN =====

    public function resendAccessPin(string $citizenId, string $adminId): array
    {
        $result = DB::transaction(function () use ($citizenId, $adminId) {
            $citizen = Citizen::findOrFail($citizenId);

            if (!$citizen->whatsapp_number) {
                throw new Exception('Warga tidak memiliki nomor WhatsApp.');
            }

            $newPin = (string) random_int(100000, 999999);
            $admin = Admin::find($adminId);

            $this->repository->update($citizen, [
                'pin' => Hash::make($newPin),
            ]);

            $message = "*[SABANA KALSEL - PIN BARU]*\n\n"
                    . "Halo {$citizen->full_name},\n\n"
                    . "PIN Akses baru Anda: *{$newPin}*\n\n"
                    . "Gunakan PIN ini untuk login ke aplikasi SABANA.";

            CitizenRegistrationLog::create([
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
                'admin_name' => $admin?->name,
                'admin_role' => $admin?->role,
                'action'     => 'resend_pin',
                'metadata'   => [
                    'whatsapp_number' => $citizen->whatsapp_number,
                ],
            ]);

            Log::info('CitizenRegistration: PIN resent', ['citizen_id' => $citizen->id]);

            return [
                'access_pin' => $newPin,
                'whatsapp'   => $citizen->whatsapp_number,
                'message'    => $message,
            ];
        });

        // === POST-COMMIT ===
        SendWhatsAppJob::dispatch($result['whatsapp'], $result['message'])->afterCommit();

        return [
            'access_pin' => $result['access_pin'],
        ];
    }

    // ===== UPDATE CITIZEN =====

    public function updateCitizen(string $citizenId, array $data, string $adminId): Citizen
    {
        $result = DB::transaction(function () use ($citizenId, $data, $adminId) {
            
            $citizen = Citizen::findOrFail($citizenId);
            $admin = Admin::find($adminId);

            if (!empty($data['nik']) && $data['nik'] !== $citizen->nik) {
                $exists = $this->repository->findByNik($data['nik']);
                if ($exists && $exists->id !== $citizen->id) {
                    throw new Exception('NIK sudah digunakan oleh warga lain.');
                }
            }

            $updateData = [
                'full_name'          => $data['full_name'] ?? $citizen->full_name,
                'family_card_number' => $data['family_card_number'] ?? $citizen->family_card_number,
                'whatsapp_number'    => $data['whatsapp_number'] ?? $citizen->whatsapp_number,
            ];

            if (!empty($data['nik'])) {
                $updateData['nik'] = $data['nik'];
            }

            $oldData = [
                'nik'                => $citizen->nik,
                'full_name'          => $citizen->full_name,
                'family_card_number' => $citizen->family_card_number,
                'whatsapp_number'    => $citizen->whatsapp_number,
            ];

            $citizen = $this->repository->update($citizen, $updateData);

            CitizenRegistrationLog::create([
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
                'admin_name' => $admin?->name,
                'admin_role' => $admin?->role,
                'action'     => 'update_data',
                'metadata'   => [
                    'old' => $oldData,
                    'new' => $updateData,
                ],
            ]);

            Log::info('CitizenRegistration: Citizen updated', [
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
            ]);

            return $citizen;
        });

        // === POST-COMMIT ===
        IndexCitizenJob::dispatch($result->id)->afterCommit();

        return $result;
    }

    // ===== RESET PIN AND GET CARD DATA =====

    public function resetPinAndGetCardData(string $citizenId, string $adminId): array
    {
        $result = DB::transaction(function () use ($citizenId, $adminId) {
            $citizen = Citizen::findOrFail($citizenId);
            $newPin = (string) random_int(100000, 999999);
            $admin = Admin::find($adminId);

            $this->repository->update($citizen, [
                'pin' => Hash::make($newPin),
            ]);

            $message = null;
            if ($citizen->whatsapp_number) {
                $message = "*[SABANA KALSEL - PIN BARU]*\n\n"
                        . "Halo {$citizen->full_name},\n\n"
                        . "PIN Akses baru Anda: *{$newPin}*\n\n"
                        . "Gunakan PIN ini untuk login ke aplikasi SABANA.";
            }

            CitizenRegistrationLog::create([
                'citizen_id' => $citizen->id,
                'admin_id'   => $adminId,
                'admin_name' => $admin?->name,
                'admin_role' => $admin?->role,
                'action'     => 'resend_pin',
                'metadata'   => [
                    'whatsapp_number' => $citizen->whatsapp_number,
                    'printed'         => true,
                ],
            ]);

            Log::info('CitizenRegistration: PIN reset and card printed', ['citizen_id' => $citizen->id]);

            return [
                'citizen'    => $citizen->fresh(),
                'access_pin' => $newPin,
                'whatsapp'   => $citizen->whatsapp_number,
                'message'    => $message,
            ];
        });

        // === POST-COMMIT ===
        if ($result['whatsapp'] && $result['message']) {
            SendWhatsAppJob::dispatch($result['whatsapp'], $result['message'])->afterCommit();
        }

        return [
            'citizen'    => $result['citizen'],
            'access_pin' => $result['access_pin'],
        ];
    }

    // ===== SEARCH METHODS =====

    public function searchCitizen(string $query): array
    {
        return $this->repository->search($query);
    }

    public function getCitizenList(array $filters, int $perPage = 15)
    {
        return $this->repository->getList($filters, $perPage);
    }

    public function searchCitizenPaginated(string $query, int $page = 1, int $perPage = 10): array
    {
        return $this->repository->searchPaginated($query, $page, $perPage);
    }
}