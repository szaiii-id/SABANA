<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class SubmissionSearchDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?string $programId = null,
        public ?string $villageId = null,
        public ?string $districtId = null,
        public ?string $regencyId = null,
        public ?string $sortBy = null,
        public ?string $sortDirection = 'desc',
        public int $page = 1,
        public int $perPage = 15,
        public bool $includeAggregations = false,
        public ?string $recommendation = null,
        public ?string $smartSort = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            status: $data['status'] ?? null,
            programId: $data['program_id'] ?? null,
            villageId: $data['village_id'] ?? null,
            districtId: $data['district_id'] ?? null,
            regencyId: $data['regency_id'] ?? null,
            sortBy: $data['sort_by'] ?? null,
            sortDirection: $data['sort_direction'] ?? 'desc',
            page: isset($data['page']) ? max(1, (int) $data['page']) : 1,
            perPage: isset($data['per_page']) ? min(100, max(1, (int) $data['per_page'])) : 15,
            includeAggregations: (bool) ($data['include_aggregations'] ?? false),
            recommendation: $data['recommendation'] ?? null,
            smartSort: $data['smart_sort'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'search'         => $this->search,
            'status'         => $this->status,
            'program_id'     => $this->programId,
            'village_id'     => $this->villageId,
            'district_id'    => $this->districtId,
            'regency_id'     => $this->regencyId,
            'sort_by'        => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'page'           => $this->page,
            'per_page'       => $this->perPage,
            'recommendation' => $this->recommendation,
            'smart_sort'       => $this->smartSort,
        ], fn ($value) => $value !== null);
    }

    /**
     * Map recommendation label ke range SMART score.
     */
    public function getSmartRange(): ?array
    {
        return match ($this->recommendation) {
            'highly_recommended' => ['gte' => 0.70, 'lte' => 1.00],
            'recommended'        => ['gte' => 0.50, 'lt'  => 0.70],
            'considered'         => ['gte' => 0.30, 'lt'  => 0.50],
            'not_recommended'    => ['gte' => 0.00, 'lt'  => 0.30],
            'unscored'           => null, // null score
            default              => null,
        };
    }
}