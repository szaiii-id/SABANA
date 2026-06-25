<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Models\Disbursement;
use App\Repositories\Contracts\DisbursementRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final class DisbursementRepository implements DisbursementRepositoryInterface
{
    public function getByWilayah(Admin $admin, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = AssistanceSubmission::query()
            ->with(['citizen', 'program', 'disbursement'])
            ->where('status', 'validated')
            ->whereDoesntHave('disbursement');

        if ($admin->isVillageOfficer()) {
            $query->where('village_id', $admin->village_id);
        } elseif ($admin->isDistrictAdmin()) {
            $query->where('district_id', $admin->district_id);
        } elseif ($admin->isRegencyAdmin()) {
            $query->where('regency_id', $admin->regency_id);
        }

        if (!empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('citizen', function ($cq) use ($filters) {
                    $cq->where('full_name', 'like', "%{$filters['search']}%")
                       ->orWhere('nik', 'like', "%{$filters['search']}%");
                });
            });
        }

        return $query->latest('updated_at')->paginate($perPage);
    }

    public function create(array $data): Disbursement
    {
        return Disbursement::create($data);
    }
}