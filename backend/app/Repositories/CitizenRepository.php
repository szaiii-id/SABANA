<?php 

namespace App\Repositories;

use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;

class CitizenRepository implements CitizenRepositoryInterface
{
    public function create(array $data): Citizen
    {
        return Citizen::create($data);
    }

    public function findByNik(string $nik): ?Citizen
    {
         return Citizen::where('nik', $nik)->first();
    }

    public function findByNikAndWhatsapp(string $nik, string $whatsapp): ?Citizen
    {
        return Citizen::where('nik', $nik)
                      ->where('whatsapp_number', $whatsapp)
                      ->first();
    }
    
    public function update($id, array $data)
    {
        $citizen = Citizen::findOrFail($id);
        $citizen->update($data);
        return $citizen;
    }

    public function isOtpExpired(Citizen $citizen): bool
    {
        return now()->greaterThan($citizen->temporary_pin_expired_at);
    }

    public function updatePin(Citizen $citizen, string $hashedPin): bool
    {
        return $citizen->update(['pin' => $hashedPin]);
    }

}
