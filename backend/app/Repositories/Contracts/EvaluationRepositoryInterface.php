<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;
use App\Models\EvaluationLog;
use Illuminate\Pagination\LengthAwarePaginator;

interface EvaluationRepositoryInterface
{
    public function getByWilayah(Admin $admin, array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findBySubmission(string $submissionId): ?EvaluationLog;
    public function findByNewSubmission(string $submissionId): ?EvaluationLog;
    public function updateStatus(EvaluationLog $evaluationLog, array $data): bool;
}