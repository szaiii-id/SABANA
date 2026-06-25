<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Repositories\Contracts\DashboardRepositoryInterface;

final readonly class DashboardService
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function getRegencyStats(string $regencyId): array
    {
        return $this->dashboardRepository->getRegencyStats($regencyId);
    }

    public function getDistrictDistribution(string $regencyId, array $filters): array
    {
        return $this->dashboardRepository->getDistrictDistribution($regencyId, $filters);
    }

    public function getMonthlyTrend(string $regencyId, array $filters): array
    {
        return $this->dashboardRepository->getMonthlyTrend($regencyId, $filters);
    }

    public function getVerificationStatus(string $regencyId): array
    {
        return $this->dashboardRepository->getVerificationStatus($regencyId);
    }

    public function getTopVillages(string $regencyId, array $filters): array
    {
        return $this->dashboardRepository->getTopVillages($regencyId, $filters);
    }

    public function getDistrictStats(string $districtId): array
    {
        return $this->dashboardRepository->getDistrictStats($districtId);
    }

    public function getVillageDistribution(string $districtId, array $filters): array
    {
        return $this->dashboardRepository->getVillageDistribution($districtId, $filters);
    }

    public function getDistrictMonthlyTrend(string $districtId, array $filters): array
    {
        return $this->dashboardRepository->getDistrictMonthlyTrend($districtId, $filters);
    }

    public function getDistrictVerificationStatus(string $districtId): array
    {
        return $this->dashboardRepository->getDistrictVerificationStatus($districtId);
    }

    public function getVillageStats(string $villageId): array
    {
        return $this->dashboardRepository->getVillageStats($villageId);
    }

    public function getRecentCitizens(string $villageId): array
    {
        return $this->dashboardRepository->getRecentCitizens($villageId);
    }
}