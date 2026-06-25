<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Admin;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminRepositoryInterface
{
    public function findByNip(string $nip): ?Admin;
    public function updateLastLogin(string $id): void;
    public function getHierarchicalAdmins(Admin $actor, array $filters, int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Admin;
    public function findById(string $id): ?Admin;
    public function update(Admin $admin, array $data): bool;
    public function delete(Admin $admin): bool;
    public function restore(Admin $admin): bool;
}