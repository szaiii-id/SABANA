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

    public function __construct(CitizenRepositoryInterface $citizenRepository, Client $elasticsearch)
    {
        $this->citizenRepository = $citizenRepository;
        $this->elasticsearch = $elasticsearch;
    }

    public function registerCitizen(array $data)
    {
        $data['pin'] = Hash::make($data['pin']);

        DB::beginTransaction();
        try {
            $citizen = $this->citizenRepository->create($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DB Error " . $e->getMessage());
            throw new \Exception("Register Failed");
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