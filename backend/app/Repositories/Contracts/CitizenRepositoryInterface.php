<?php 

namespace App\Repositories\Contracts;

use App\Models\Citizen;

interface CitizenRepositoryInterface
{
    public function create(array $data): Citizen;
    public function findByNik(string $id): ?Citizen;
    public function update(Citizen $citizen, array $data): bool;
    public function findByNikAndWhatsapp(string $nik, string $whatsapp): ?Citizen;
    public function isOtpExpired(Citizen $citizen): bool;
}

