<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;
use App\Models\Disbursement;
use Illuminate\Pagination\LengthAwarePaginator;

interface DisbursementRepositoryInterface
{
    public function getByWilayah(Admin $admin, array $filters, int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Disbursement;
}