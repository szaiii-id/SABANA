<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function regencyStats(Request $request, string $regencyId): JsonResponse
    {
        $data = $this->dashboardService->getRegencyStats($regencyId);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function districtDistribution(Request $request, string $regencyId): JsonResponse
    {
        $data = $this->dashboardService->getDistrictDistribution(
            $regencyId,
            $request->only(['tgl_mulai', 'tgl_akhir'])
        );

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function monthlyTrend(Request $request, string $regencyId): JsonResponse
    {
        $data = $this->dashboardService->getMonthlyTrend(
            $regencyId,
            $request->only(['tgl_mulai', 'tgl_akhir'])
        );

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function verificationStatus(string $regencyId): JsonResponse
    {
        $data = $this->dashboardService->getVerificationStatus($regencyId);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function topVillages(Request $request, string $regencyId): JsonResponse
    {
        $data = $this->dashboardService->getTopVillages(
            $regencyId,
            $request->only(['tgl_mulai', 'tgl_akhir'])
        );

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function districtStats(string $districtId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getDistrictStats($districtId),
        ]);
    }

    public function villageDistribution(Request $request, string $districtId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getVillageDistribution($districtId, $request->only(['tgl_mulai', 'tgl_akhir'])),
        ]);
    }

    public function districtMonthlyTrend(Request $request, string $districtId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getDistrictMonthlyTrend($districtId, $request->only(['tgl_mulai', 'tgl_akhir'])),
        ]);
    }

    public function districtVerificationStatus(string $districtId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getDistrictVerificationStatus($districtId),
        ]);
    }

    public function villageStats(string $villageId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getVillageStats($villageId),
        ]);
    }

    public function recentCitizens(string $villageId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->dashboardService->getRecentCitizens($villageId),
        ]);
    }

}