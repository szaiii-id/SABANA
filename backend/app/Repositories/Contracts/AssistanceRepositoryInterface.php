<?php
namespace App\Repositories\Contracts;

interface AssistanceRepositoryInterface {
    public function createSubmission(array $data): object;
    public function findActiveSubmission(int $citizenId): ?object;
    public function storeEvidence(array $evidenceData): void;
    public function findByRegistrationNumber(string $registrationNumber): object;
    public function getHistoryByCitizenId(string $citizenId): object;
    public function findById(string $id): object;
    public function deleteByRegistrationNumber(string $registrationNumber, string $citizenId): bool;
  
}