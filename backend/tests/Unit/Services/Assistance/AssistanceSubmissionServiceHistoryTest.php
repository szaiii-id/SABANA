<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Assistance;

use Tests\TestCase;
use App\Models\Citizen;
use App\Models\AssistanceSubmission;
use App\Services\Assistance\AssistanceSubmissionService;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Contracts\Storage\FileStorageInterface;
use App\Services\Admin\SmartCalculationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('service')]
final class AssistanceSubmissionServiceHistoryTest extends TestCase
{
    private AssistanceSubmissionService $service;
    private AssistanceRepositoryInterface $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(AssistanceRepositoryInterface::class);
        $storageMock = Mockery::mock(FileStorageInterface::class);
        $smartMock = Mockery::mock(SmartCalculationService::class);

        $this->service = new AssistanceSubmissionService($this->repositoryMock, $storageMock, $smartMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== HAPPY PATH =====

    public function test_get_citizen_history_returns_paginator(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('uuid-citizen-123', 10)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->service->getCitizenHistory('uuid-citizen-123');

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== SAD PATH =====

    public function test_get_citizen_history_returns_empty(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->service->getCitizenHistory('uuid-nonexistent');

        $this->assertEmpty($result->items());
    }

    // ===== BOUNDARY =====

    public function test_get_citizen_history_respects_per_page(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('uuid-citizen-123', 5)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 5));

        $result = $this->service->getCitizenHistory('uuid-citizen-123', 5);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_get_citizen_history_default_per_page_is_10(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('uuid-citizen-123', 10)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->service->getCitizenHistory('uuid-citizen-123');
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== EDGE CASE =====

    public function test_get_citizen_history_with_large_per_page(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('uuid-citizen-123', 100)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 100));

        $result = $this->service->getCitizenHistory('uuid-citizen-123', 100);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== NULL/EMPTY =====

    public function test_get_citizen_history_with_zero_per_page(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('uuid-citizen-123', 0)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->service->getCitizenHistory('uuid-citizen-123', 0);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== DATA TYPE =====

    public function test_get_citizen_history_accepts_string_uuid(): void
    {
        $uuid = '123e4567-e89b-12d3-a456-426614174000';

        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with($uuid, 10)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->service->getCitizenHistory($uuid);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== STATE TRANSITION =====

    public function test_get_citizen_history_called_multiple_times(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->twice()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result1 = $this->service->getCitizenHistory('uuid-citizen-123');
        $result2 = $this->service->getCitizenHistory('uuid-citizen-123');
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result1);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result2);
    }

    // ===== CONCURRENCY =====

    public function test_get_citizen_history_isolates_per_citizen(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('citizen-a', 10)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator(
                [['id' => '1'], ['id' => '2'], ['id' => '3']], 3, 10
            ));

        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('citizen-b', 10)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator(
                [['id' => '4']], 1, 10
            ));

        $resultA = $this->service->getCitizenHistory('citizen-a');
        $resultB = $this->service->getCitizenHistory('citizen-b');

        $this->assertCount(3, $resultA->items());
        $this->assertCount(1, $resultB->items());
    }

    // ===== SECURITY =====

    public function test_get_citizen_history_data_isolation(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('citizen-a', 10)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->service->getCitizenHistory('citizen-a');

        $this->assertEmpty($result->items());
    }
}