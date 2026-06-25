<?php

namespace App\Repositories\Contracts;

use App\Models\AssistanceProgram;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProgramRepositoryInterface
{
    public function getAll(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findById(string $id): ?AssistanceProgram;
    public function create(array $data): AssistanceProgram;
    public function update(AssistanceProgram $program, array $data): bool;
    public function delete(AssistanceProgram $program): bool;
    public function getActive(): Collection;
    public function existsByNameExcludingId(string $name, string $excludeId): bool;
}