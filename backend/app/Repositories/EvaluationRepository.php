<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\EvaluationStatus;
use App\Models\Admin;
use App\Models\EvaluationLog;
use App\Repositories\Contracts\EvaluationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class EvaluationRepository implements EvaluationRepositoryInterface
{
    public function getByWilayah(Admin $admin, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = EvaluationLog::query()
            ->with([
                'submission.citizen',
                'submission.program',
                'submission.village',
                'submission.district',
                'submission.regency',
                'newSubmission',
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            if ($admin->isVillageOfficer() || $admin->isSuperAdmin()) {
                $query->whereIn('status', EvaluationStatus::active());
            } else {
                $query->where('status', EvaluationStatus::UPDATED->value);
            }
        }

        if ($admin->isVillageOfficer()) {
            $query->where('village_id', $admin->village_id);
        } elseif ($admin->isDistrictAdmin()) {
            $query->where('district_id', $admin->district_id);
        } elseif ($admin->isRegencyAdmin()) {
            $query->where('regency_id', $admin->regency_id);
        }

        return $query->latest('updated_at')->paginate($perPage);
    }

    public function findBySubmission(string $submissionId): ?EvaluationLog
    {
        return EvaluationLog::where('submission_id', $submissionId)
            ->whereIn('status', EvaluationStatus::active())
            ->first();
    }

    public function findByNewSubmission(string $submissionId): ?EvaluationLog
    {
        return EvaluationLog::where('new_submission_id', $submissionId)
            ->where('status', EvaluationStatus::UPDATED->value)
            ->first();
    }

    public function updateStatus(EvaluationLog $evaluationLog, array $data): bool
    {
        return $evaluationLog->update($data);
    }
}