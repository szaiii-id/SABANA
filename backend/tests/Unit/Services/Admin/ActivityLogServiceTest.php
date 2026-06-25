<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Models\ActivityLog;
use App\Services\Admin\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ActivityLogService::class);
    }

    // ===== HAPPY PATH (1 test) =====

    public function test_get_all_returns_paginator(): void
    {
        ActivityLog::query()->create([
            'actor_type' => 'admin',
            'actor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'actor_name' => 'Admin',
            'actor_role' => 'super_admin',
            'module' => 'verification',
            'action' => 'approve',
            'action_label' => 'Menyetujui',
            'created_at' => now(),
        ]);

        $result = $this->service->getAll([]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertGreaterThan(0, $result->total());
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_all_with_empty_data(): void
    {
        $result = $this->service->getAll([]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    // ===== DATA TYPE (1 test) =====

    public function test_get_all_returns_paginator_instance(): void
    {
        $result = $this->service->getAll([]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_get_all_with_filters(): void
    {
        ActivityLog::query()->create([
            'actor_type' => 'admin',
            'actor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'actor_name' => 'Admin Test',
            'actor_role' => 'super_admin',
            'module' => 'verification',
            'action' => 'approve',
            'action_label' => 'Menyetujui',
            'created_at' => now(),
        ]);

        $result = $this->service->getAll(['module' => 'verification'], 10);

        $this->assertEquals(1, $result->total());
    }
}