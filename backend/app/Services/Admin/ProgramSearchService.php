<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Contracts\SearchEngineInterface;
use App\Models\AssistanceProgram;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

final class ProgramSearchService
{
    private const INDEX = 'sabana_programs';

    public function __construct(
        private readonly SearchEngineInterface $searchEngine 
    ) {}

    // =============================================
    // INDEXING
    // =============================================

    public function index(AssistanceProgram $program): void
    {
        $params = [
            'index' => self::INDEX,
            'id'    => $program->id,
            'body'  => [
                'id'             => $program->id,
                'name'           => $program->name,
                'slug'           => $program->slug,
                'description'    => strip_tags((string) ($program->description ?? '')),
                'status'         => $program->status,
                'start_date'     => $program->start_date?->toDateString(),
                'end_date'       => $program->end_date?->toDateString(),
                'quota_total'    => $program->quota_total,
                'benefit_amount' => (float) ($program->benefit_amount ?? 0),
                'is_active'      => $program->is_active,
                'created_at'     => $program->created_at?->toIso8601String(),
                'updated_at'     => $program->updated_at?->toIso8601String(),
            ],
        ];

        $this->searchEngine->index($params);
    }

    public function delete(string $programId): void
    {
        try {
            $this->searchEngine->delete([
                'index' => self::INDEX,
                'id'    => $programId,
            ]);
        } catch (ClientResponseException $e) {
            if ($e->getCode() === 404) {
                Log::info("SABANA: Program [{$programId}] tidak ditemukan di Elasticsearch, skip delete.");
                return;
            }
            throw $e;
        } catch (MissingParameterException $e) {
            Log::error("SABANA: Parameter tidak lengkap saat delete program [{$programId}].", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =============================================
    // SEARCHING
    // =============================================

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $page = (int) ($filters['page'] ?? 1);
        $from = ($page - 1) * $perPage;

        $params = [
            'index' => self::INDEX,
            'from'  => $from,
            'size'  => $perPage,
            'body'  => [
                'sort' => [
                    ['created_at' => ['order' => 'desc']],
                ],
                'query' => [
                    'bool' => [
                        'must'   => [],
                        'filter' => [],
                    ],
                ],
            ],
        ];

        // Pencarian teks — match + wildcard untuk partial + case-insensitive
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $lowerSearch = strtolower($searchTerm);

            $params['body']['query']['bool']['must'][] = [
                'bool' => [
                    'should' => [
                        [
                            'match' => [
                                'name' => [
                                    'query'    => $searchTerm,
                                    'fuzziness' => 'AUTO',
                                    'analyzer'  => 'indonesian_analyzer',
                                ],
                            ],
                        ],
                        [
                            'wildcard' => [
                                'name' => '*' . $lowerSearch . '*',
                            ],
                        ],
                        [
                            'match' => [
                                'description' => [
                                    'query'    => $searchTerm,
                                    'fuzziness' => 'AUTO',
                                    'analyzer'  => 'indonesian_analyzer',
                                ],
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1,
                ],
            ];
        }

        // Filter status
        if (!empty($filters['status'])) {
            $params['body']['query']['bool']['filter'][] = [
                'term' => ['status' => $filters['status']],
            ];
        }

        // Jika tidak ada query, gunakan match_all
        if (empty($params['body']['query']['bool']['must']) && empty($params['body']['query']['bool']['filter'])) {
            $params['body']['query'] = ['match_all' => (object) []];
        }

        try {
            /** @var ElasticsearchResponse $response */
            $response = $this->searchEngine->search($params);
            $data = $response->asArray();

            $total = $data['hits']['total']['value'] ?? 0;
            $hits  = $data['hits']['hits'];

            $ids = array_column($hits, '_id');

            if (empty($ids)) {
                return new LengthAwarePaginator(collect(), $total, $perPage, $page);
            }

            $programs = AssistanceProgram::whereIn('id', $ids)
                ->orderByRaw($this->buildArrayPositionOrder($ids))
                ->get();

            return new LengthAwarePaginator($programs, $total, $perPage, $page);

        } catch (\Throwable $e) {
            Log::error("SABANA: Elasticsearch search gagal.", [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            return new LengthAwarePaginator(collect(), 0, $perPage, $page);
        }
    }

    // =============================================
    // AGGREGATIONS (untuk Dashboard)
    // =============================================

    public function getStatusCounts(): array
    {
        try {
            /** @var ElasticsearchResponse $response */
            $response = $this->searchEngine->search([
                'index' => self::INDEX,
                'size'  => 0,
                'body'  => [
                    'aggs' => [
                        'status_counts' => [
                            'terms' => ['field' => 'status'],
                        ],
                    ],
                ],
            ]);

            $data = $response->asArray();

            $counts = [];
            foreach ($data['aggregations']['status_counts']['buckets'] as $bucket) {
                $counts[$bucket['key']] = $bucket['doc_count'];
            }

            return $counts;

        } catch (\Throwable $e) {
            Log::error("SABANA: Gagal ambil aggregasi program.", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // =============================================
    // HELPERS
    // =============================================

    private function buildArrayPositionOrder(array $ids): string
    {
        if (empty($ids)) {
            return 'created_at DESC';
        }

        $quotedIds = array_map(function (string $id): string {
            return "'" . preg_replace('/[^a-f0-9\-]/i', '', $id) . "'";
        }, $ids);

        $idsArray = 'ARRAY[' . implode(',', $quotedIds) . ']::uuid[]';

        return "array_position({$idsArray}, id::uuid)";
    }

    // =============================================
    // INDEX MANAGEMENT
    // =============================================

    public function createIndex(): void
    {
        $params = [
            'index' => self::INDEX,
            'body'  => [
                'settings' => [
                    'number_of_shards'   => 1,
                    'number_of_replicas' => 1,
                    'analysis'           => [
                        'analyzer' => [
                            'indonesian_analyzer' => [
                                'type'      => 'custom',
                                'tokenizer' => 'standard',
                                'filter'    => ['lowercase', 'asciifolding'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'keyword'],
                        'name' => [
                            'type'     => 'text',
                            'analyzer' => 'indonesian_analyzer',
                            'fields'   => [
                                'keyword' => ['type' => 'keyword'],
                            ],
                        ],
                        'slug'           => ['type' => 'keyword'],
                        'description'    => [
                            'type'     => 'text',
                            'analyzer' => 'indonesian_analyzer',
                        ],
                        'status'         => ['type' => 'keyword'],
                        'start_date'     => ['type' => 'date'],
                        'end_date'       => ['type' => 'date'],
                        'quota_total'    => ['type' => 'integer'],
                        'benefit_amount' => ['type' => 'float'],
                        'is_active'      => ['type' => 'boolean'],
                        'created_at'     => ['type' => 'date'],
                        'updated_at'     => ['type' => 'date'],
                    ],
                ],
            ],
        ];

        if (!$this->indexExists()) {
            $this->searchEngine->indices()->create($params);
            Log::info("SABANA: Elasticsearch index '" . self::INDEX . "' berhasil dibuat.");
        }
    }

    public function deleteIndex(): void
    {
        if ($this->indexExists()) {
            $this->searchEngine->indices()->delete(['index' => self::INDEX]);
            Log::info("SABANA: Elasticsearch index '" . self::INDEX . "' dihapus.");
        }
    }

    public function indexExists(): bool
    {
        try {
            /** @var ElasticsearchResponse $response */
            $response = $this->searchEngine->indices()->exists(['index' => self::INDEX]);
            return $response->asBool();
        } catch (\Throwable) {
            return false;
        }
    }
}