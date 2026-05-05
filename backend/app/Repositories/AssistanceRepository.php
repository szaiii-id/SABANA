<?php
namespace App\Repositories;

use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceEvidence;

class AssistanceRepository implements AssistanceRepositoryInterface {
    
    public function createSubmission(array $data): object {
        return AssistanceSubmission::create($data);
    }

    public function findActiveSubmission(int $citizenId): ?object {
        return AssistanceSubmission::where('citizen_id', $citizenId)
            ->whereIn('status', ['pending', 'validated'])
            ->first();
    }

    public function storeEvidence(array $evidenceData): void {
        AssistanceEvidence::create($evidenceData);
    }

    public function findByRegistrationNumber(string $registrationNumber): object {
        return AssistanceSubmission::with(['program', 'evidences'])
            ->where('registration_number', $registrationNumber)
            ->firstOrFail();
    }

    public function getHistoryByCitizenId(string $citizenId): object {
        return AssistanceSubmission::with(['program','evidences']) 
            ->where('citizen_id', $citizenId)
            ->orderBy('created_at', 'desc') 
            ->get();
    }
    public function findById(string $id): object {
        return AssistanceSubmission::with(['citizen', 'program', 'evidences', 'village', 'district', 'regency', 'regency.province'])->findOrFail($id);
    }
    
    public function deleteByRegistrationNumber(string $registrationNumber, string $citizenId): bool {
    $submission = AssistanceSubmission::where('registration_number', $registrationNumber)
        ->where('citizen_id', $citizenId)
        ->firstOrFail();
    
    return $submission->forceDelete();
}
}