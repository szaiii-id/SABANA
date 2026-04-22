<?php

namespace App\Services;

use App\Repositories\Contracts\CitizenRepositoryInterface;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class CitizenService
{
    protected $citizenRepository;
    protected $elasticsearch;
    protected $fonnteService; 

    public function __construct(CitizenRepositoryInterface $citizenRepository, Client $elasticsearch, FonnteService $fonnteService)
    {
        $this->citizenRepository = $citizenRepository;
        $this->elasticsearch = $elasticsearch;
        $this->fonnteService = $fonnteService;
    }

    public function registerCitizen(array $data)
    {
        $plainOtp = (string) random_int(100000, 999999);
        $hashedPin = Hash::make($data['pin']);
        $hashedOtp = Hash::make($plainOtp);
        $expiry = now()->addMinutes(10);

        DB::beginTransaction();
        try {
            $citizen = $this->citizenRepository->findByNik($data['nik']);

            if ($citizen) {
                if ($citizen->is_verified) {
                    throw new \Exception("NIK ini sudah terdaftar dan aktif.");
                }

                $this->citizenRepository->update($citizen->id, [
                    'family_card_number'       => $data['family_card_number'],
                    'full_name'                => $data['full_name'],
                    'whatsapp_number'          => $data['whatsapp_number'],
                    'pin'                      => $hashedPin,
                    'temporary_pin'            => $hashedOtp,
                    'temporary_pin_expired_at' => $expiry,
                ]);
                
                $citizen = $this->citizenRepository->findByNik($data['nik']);
            } else {
                $data['pin'] = $hashedPin;
                $data['temporary_pin'] = $hashedOtp;
                $data['temporary_pin_expired_at'] = $expiry;
                $data['is_verified'] = false;
                
                $citizen = $this->citizenRepository->create($data);
            }

            DB::commit();

            $message = "*[SABANA KALSEL]*\n\nHalo {$citizen->full_name}, kode verifikasi Anda adalah: *{$plainOtp}*";
            $this->fonnteService->sendMessage($citizen->whatsapp_number, $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DB Error Register: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }

        try {
            $this->elasticsearch->index([
                'index' => 'citizens',
                'id' => $citizen->id,
                'body' => [
                    'full_name' => $citizen->full_name,
                    'nik' => $citizen->nik,
                    'family_card_number' => $citizen->family_card_number,
                    'whatsapp_number' => $citizen->whatsapp_number,
                    'create_at' => $citizen->created_at->toDateTimeString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Elasticsearch Indexing failed " . $e->getMessage());
        }
        
        return $citizen;
    }
}