<?php

namespace App\Repositories;

use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceEvidence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssistanceRepository implements AssistanceRepositoryInterface
{
    public function createSubmission(array $data): AssistanceSubmission
    {
        return AssistanceSubmission::create($data);
    }

    public function findActiveSubmission(string $citizenId): ?AssistanceSubmission
    {
        return AssistanceSubmission::where('citizen_id', $citizenId)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->first();
    }

    public function storeEvidence(array $evidenceData): void
    {
        AssistanceEvidence::create($evidenceData);
    }

    public function findByRegistrationNumber(string $registrationNumber): AssistanceSubmission
    {
        return AssistanceSubmission::with(['program', 'evidences'])
            ->where('registration_number', $registrationNumber)
            ->firstOrFail();
    }

    public function getHistoryByCitizenId(string $citizenId, int $perPage = 10): LengthAwarePaginator
    {
        return AssistanceSubmission::with(['program', 'evidences'])
            ->where('citizen_id', $citizenId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllHistoryByCitizenId(string $citizenId): Collection
    {
        return AssistanceSubmission::with(['program', 'evidences', 'evaluationLog', 'verifications'])
            ->where('citizen_id', $citizenId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function findById(string $id): AssistanceSubmission
    {
        return AssistanceSubmission::with([
            'citizen', 'program', 'evidences',
            'village', 'district', 'regency', 'regency.province', 'verifications',
            'evaluationLog', 'evaluationLogAsNew',
        ])->findOrFail($id);
    }
    
    public function deleteByRegistrationNumber(string $registrationNumber, string $citizenId): bool
    {
        $submission = AssistanceSubmission::where('registration_number', $registrationNumber)
            ->where('citizen_id', $citizenId)
            ->firstOrFail();

        // Soft delete evidence dulu
        $submission->evidences()->delete();
        
        // Baru soft delete submission
        return $submission->delete();
    }

    public function hasActiveSubmission(string $citizenId, string $programId): bool
    {
        return AssistanceSubmission::where('citizen_id', $citizenId)
            ->where('program_id', $programId)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->exists();
    }

    public function findByIdempotencyKey(string $citizenId, string $key): ?AssistanceSubmission
    {
        return AssistanceSubmission::where('citizen_id', $citizenId)
            ->where('submission_data->_idempotency_key', $key)
            ->first();
    }

    public function countActiveByProgram(string $programId): int
    {
        return AssistanceSubmission::where('program_id', $programId)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->count();
    }

    public function countByVillageToday(string $villageId): int
    {
        return AssistanceSubmission::where('village_id', $villageId)
            ->whereDate('created_at', now())
            ->whereNull('deleted_at')
            ->count();
    }

    public function countByKK(string $kkNumber, string $excludeProgramId): int
    {
        return AssistanceSubmission::where('submission_data->family_card_number', $kkNumber)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->where('program_id', '!=', $excludeProgramId)
            ->whereNull('deleted_at')
            ->count();
    }
}