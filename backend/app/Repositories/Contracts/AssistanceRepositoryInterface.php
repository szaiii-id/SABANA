<?php

namespace App\Repositories\Contracts;

use App\Models\AssistanceSubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AssistanceRepositoryInterface
{
    public function createSubmission(array $data): AssistanceSubmission;
    public function findActiveSubmission(string $citizenId): ?AssistanceSubmission;
    public function storeEvidence(array $evidenceData): void;
    public function findByRegistrationNumber(string $registrationNumber): AssistanceSubmission;
    public function getHistoryByCitizenId(string $citizenId, int $perPage = 10): LengthAwarePaginator;
    public function findById(string $id): AssistanceSubmission;
    public function deleteByRegistrationNumber(string $registrationNumber, string $citizenId): bool;
    public function hasActiveSubmission(string $citizenId, string $programId): bool;
    public function findByIdempotencyKey(string $citizenId, string $key): ?AssistanceSubmission;
    public function countActiveByProgram(string $programId): int;
    public function countByVillageToday(string $villageId): int;
    public function countByKK(string $kkNumber, string $excludeProgramId): int;
    public function getAllHistoryByCitizenId(string $citizenId): Collection;
}