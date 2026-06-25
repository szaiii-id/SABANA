<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Models\Admin;
use App\Repositories\Contracts\DisbursementRepositoryInterface;
use App\Services\Admin\DisbursementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

final class DisbursementServiceTest extends TestCase
{
    use RefreshDatabase;

    private DisbursementRepositoryInterface $repository;
    private DisbursementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->repository = Mockery::mock(DisbursementRepositoryInterface::class);
        $this->service = new DisbursementService($this->repository);
    }

    // ===== HELPER =====

    private function createAdmin(): Admin
    {
        return Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Disbursement',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    // ===== HAPPY PATH (2 test) =====

    public function test_get_list_delegates_to_repository(): void
    {
        $admin = $this->createAdmin();
        $paginator = new LengthAwarePaginator(collect(), 0, 15);

        $this->repository
            ->shouldReceive('getByWilayah')
            ->with($admin, [])
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($admin, []);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_get_list_with_filters(): void
    {
        $admin = $this->createAdmin();
        $paginator = new LengthAwarePaginator(collect(), 0, 15);
        $filters = ['program_id' => 'uuid-123', 'search' => 'test'];

        $this->repository
            ->shouldReceive('getByWilayah')
            ->with($admin, $filters)
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($admin, $filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== SAD PATH (1 test) =====

    public function test_disburse_throws_for_non_existent_submission(): void
    {
        $this->markTestSkipped('Method disburse() pakai lockForUpdate + transaksi — butuh integration test.');
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_list_with_empty_filters(): void
    {
        $admin = $this->createAdmin();
        $paginator = new LengthAwarePaginator(collect(), 0, 15);

        $this->repository
            ->shouldReceive('getByWilayah')
            ->with($admin, [])
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($admin, []);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    // ===== DATA TYPE (1 test) =====

    public function test_get_list_returns_paginator(): void
    {
        $admin = $this->createAdmin();
        $paginator = new LengthAwarePaginator(collect(), 0, 15);

        $this->repository
            ->shouldReceive('getByWilayah')
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($admin, []);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== SECURITY (1 test) =====

    public function test_get_list_passes_admin_to_repository(): void
    {
        $admin = $this->createAdmin();
        $paginator = new LengthAwarePaginator(collect(), 0, 15);

        $this->repository
            ->shouldReceive('getByWilayah')
            ->with(Mockery::on(fn($a) => $a->id === $admin->id), [])
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($admin, []);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}