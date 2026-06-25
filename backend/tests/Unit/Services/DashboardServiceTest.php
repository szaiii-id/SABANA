<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Services\Admin\DashboardService;
use Mockery;
use Tests\TestCase;

final class DashboardServiceTest extends TestCase
{
    private DashboardRepositoryInterface $repository;
    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(DashboardRepositoryInterface::class);
        $this->service = new DashboardService($this->repository);
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_get_regency_stats_delegates_to_repository(): void
    {
        $this->repository
            ->shouldReceive('getRegencyStats')
            ->with('6301')
            ->once()
            ->andReturn(['totalPenerima' => 10]);

        $result = $this->service->getRegencyStats('6301');

        $this->assertEquals(['totalPenerima' => 10], $result);
    }

    public function test_get_district_distribution_delegates(): void
    {
        $this->repository
            ->shouldReceive('getDistrictDistribution')
            ->with('6301', [])
            ->once()
            ->andReturn([]);

        $result = $this->service->getDistrictDistribution('6301', []);

        $this->assertIsArray($result);
    }

    public function test_get_monthly_trend_delegates(): void
    {
        $this->repository
            ->shouldReceive('getMonthlyTrend')
            ->once()
            ->andReturn([]);

        $result = $this->service->getMonthlyTrend('6301', []);

        $this->assertIsArray($result);
    }

    public function test_get_district_stats_delegates(): void
    {
        $this->repository
            ->shouldReceive('getDistrictStats')
            ->once()
            ->andReturn(['totalPenerima' => 5]);

        $result = $this->service->getDistrictStats('6301010');

        $this->assertEquals(['totalPenerima' => 5], $result);
    }

    public function test_get_village_stats_delegates(): void
    {
        $this->repository
            ->shouldReceive('getVillageStats')
            ->once()
            ->andReturn(['totalWarga' => 100]);

        $result = $this->service->getVillageStats('6301010001');

        $this->assertEquals(['totalWarga' => 100], $result);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_regency_stats_with_empty_result(): void
    {
        $this->repository
            ->shouldReceive('getRegencyStats')
            ->once()
            ->andReturn([]);

        $result = $this->service->getRegencyStats('9999');

        $this->assertEmpty($result);
    }

    // ===== DATA TYPE (2 test) =====

    public function test_get_verification_status_returns_array(): void
    {
        $this->repository
            ->shouldReceive('getVerificationStatus')
            ->once()
            ->andReturn([]);

        $result = $this->service->getVerificationStatus('6301');

        $this->assertIsArray($result);
    }

    public function test_get_top_villages_returns_array(): void
    {
        $this->repository
            ->shouldReceive('getTopVillages')
            ->once()
            ->andReturn([]);

        $result = $this->service->getTopVillages('6301', []);

        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}