<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AssistanceSubmission;
use App\DTOs\SubmissionSearchDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

final readonly class SubmissionSearchService
{
    private const INDEX = 'sabana_submissions';
    private const PER_PAGE = 15;

    public function __construct(
        private Client $elasticsearch
    ) {}

    public function search(SubmissionSearchDTO $dto): LengthAwarePaginator
    {
        $page = $dto->page ?? 1;
        $perPage = $dto->perPage ?? self::PER_PAGE;
        $from = ($page - 1) * $perPage;

        try {
            $aggs = $this->buildAggregations($dto);

            $body = [
                'query' => $this->buildQuery($dto),
                'sort'  => $this->buildSort($dto),
            ];

            if (!empty($aggs)) {
                $body['aggs'] = $aggs;
            }

            $params = [
                'index' => self::INDEX,
                'from'  => $from,
                'size'  => $perPage,
                'body'  => $body,
            ];

            $response = $this->elasticsearch->search($params);

            $total = $response['hits']['total']['value'] ?? 0;
            $hits = $response['hits']['hits'] ?? [];
            $ids = array_column($hits, '_id');

            if (empty($ids)) {
                return new LengthAwarePaginator(
                    collect(),
                    0,
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            }

            $submissions = AssistanceSubmission::whereIn('id', $ids)
                ->with(['citizen', 'program', 'village', 'district', 'regency'])
                ->orderByRaw($this->buildArrayPositionOrder($ids))
                ->get();

            return new LengthAwarePaginator(
                $submissions,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } catch (ClientResponseException $e) {
            Log::error('Elasticsearch search error', [
                'error' => $e->getMessage(),
                'dto'   => $dto->toArray(),
            ]);
            return $this->fallbackDbQuery($dto);
        } catch (ServerResponseException $e) {
            Log::error('Elasticsearch server error', ['error' => $e->getMessage()]);
            return $this->fallbackDbQuery($dto);
        }
    }

    public function index(AssistanceSubmission $submission): void
    {
        try {
            $this->elasticsearch->index([
                'index' => self::INDEX,
                'id'    => $submission->id,
                'body'  => [
                    'id'                  => $submission->id,
                    'citizen_id'          => $submission->citizen_id,
                    'citizen_nik'         => $submission->citizen?->nik,
                    'citizen_name'        => $submission->citizen?->full_name,
                    'citizen_whatsapp'    => $submission->citizen?->whatsapp_number,
                    'program_id'          => $submission->program_id,
                    'program_name'        => $submission->program?->name,
                    'registration_number' => $submission->registration_number,
                    'status'              => $submission->status,
                    'smart_score'           => $submission->smart_score,
                    'village_id'          => $submission->village_id,
                    'district_id'         => $submission->district_id,
                    'regency_id'          => $submission->regency_id,
                    'disbursement_method' => $submission->disbursement_method,
                    'created_at'          => $submission->created_at?->toIso8601String(),
                    'updated_at'          => $submission->updated_at?->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Elasticsearch indexing error', [
                'submission_id' => $submission->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->elasticsearch->delete(['index' => self::INDEX, 'id' => $id]);
        } catch (ClientResponseException $e) {
            if ($e->getCode() !== 404) {
                Log::error('Elasticsearch delete error', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }
    }

    public function bulkIndex(array $submissions): void
    {
        if (empty($submissions)) return;

        $body = [];
        foreach ($submissions as $submission) {
            $body[] = ['index' => ['_index' => self::INDEX, '_id' => $submission->id]];
            $body[] = [
                'id'                  => $submission->id,
                'citizen_id'          => $submission->citizen_id,
                'citizen_nik'         => $submission->citizen?->nik,
                'citizen_name'        => $submission->citizen?->full_name,
                'program_id'          => $submission->program_id,
                'program_name'        => $submission->program?->name,
                'registration_number' => $submission->registration_number,
                'status'              => $submission->status,
                'smart_score'           => $submission->smart_score,
                'village_id'          => $submission->village_id,
                'district_id'         => $submission->district_id,
                'regency_id'          => $submission->regency_id,
                'disbursement_method' => $submission->disbursement_method,
                'created_at'          => $submission->created_at?->toIso8601String(),
                'updated_at'          => $submission->updated_at?->toIso8601String(),
            ];
        }

        try {
            $this->elasticsearch->bulk(['body' => $body]);
        } catch (\Exception $e) {
            Log::error('Elasticsearch bulk index error', ['count' => count($submissions), 'error' => $e->getMessage()]);
        }
    }

    public function getStatusCounts(array $filters = []): array
    {
        try {
            $response = $this->elasticsearch->search([
                'index' => self::INDEX,
                'body'  => [
                    'size'  => 0,
                    'query' => $this->buildFilterQuery($filters),
                    'aggs'  => ['by_status' => ['terms' => ['field' => 'status', 'size' => 20]]],
                ],
            ]);

            $buckets = $response['aggregations']['by_status']['buckets'] ?? [];
            $counts = [];
            foreach ($buckets as $bucket) {
                $counts[$bucket['key']] = $bucket['doc_count'];
            }
            return $counts;
        } catch (\Exception $e) {
            Log::error('ES aggregation error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getAverageSmartScore(string $programId): ?float
    {
        try {
            $response = $this->elasticsearch->search([
                'index' => self::INDEX,
                'body'  => [
                    'size'  => 0,
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['term' => ['program_id' => $programId]],
                                ['exists' => ['field' => 'smart_score']],
                            ],
                        ],
                    ],
                    'aggs' => ['avg_score' => ['avg' => ['field' => 'smart_score']]],
                ],
            ]);

            $avg = $response['aggregations']['avg_score']['value'] ?? null;
            return $avg !== null ? round((float) $avg, 2) : null;
        } catch (\Exception $e) {
            Log::error('ES avg SMART error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildQuery(SubmissionSearchDTO $dto): array
    {
        $must = [];
        $filter = [];

        $filter[] = ['exists' => ['field' => 'citizen_id']];
        $filter[] = ['exists' => ['field' => 'program_id']];

        if ($dto->villageId) {
            $filter[] = ['term' => ['village_id' => $dto->villageId]];
        } elseif ($dto->districtId) {
            $filter[] = ['term' => ['district_id' => $dto->districtId]];
        } elseif ($dto->regencyId) {
            $filter[] = ['term' => ['regency_id' => $dto->regencyId]];
        }

        if ($dto->status) {
            $statuses = explode(',', $dto->status);
            if (count($statuses) > 1) {
                $filter[] = ['terms' => ['status' => $statuses]];
            } else {
                $filter[] = ['term' => ['status' => $dto->status]];
            }
        } else {
            $filter[] = [
                'bool' => [
                    'must_not' => [
                        'term' => ['status' => 'evaluation_pending']
                    ]
                ]
            ];
        }

        if ($dto->programId) {
            $filter[] = ['term' => ['program_id' => $dto->programId]];
        }

        // Filter Rekomendasi (SMART Score range)
        $smartRange = $dto->getSmartRange();
        if ($dto->recommendation === 'unscored') {
            $must[] = [
                'bool' => [
                    'must_not' => [
                        ['exists' => ['field' => 'smart_score']],
                    ],
                ],
            ];
        } elseif ($smartRange !== null) {
            $rangeQuery = ['range' => ['smart_score' => []]];
            if (isset($smartRange['gte'])) {
                $rangeQuery['range']['smart_score']['gte'] = $smartRange['gte'];
            }
            if (isset($smartRange['lte'])) {
                $rangeQuery['range']['smart_score']['lte'] = $smartRange['lte'];
            }
            if (isset($smartRange['lt'])) {
                $rangeQuery['range']['smart_score']['lt'] = $smartRange['lt'];
            }
            $filter[] = $rangeQuery;
        }

        if ($dto->search) {
            $searchTerm = $dto->search;
            $lowerSearch = strtolower($searchTerm);

            $must[] = [
                'bool' => [
                    'should' => [
                        [
                            'match' => [
                                'citizen_name' => [
                                    'query'    => $searchTerm,
                                    'fuzziness' => 'AUTO',
                                    'analyzer'  => 'standard',
                                ],
                            ],
                        ],
                        [
                            'wildcard' => [
                                'citizen_name' => '*' . $lowerSearch . '*',
                            ],
                        ],
                        [
                            'match' => [
                                'citizen_nik' => [
                                    'query'    => $searchTerm,
                                    'fuzziness' => 'AUTO',
                                    'analyzer'  => 'standard',
                                ],
                            ],
                        ],
                        [
                            'wildcard' => [
                                'citizen_nik' => '*' . $searchTerm . '*',
                            ],
                        ],
                        [
                            'match' => [
                                'registration_number' => [
                                    'query'    => $searchTerm,
                                    'fuzziness' => 'AUTO',
                                    'analyzer'  => 'standard',
                                ],
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1,
                ],
            ];
        }

        return ['bool' => ['must' => $must, 'filter' => $filter]];
    }

    private function buildFilterQuery(array $filters): array
    {
        $query = ['bool' => ['must' => [], 'filter' => []]];

        if (!empty($filters['village_id'])) {
            $query['bool']['filter'][] = ['term' => ['village_id' => $filters['village_id']]];
        }
        if (!empty($filters['district_id'])) {
            $query['bool']['filter'][] = ['term' => ['district_id' => $filters['district_id']]];
        }
        if (!empty($filters['regency_id'])) {
            $query['bool']['filter'][] = ['term' => ['regency_id' => $filters['regency_id']]];
        }
        if (!empty($filters['status'])) {
            $query['bool']['filter'][] = ['term' => ['status' => $filters['status']]];
        }

        return $query;
    }

    private function buildSort(SubmissionSearchDTO $dto): array
    {
        $sort = [];

        if ($dto->smartSort === 'asc') {
            $sort[] = ['smart_score' => ['order' => 'asc']];
            $sort[] = ['created_at' => ['order' => 'asc']];
        } elseif ($dto->smartSort === 'desc') {
            $sort[] = ['smart_score' => ['order' => 'desc']];
            $sort[] = ['created_at' => ['order' => 'asc']];
        } else {
            if ($dto->sortBy) {
                $sort[] = [$dto->sortBy => ['order' => $dto->sortDirection ?? 'desc']];
            }
            $sort[] = ['smart_score' => ['order' => 'desc']];
            $sort[] = ['created_at' => ['order' => 'asc']];
        }

        return $sort;
    }

    private function buildAggregations(SubmissionSearchDTO $dto): array
    {
        if (!$dto->includeAggregations) {
            return [];
        }

        return [
            'status_counts' => [
                'terms' => ['field' => 'status', 'size' => 10],
            ],
            'program_counts' => [
                'terms' => ['field' => 'program_name.keyword', 'size' => 10],
            ],
        ];
    }

    private function buildArrayPositionOrder(array $ids): string
    {
        if (empty($ids)) {
            return 'created_at ASC';
        }

        $quotedIds = array_map(function (string $id): string {
            return "'" . preg_replace('/[^a-f0-9\-]/i', '', $id) . "'";
        }, $ids);

        $idsArray = 'ARRAY[' . implode(',', $quotedIds) . ']::uuid[]';

        return "array_position({$idsArray}, id::uuid)";
    }

    private function fallbackDbQuery(SubmissionSearchDTO $dto): LengthAwarePaginator
    {
        Log::warning('Using DB fallback for submission search');

        $query = AssistanceSubmission::query()
            ->with(['citizen', 'program', 'village', 'district', 'regency'])
            ->whereHas('program', fn($q) => $q->whereNull('deleted_at'))
            ->whereHas('citizen');

        if ($dto->villageId) {
            $query->where('village_id', $dto->villageId);
        } elseif ($dto->districtId) {
            $query->where('district_id', $dto->districtId);
        } elseif ($dto->regencyId) {
            $query->where('regency_id', $dto->regencyId);
        }

        if ($dto->status) {
            $statuses = explode(',', $dto->status);
            if (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', $dto->status);
            }
        } else {
            $query->where('status', '!=', 'evaluation_pending');
        }

        if ($dto->programId) {
            $query->where('program_id', $dto->programId);
        }

        $smartRange = $dto->getSmartRange();
        if ($dto->recommendation === 'unscored') {
            $query->whereNull('smart_score');
        } elseif ($smartRange !== null) {
            if (isset($smartRange['gte'])) {
                $query->where('smart_score', '>=', $smartRange['gte']);
            }
            if (isset($smartRange['lte'])) {
                $query->where('smart_score', '<=', $smartRange['lte']);
            }
            if (isset($smartRange['lt'])) {
                $query->where('smart_score', '<', $smartRange['lt']);
            }
        }

        if ($dto->search) {
            $query->where(function ($q) use ($dto) {
                $q->where('registration_number', 'like', "%{$dto->search}%")
                  ->orWhereHas('citizen', function ($cq) use ($dto) {
                      $cq->where('full_name', 'like', "%{$dto->search}%")
                         ->orWhere('nik', 'like', "%{$dto->search}%");
                  });
            });
        }

        if ($dto->smartSort === 'asc') {
            return $query->orderBy('smart_score', 'asc')
                         ->orderBy('created_at', 'asc')
                         ->paginate($dto->perPage ?? self::PER_PAGE);
        }

        return $query->orderBy('smart_score', 'desc')
                     ->orderBy('created_at', 'asc')
                     ->paginate($dto->perPage ?? self::PER_PAGE);
    }

    public function ensureIndexExists(): void
    {
        try {
            $exists = $this->elasticsearch->indices()->exists(['index' => self::INDEX]);

            if (!$exists) {
                $this->elasticsearch->indices()->create([
                    'index' => self::INDEX,
                    'body'  => [
                        'settings' => [
                            'number_of_shards'   => 1,
                            'number_of_replicas' => 0,
                            'analysis' => [
                                'analyzer' => [
                                    'nik_analyzer' => [
                                        'type'      => 'custom',
                                        'tokenizer' => 'standard',
                                        'filter'    => ['lowercase', 'asciifolding'],
                                    ],
                                ],
                            ],
                        ],
                        'mappings' => [
                            'properties' => [
                                'id'                  => ['type' => 'keyword'],
                                'citizen_id'          => ['type' => 'keyword'],
                                'citizen_nik'         => ['type' => 'text', 'analyzer' => 'nik_analyzer'],
                                'citizen_name'        => ['type' => 'text', 'analyzer' => 'standard'],
                                'citizen_whatsapp'    => ['type' => 'keyword'],
                                'program_id'          => ['type' => 'keyword'],
                                'program_name'        => ['type' => 'text'],
                                'registration_number' => ['type' => 'text'],
                                'status'              => ['type' => 'keyword'],
                                'smart_score'         => ['type' => 'float'],
                                'village_id'          => ['type' => 'keyword'],
                                'district_id'         => ['type' => 'keyword'],
                                'regency_id'          => ['type' => 'keyword'],
                                'disbursement_method' => ['type' => 'keyword'],
                                'created_at'          => ['type' => 'date'],
                                'updated_at'          => ['type' => 'date'],
                            ],
                        ],
                    ],
                ]);

                Log::info('Elasticsearch index created: ' . self::INDEX);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create Elasticsearch index', ['index' => self::INDEX, 'error' => $e->getMessage()]);
        }
    }

    public function reindexAll(): int
    {
        $count = 0;

        AssistanceSubmission::with(['citizen', 'program'])
            ->chunk(500, function ($submissions) use (&$count) {
                $this->bulkIndex($submissions->all());
                $count += $submissions->count();
            });

        Log::info('Reindex completed', ['total' => $count]);
        return $count;
    }
}