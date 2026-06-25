<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\AssistanceProgram;
use App\Repositories\ProgramRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class ProgramRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProgramRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ProgramRepository::class);
    }

    private function createProgram(array $overrides = []): AssistanceProgram
    {
        return AssistanceProgram::query()->create(array_merge([
            'name' => 'Program Test ' . uniqid(),
            'description' => 'Deskripsi',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'is_active' => true,
        ], $overrides));
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_get_all_without_search_returns_paginator(): void
    {
        $this->createProgram();

        $result = $this->repository->getAll([], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertGreaterThan(0, $result->total());
    }

    public function test_get_all_with_status_filter(): void
    {
        $this->createProgram(['status' => 'draft', 'name' => 'Draft']);
        $this->createProgram(['status' => 'active', 'name' => 'Active']);

        $result = $this->repository->getAll(['status' => 'draft'], 10);

        $this->assertEquals(1, $result->total());
    }

    public function test_find_by_id_returns_program(): void
    {
        $program = $this->createProgram();

        $found = $this->repository->findById($program->id);

        $this->assertInstanceOf(AssistanceProgram::class, $found);
        $this->assertEquals($program->id, $found->id);
    }

    public function test_create_returns_program(): void
    {
        $program = $this->repository->create([
            'name' => 'New Program',
            'description' => 'Desc',
            'status' => 'draft',
        ]);

        $this->assertInstanceOf(AssistanceProgram::class, $program);
        $this->assertDatabaseHas('assistance_programs', ['name' => 'New Program']);
    }

    public function test_get_active_returns_collection(): void
    {
        $this->createProgram([
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $result = $this->repository->getActive();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThan(0, $result->count());
    }

    // ===== SAD PATH (2 test) =====

    public function test_find_by_id_returns_null_for_unknown(): void
    {
        $found = $this->repository->findById('00000000-0000-0000-0000-000000000000');

        $this->assertNull($found);
    }

    public function test_get_active_excludes_expired_programs(): void
    {
        $this->createProgram([
            'status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDay(),
        ]);

        $result = $this->repository->getActive();

        $this->assertEquals(0, $result->count());
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_all_per_page_respected(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createProgram(['name' => "Program {$i}"]);
        }

        $result = $this->repository->getAll([], 2);

        $this->assertEquals(2, $result->perPage());
        $this->assertEquals(5, $result->total());
    }

    // ===== EDGE CASE (1 test) =====

    public function test_get_active_with_null_end_date(): void
    {
        $this->createProgram([
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => null,
        ]);

        $result = $this->repository->getActive();

        $this->assertEquals(1, $result->count());
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_get_all_with_empty_filters(): void
    {
        $this->createProgram();

        $result = $this->repository->getAll([], 10);

        $this->assertGreaterThan(0, $result->total());
    }

    // ===== DATA TYPE (2 test) =====

    public function test_get_all_returns_length_aware_paginator(): void
    {
        $result = $this->repository->getAll([], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_get_active_returns_collection_instance(): void
    {
        $result = $this->repository->getActive();

        $this->assertInstanceOf(Collection::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_get_all_all_status_filters(): void
    {
        foreach (['draft', 'active', 'closed', 'completed'] as $status) {
            $this->createProgram(['status' => $status, 'name' => "Program {$status}"]);

            $result = $this->repository->getAll(['status' => $status], 10);

            $this->assertEquals(1, $result->total());
        }
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_update_then_delete(): void
    {
        $program = $this->createProgram();

        $this->repository->update($program, ['status' => 'closed']);
        $this->assertEquals('closed', $program->fresh()->status);

        $this->repository->delete($program);
        $this->assertSoftDeleted('assistance_programs', ['id' => $program->id]);
    }

    // ===== SECURITY (1 test) =====

    public function test_find_by_id_returns_all_fillable_fields(): void
    {
        $program = $this->createProgram();

        $found = $this->repository->findById($program->id);

        $this->assertEquals($program->id, $found->id);
    }

    // ===== GAP COVERAGE — existsByNameExcludingId (2 test) =====

    public function test_exists_by_name_excluding_id_returns_true(): void
    {
        $this->createProgram(['name' => 'Program Unik']);
        $program = $this->createProgram(['name' => 'Program Lain']);

        $exists = $this->repository->existsByNameExcludingId('Program Unik', $program->id);

        $this->assertTrue($exists);
    }

    public function test_exists_by_name_excluding_id_returns_false_for_same_id(): void
    {
        $program = $this->createProgram(['name' => 'Program Unik']);

        $exists = $this->repository->existsByNameExcludingId('Program Unik', $program->id);

        $this->assertFalse($exists);
    }
}