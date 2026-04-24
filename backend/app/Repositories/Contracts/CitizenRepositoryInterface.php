<?php 

namespace App\Repositories\Contracts;

use App\Models\Citizen;

interface CitizenRepositoryInterface
{
    public function create(array $data): Citizen;
    public function findByNik(string $id): ?Citizen;
    public function update($id, array $data);
    public function findByNikAndWhatsapp(string $nik, string $whatsapp): ?Citizen;
    public function isOtpExpired(Citizen $citizen): bool;
    public function updatePin(Citizen $citizen, string $hashedPin): bool;
}

