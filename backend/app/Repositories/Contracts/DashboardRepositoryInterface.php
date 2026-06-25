<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    public function getRegencyStats(string $regencyId): array;
    public function getDistrictDistribution(string $regencyId, array $filters): array;
    public function getMonthlyTrend(string $regencyId, array $filters): array;
    public function getVerificationStatus(string $regencyId): array;
    public function getTopVillages(string $regencyId, array $filters): array;

    public function getDistrictStats(string $districtId): array;
    public function getVillageDistribution(string $districtId, array $filters): array;
    public function getDistrictMonthlyTrend(string $districtId, array $filters): array;
    public function getDistrictVerificationStatus(string $districtId): array;

    public function getVillageStats(string $villageId): array;
    public function getRecentCitizens(string $villageId): array;
}