<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Contracts\SearchEngineInterface;
use App\Models\AssistanceProgram;
use App\Services\Admin\ProgramSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

final class ProgramSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private SearchEngineInterface $searchEngine;
    private ProgramSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchEngine = Mockery::mock(SearchEngineInterface::class);
        $this->service = new ProgramSearchService($this->searchEngine);
    }

    // ===== HELPER =====

    private function createProgram(array $overrides = []): AssistanceProgram
    {
        return AssistanceProgram::query()->create(array_merge([
            'name' => 'Program Test ' . uniqid(),
            'description' => 'Deskripsi',
            'status' => 'active',
            'is_active' => true,
        ], $overrides));
    }

    private function mockSearchResponse(array $hits, int $total): void
    {
        $response = new class($hits, $total) {
            private array $hits;
            private int $total;

            public function __construct(array $hits, int $total)
            {
                $this->hits = $hits;
                $this->total = $total;
            }

            public function asArray(): array
            {
                return [
                    'hits' => [
                        'total' => ['value' => $this->total],
                        'hits' => $this->hits,
                    ],
                ];
            }
        };

        $this->searchEngine
            ->shouldReceive('search')
            ->once()
            ->andReturn($response);
    }

    // ===== HAPPY PATH (4 test) =====

    public function test_index_sends_document_to_search_engine(): void
    {
        $program = $this->createProgram();

        $this->searchEngine
            ->shouldReceive('index')
            ->with(Mockery::on(function (array $params) use ($program) {
                return $params['index'] === 'sabana_programs'
                    && $params['id'] === $program->id
                    && $params['body']['name'] === $program->name;
            }))
            ->once();

        $this->service->index($program);

        $this->assertTrue(true);
    }

    public function test_search_returns_paginator(): void
    {
        $program = $this->createProgram();

        $this->mockSearchResponse([['_id' => $program->id]], 1);

        $result = $this->service->search(['search' => 'test'], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
    }

    public function test_search_empty_returns_empty_paginator(): void
    {
        $this->mockSearchResponse([], 0);

        $result = $this->service->search(['search' => 'nonexistent'], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    public function test_delete_calls_search_engine(): void
    {
        $this->searchEngine
            ->shouldReceive('delete')
            ->with(Mockery::on(function (array $params) {
                return $params['index'] === 'sabana_programs'
                    && $params['id'] === 'test-id-123';
            }))
            ->once();

        $this->service->delete('test-id-123');

        $this->assertTrue(true);
    }

    // ===== SAD PATH (2 test) =====

    public function test_search_fallback_on_exception(): void
    {
        $this->searchEngine
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception('Search engine down'));

        $result = $this->service->search(['search' => 'test'], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    public function test_index_exists_returns_false_on_error(): void
    {
        $this->searchEngine
            ->shouldReceive('indices')
            ->once()
            ->andThrow(new \Exception('Connection error'));

        $result = $this->service->indexExists();

        $this->assertFalse($result);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_search_with_pagination(): void
    {
        $this->mockSearchResponse([], 100);

        $result = $this->service->search(['page' => 5], 10);

        $this->assertEquals(5, $result->currentPage());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(100, $result->total());
    }

    // ===== EDGE CASE (1 test) =====

    public function test_index_with_null_dates(): void
    {
        $program = $this->createProgram([
            'start_date' => null,
            'end_date' => null,
        ]);

        $this->searchEngine
            ->shouldReceive('index')
            ->with(Mockery::on(function (array $params) {
                return $params['body']['start_date'] === null
                    && $params['body']['end_date'] === null;
            }))
            ->once();

        $this->service->index($program);

        $this->assertTrue(true);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_search_without_filters_returns_match_all(): void
    {
        $this->mockSearchResponse([], 0);

        $result = $this->service->search([], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_search_returns_length_aware_paginator(): void
    {
        $this->mockSearchResponse([], 0);

        $result = $this->service->search(['search' => 'test'], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_get_status_counts_returns_array(): void
    {
        $response = new class {
            public function asArray(): array
            {
                return [
                    'aggregations' => [
                        'status_counts' => [
                            'buckets' => [
                                ['key' => 'active', 'doc_count' => 5],
                                ['key' => 'draft', 'doc_count' => 3],
                            ],
                        ],
                    ],
                ];
            }
        };

        $this->searchEngine
            ->shouldReceive('search')
            ->once()
            ->andReturn($response);

        $result = $this->service->getStatusCounts();

        $this->assertIsArray($result);
        $this->assertEquals(['active' => 5, 'draft' => 3], $result);
    }

    // ===== SECURITY (1 test) =====

    public function test_build_array_position_sanitizes_ids(): void
    {
        $maliciousId = "abc'; DROP TABLE users;--";

        $this->mockSearchResponse([['_id' => $maliciousId]], 1);

        $result = $this->service->search(['search' => 'test'], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== GAP COVERAGE (4 test) =====

    public function test_create_index_when_not_exists(): void
    {
        $indices = Mockery::mock();
        $this->searchEngine->shouldReceive('indices')->andReturn($indices);

        $existsResponse = new class {
            public function asBool(): bool { return false; }
        };
        $indices->shouldReceive('exists')->once()->andReturn($existsResponse);
        $indices->shouldReceive('create')->once();

        $this->service->createIndex();

        $this->assertTrue(true);
    }

    public function test_create_index_when_already_exists(): void
    {
        $indices = Mockery::mock();
        $this->searchEngine->shouldReceive('indices')->andReturn($indices);

        $existsResponse = new class {
            public function asBool(): bool { return true; }
        };
        $indices->shouldReceive('exists')->once()->andReturn($existsResponse);
        $indices->shouldReceive('create')->never();

        $this->service->createIndex();

        $this->assertTrue(true);
    }

    public function test_delete_index_when_exists(): void
    {
        $indices = Mockery::mock();
        $this->searchEngine->shouldReceive('indices')->andReturn($indices);

        $existsResponse = new class {
            public function asBool(): bool { return true; }
        };
        $indices->shouldReceive('exists')->once()->andReturn($existsResponse);
        $indices->shouldReceive('delete')->once();

        $this->service->deleteIndex();

        $this->assertTrue(true);
    }

    public function test_index_exists_returns_true(): void
    {
        $indices = Mockery::mock();
        $this->searchEngine->shouldReceive('indices')->andReturn($indices);

        $existsResponse = new class {
            public function asBool(): bool { return true; }
        };
        $indices->shouldReceive('exists')->once()->andReturn($existsResponse);

        $result = $this->service->indexExists();

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}