<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\ActivityLog;
use App\Models\SubmissionVerification;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\Admin;
use App\Repositories\ActivityLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class ActivityLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ActivityLogRepository::class);
    }

    // ===== HELPER =====

    private function createActivityLog(array $overrides = []): ActivityLog
    {
        return ActivityLog::query()->create(array_merge([
            'actor_type' => 'admin',
            'actor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'actor_name' => 'Admin Test',
            'actor_role' => 'super_admin',
            'module' => 'verification',
            'action' => 'approve',
            'action_label' => 'Menyetujui Pengajuan',
            'created_at' => now(),
        ], $overrides));
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_get_all_returns_paginator(): void
    {
        $this->createActivityLog();

        $result = $this->repository->getAll([]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertGreaterThan(0, $result->total());
    }

    public function test_get_all_with_module_filter(): void
    {
        $this->createActivityLog(['module' => 'verification']);
        $this->createActivityLog(['module' => 'disbursement']);

        $result = $this->repository->getAll(['module' => 'verification']);

        $this->assertEquals(1, $result->total());
    }

    public function test_get_all_with_actor_name_filter(): void
    {
        $this->createActivityLog(['actor_name' => 'Admin Test']);
        $this->createActivityLog(['actor_name' => 'Other Admin']);

        $result = $this->repository->getAll(['actor_name' => 'Admin Test']);

        $this->assertEquals(1, $result->total());
    }

    // ===== SAD PATH (1 test) =====

    public function test_get_all_returns_empty_when_no_logs(): void
    {
        $result = $this->repository->getAll([]);

        $this->assertEquals(0, $result->total());
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_all_pagination_respected(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createActivityLog(['action_label' => "Action {$i}"]);
        }

        $result = $this->repository->getAll([], 2);

        $this->assertEquals(2, $result->perPage());
        $this->assertEquals(5, $result->total());
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_get_all_with_empty_filters(): void
    {
        $this->createActivityLog();

        $result = $this->repository->getAll([]);

        $this->assertGreaterThan(0, $result->total());
    }

    // ===== DATA TYPE (1 test) =====

    public function test_get_all_returns_paginator_instance(): void
    {
        $result = $this->repository->getAll([]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_get_all_with_actor_role_filter(): void
    {
        $this->createActivityLog(['actor_role' => 'super_admin']);
        $this->createActivityLog(['actor_role' => 'village_officer']);

        $result = $this->repository->getAll(['actor_role' => 'super_admin']);

        $this->assertEquals(1, $result->total());
    }
}