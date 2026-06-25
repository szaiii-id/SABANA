<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\SubmissionSearchDTO;
use Tests\TestCase;

final class SubmissionSearchDTOTest extends TestCase
{
    // ===== HAPPY PATH (3 test) =====

    public function test_from_array_creates_dto_with_all_fields(): void
    {
        $dto = SubmissionSearchDTO::fromArray([
            'search' => 'test',
            'status' => 'pending',
            'program_id' => 'uuid-123',
            'village_id' => '6301010001',
            'page' => 2,
            'per_page' => 20,
        ]);

        $this->assertEquals('test', $dto->search);
        $this->assertEquals('pending', $dto->status);
        $this->assertEquals('uuid-123', $dto->programId);
        $this->assertEquals(2, $dto->page);
        $this->assertEquals(20, $dto->perPage);
    }

    public function test_from_array_with_empty_data(): void
    {
        $dto = SubmissionSearchDTO::fromArray([]);

        $this->assertNull($dto->search);
        $this->assertNull($dto->status);
        $this->assertEquals(1, $dto->page);
        $this->assertEquals(15, $dto->perPage);
    }

    public function test_to_array_filters_null_values(): void
    {
        $dto = new SubmissionSearchDTO(
            search: 'test',
            status: null,
            page: 1,
            perPage: 15,
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('search', $array);
        $this->assertArrayNotHasKey('status', $array);
    }

    // ===== SAD PATH (1 test) =====

    public function test_from_array_clamps_page_minimum(): void
    {
        $dto = SubmissionSearchDTO::fromArray(['page' => -5]);

        $this->assertEquals(1, $dto->page);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_from_array_clamps_per_page_maximum(): void
    {
        $dto = SubmissionSearchDTO::fromArray(['per_page' => 200]);

        $this->assertEquals(100, $dto->perPage);
    }

    public function test_from_array_clamps_per_page_minimum(): void
    {
        $dto = SubmissionSearchDTO::fromArray(['per_page' => 0]);

        $this->assertEquals(1, $dto->perPage);
    }

    // ===== EQUIVALENCE PARTITION (4 test) =====

    public function test_get_smart_range_highly_recommended(): void
    {
        $dto = new SubmissionSearchDTO(recommendation: 'highly_recommended');

        $range = $dto->getSmartRange();

        $this->assertEquals(['gte' => 0.70, 'lte' => 1.00], $range);
    }

    public function test_get_smart_range_recommended(): void
    {
        $dto = new SubmissionSearchDTO(recommendation: 'recommended');

        $range = $dto->getSmartRange();

        $this->assertEquals(['gte' => 0.50, 'lt' => 0.70], $range);
    }

    public function test_get_smart_range_not_recommended(): void
    {
        $dto = new SubmissionSearchDTO(recommendation: 'not_recommended');

        $range = $dto->getSmartRange();

        $this->assertEquals(['gte' => 0.00, 'lt' => 0.30], $range);
    }

    public function test_get_smart_range_default_null(): void
    {
        $dto = new SubmissionSearchDTO(recommendation: null);

        $range = $dto->getSmartRange();

        $this->assertNull($range);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_include_aggregations_is_boolean(): void
    {
        $dto = SubmissionSearchDTO::fromArray(['include_aggregations' => '1']);

        $this->assertTrue($dto->includeAggregations);
        $this->assertIsBool($dto->includeAggregations);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_from_array_with_null_values(): void
    {
        $dto = SubmissionSearchDTO::fromArray([
            'search' => null,
            'status' => null,
            'program_id' => null,
        ]);

        $this->assertNull($dto->search);
        $this->assertNull($dto->status);
        $this->assertNull($dto->programId);
    }
}