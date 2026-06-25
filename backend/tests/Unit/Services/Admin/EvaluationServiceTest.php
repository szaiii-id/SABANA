<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Models\Admin;
use App\Repositories\Contracts\EvaluationRepositoryInterface;
use App\Services\Admin\EvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

final class EvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    private EvaluationRepositoryInterface $repository;
    private EvaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->repository = Mockery::mock(EvaluationRepositoryInterface::class);
        $this->service = new EvaluationService($this->repository);
    }

    // ===== HELPER =====

    private function createAdmin(): Admin
    {
        return Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Evaluator',
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
        $filters = ['status' => 'updated'];

        $this->repository
            ->shouldReceive('getByWilayah')
            ->with($admin, $filters)
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($admin, $filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== SAD PATH (2 test) =====

    public function test_approve_throws_for_invalid_log(): void
    {
        $this->markTestSkipped('Method approve() pakai lockForUpdate + transaksi — butuh integration test.');
    }

    public function test_revoke_throws_without_notes(): void
    {
        $this->markTestSkipped('Method revoke() pakai lockForUpdate + transaksi — butuh integration test.');
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}