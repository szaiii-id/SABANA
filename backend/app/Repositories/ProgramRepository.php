<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AssistanceProgram;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use App\Services\Admin\ProgramSearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ProgramRepository implements ProgramRepositoryInterface
{
    public function __construct(
        private readonly ProgramSearchService $searchService
    ) {}

    public function getAll(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // Jika ada pencarian → gunakan Elasticsearch
        if (!empty($filters['search'])) {
            return $this->searchService->search($filters, $perPage);
        }

        // Jika hanya filter status → query DB langsung
        $query = AssistanceProgram::query()
            ->withCount('submissions');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id): ?AssistanceProgram
    {
        return AssistanceProgram::withCount('submissions')->find($id);
    }

    public function create(array $data): AssistanceProgram
    {
        return AssistanceProgram::create($data);
    }

    public function update(AssistanceProgram $program, array $data): bool
    {
        return $program->update($data);
    }

    public function delete(AssistanceProgram $program): bool
    {
        return $program->delete();
    }

    public function getActive(): Collection
    {
        return AssistanceProgram::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->latest()
            ->get();
    }

    public function existsByNameExcludingId(string $name, string $excludeId): bool
    {
        return AssistanceProgram::where('name', $name)
            ->where('id', '!=', $excludeId)
            ->whereNull('deleted_at')
            ->exists();
    }
}