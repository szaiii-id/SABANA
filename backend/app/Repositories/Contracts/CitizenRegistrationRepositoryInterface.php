<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Citizen;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CitizenRegistrationRepositoryInterface
{
    public function findByNik(string $nik): ?Citizen;
    public function findByNikWithLock(string $nik): ?Citizen;
    public function create(array $data): Citizen;
    public function update(Citizen $citizen, array $data): Citizen;
    public function search(string $query): array;
    public function searchPaginated(string $query, int $page = 1, int $perPage = 10): array;
    public function getList(array $filters, int $perPage = 15): LengthAwarePaginator;
}