<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SpkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SpkController extends Controller
{
    public function __construct(
        private readonly SpkService $spkService
    ) {}

    /**
     * Daftar program aktif untuk card SPK.
     */
    public function programs(Request $request): JsonResponse
    {
        $search   = $request->query('search');
        $programs = $this->spkService->getActivePrograms($search);

        return response()->json([
            'status' => 'success',
            'data'   => $programs->map(fn($p) => [
                'id'                => $p->id,
                'name'              => $p->name,
                'description'       => $p->description,
                'quota_total'       => $p->quota_total,
                'criteria_count'    => count($p->criteria['inputs'] ?? []),
                'submissions_count' => $p->submissions_count ?? 0,
                'status'            => $p->status,
                'banner_url'        => $p->banner_url,
            ]),
        ]);
    }

    /**
     * Data SPK lengkap untuk satu program.
     */
    public function show(Request $request, string $programId): JsonResponse
    {
        try {
            $data = $this->spkService->getSpkData($programId, $request->only([
                'regency_id', 'district_id', 'village_id', 'status',
            ]));

            return response()->json([
                'status' => 'success',
                'data'   => $data,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}