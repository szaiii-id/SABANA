<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Admin;
use App\Models\AssistanceSubmission;
use Illuminate\Pagination\LengthAwarePaginator;

interface VerificationRepositoryInterface
{
    /**
     * Get paginated list of submissions filtered by admin wilayah.
     * Uses Elasticsearch for search, falls back to DB if ES is down.
     */
    public function getByWilayah(Admin $admin, array $filters, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Find submission by ID with all relations.
     */
    public function findById(string $id): ?AssistanceSubmission;
    
    /**
     * Find submission by ID with pessimistic lock for race condition prevention.
     */
    public function findByIdWithLock(string $id): ?AssistanceSubmission;
    
    /**
     * Update submission status.
     */
    public function updateStatus(AssistanceSubmission $submission, string $status): bool;
    
    /**
     * Create verification audit record.
     */
    public function createVerificationRecord(array $data): void;

    /**
     * Clear verification list cache.
     */
    public function clearVerificationListCache(): void;
}