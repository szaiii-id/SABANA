<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Repositories\ActivityLogRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ActivityLogService
{
    public function __construct(
        private ActivityLogRepository $repository
    ) {}

    public function getAll(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAll($filters, $perPage);
    }
}