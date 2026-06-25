<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Admin;
use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class AdminRepository implements AdminRepositoryInterface
{
    public function findByNip(string $nip): ?Admin
    {
        return Admin::where('nip', $nip)->first();
    }

    public function updateLastLogin(string $id): void
    {
        Admin::where('id', $id)->update(['last_login_at' => now()]);
    }

    public function getHierarchicalAdmins(Admin $actor, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Admin::withTrashed()->with(['regency', 'district', 'village']);

        $query->where('role', '!=', 'super_admin');

        if ($actor->isRegencyAdmin()) {
            $query->where('regency_id', $actor->regency_id)
                  ->whereIn('role', ['district_admin', 'village_officer']);
        } elseif ($actor->isDistrictAdmin()) {
            $query->where('district_id', $actor->district_id)
                  ->where('role', 'village_officer');
        }

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->where(function ($q) use ($filters, $search) {
                $q->where('nip', 'like', "%{$filters['search']}%")
                  ->orWhere(DB::raw('LOWER(name)'), 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Admin
    {
        return Admin::create($data);
    }

    public function findById(string $id): ?Admin
    {
        return Admin::withTrashed()->find($id);
    }

    public function update(Admin $admin, array $data): bool
    {
        return $admin->update($data);
    }

    public function delete(Admin $admin): bool
    {
        return $admin->delete();
    }

    public function restore(Admin $admin): bool
    {
        return $admin->restore();
    }
}