<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\SubmissionSearchDTO;
use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Models\SubmissionVerification;
use App\Repositories\Contracts\VerificationRepositoryInterface;
use App\Services\Admin\SubmissionSearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class VerificationRepository implements VerificationRepositoryInterface
{
    public function __construct(
        private readonly SubmissionSearchService $searchService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | LIST — PAKAI ELASTICSEARCH (PENGGANTI LIKE) + REDIS CACHE
    |--------------------------------------------------------------------------
    */

    public function getByWilayah(Admin $admin, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = 'verification_list:' . md5(serialize([
            'admin_id'    => $admin->id,
            'filters'     => $filters,
            'per_page'    => $perPage,
            'page'        => request()->get('page', 1),
        ]));

        // ✅ Cache hanya total count + IDs, bukan Collection
        $cachedResult = Cache::tags(['verification-list'])->get($cacheKey);

        if ($cachedResult && isset($cachedResult['ids'], $cachedResult['total'])) {
            $submissions = AssistanceSubmission::with([
                'citizen', 'program' => fn($q) => $q->withTrashed(), 'evidences',
                'village', 'district', 'regency', 'verifications', 'disbursement',
            ])
            ->whereIn('id', $cachedResult['ids'])
            ->orderByRaw('ARRAY_POSITION(ARRAY[\'' . implode("','", $cachedResult['ids']) . '\']::uuid[], id::uuid)')
            ->get();

            return new LengthAwarePaginator(
                $submissions,
                $cachedResult['total'],
                $perPage,
                (int) request()->get('page', 1)
            );
        }

        // Jika tidak ada di cache, query via Elasticsearch
        $dto = SubmissionSearchDTO::fromArray([
            'search'       => $filters['search'] ?? null,
            'status'       => $filters['status'] ?? null,
            'program_id'   => $filters['program_id'] ?? null,
            'village_id'   => $admin->isVillageOfficer() ? $admin->village_id : null,
            'district_id'  => $admin->isDistrictAdmin() ? $admin->district_id : null,
            'regency_id'   => $admin->isRegencyAdmin() ? $admin->regency_id : null,
            'page'         => (int) request()->get('page', 1),
            'per_page'     => $perPage,
            'sort_by'      => 'smart_score',
            'sort_direction' => 'desc',
        ]);

        $result = $this->searchService->search($dto);

        // Simpan ID ke cache
        if ($result->total() > 0) {
            Cache::tags(['verification-list'])->put($cacheKey, [
                'ids'   => $result->pluck('id')->toArray(),
                'total' => $result->total(),
            ], 30);
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL — DENGAN RELASI LENGKAP
    |--------------------------------------------------------------------------
    */

    public function findById(string $id): ?AssistanceSubmission
    {
        return AssistanceSubmission::with([
            'citizen',
            'program' => fn ($q) => $q->withTrashed(),
            'evidences',
            'village',
            'district',
            'regency',
            'verifications',
            'disbursement',
        ])->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL DENGAN LOCK — UNTUK RACE CONDITION PREVENTION
    |--------------------------------------------------------------------------
    */

    public function findByIdWithLock(string $id): ?AssistanceSubmission
    {
        return AssistanceSubmission::with([
            'citizen',
            'program' => fn ($q) => $q->withTrashed(),
            'evidences',
            'village',
            'district',
            'regency',
            'verifications',
            'disbursement',
        ])
        ->where('id', $id)
        ->lockForUpdate()
        ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function updateStatus(AssistanceSubmission $submission, string $status): bool
    {
        return $submission->update(['status' => $status]);
    }

    public function createVerificationRecord(array $data): void
    {
        SubmissionVerification::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    public function clearVerificationListCache(): void
    {
        Cache::tags(['verification-list'])->flush();
    }
}