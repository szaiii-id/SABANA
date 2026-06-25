<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ActivityLogs\Loggable;
use App\Models\ActivityLog;
use App\Models\AdminAssistanceLog;
use App\Models\CitizenRegistrationLog;
use App\Models\Disbursement;
use App\Models\EvaluationLog;
use App\Models\SubmissionVerification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ActivityLogRepository
{
    private array $sources = [
        ActivityLog::class,
        SubmissionVerification::class,
        Disbursement::class,
        EvaluationLog::class,
        CitizenRegistrationLog::class,
        AdminAssistanceLog::class,
    ];

    public function getAll(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $allLogs = collect();

        foreach ($this->sources as $source) {
            $allLogs = $allLogs->concat($this->fetchFromSource($source, $filters));
        }

        $allLogs = $allLogs->sortByDesc('created_at')->values();

        if (!empty($filters['actor_name'])) {
            $allLogs = $allLogs->filter(fn($log) => str_contains(strtolower($log['actor_name']), strtolower($filters['actor_name'])));
        }
        if (!empty($filters['actor_role'])) {
            $allLogs = $allLogs->filter(fn($log) => ($log['actor_role'] ?? '') === $filters['actor_role']);
        }
        if (!empty($filters['module'])) {
            $allLogs = $allLogs->filter(fn($log) => $log['module'] === $filters['module']);
        }

        $allLogs = $allLogs->values();

        $page = (int) request()->get('page', 1);
        $total = $allLogs->count();
        $items = $allLogs->forPage($page, $perPage)->values()->toArray();

        return new LengthAwarePaginator($items, $total, $perPage, $page);
    }

    private function fetchFromSource(string $modelClass, array $filters): Collection
    {
        $query = $modelClass::query();

        if (method_exists($modelClass, 'scopeDateFilter')) {
            $query->dateFilter($filters);
        }

        if ($modelClass === SubmissionVerification::class) {
            $query->with('submission');
        } elseif ($modelClass === Disbursement::class) {
            $query->with(['submission', 'officer']);
        } elseif ($modelClass === EvaluationLog::class) {
            $query->with(['submission', 'decider']);
        }

        return $query->get()->map(fn(Loggable $model) => $model->toActivityLog());
    }
}