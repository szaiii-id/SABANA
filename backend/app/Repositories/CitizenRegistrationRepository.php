<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRegistrationRepositoryInterface;
use Elastic\Elasticsearch\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class CitizenRegistrationRepository implements CitizenRegistrationRepositoryInterface
{
    private const SEARCHABLE_COLUMNS = [
        'id',
        'nik',
        'full_name',
        'family_card_number',
        'whatsapp_number',
        'is_verified',
    ];

    private const CACHE_TTL_SECONDS = 300; // 5 menit
    private const CACHE_PREFIX_LIST = 'citizen_list:';
    private const CACHE_PREFIX_SEARCH = 'citizen_search:';
    private const ELASTICSEARCH_INDEX = 'citizens';

    public function __construct(
        private ?Client $elasticsearch = null
    ) {}

    // ===== FIND (NO CACHE — single row lookup) =====

    public function findByNik(string $nik): ?Citizen
    {
        return Citizen::where('nik', $nik)->first();
    }

    public function findByNikWithLock(string $nik): ?Citizen
    {
        return Citizen::where('nik', $nik)
            ->lockForUpdate()
            ->first();
    }

    // ===== CREATE / UPDATE WITH CACHE INVALIDATION =====

    public function create(array $data): Citizen
    {
        $citizen = Citizen::create($data);
        $this->invalidateListCache();
        return $citizen;
    }

    public function update(Citizen $citizen, array $data): Citizen
    {
        $citizen->update($data);
        $this->invalidateListCache();
        $this->forgetCache(self::CACHE_PREFIX_SEARCH . md5($citizen->nik));
        return $citizen->fresh();
    }

    // ===== SEARCH WITH ELASTICSEARCH (FALLBACK MYSQL) =====

    public function search(string $query): array
    {
        $cacheKey = self::CACHE_PREFIX_SEARCH . md5($query);

        return $this->rememberCache($cacheKey, function () use ($query) {
            // Coba Elasticsearch dulu
            $esResult = $this->searchUsingElasticsearch($query, 10, 0);

            if ($esResult !== null) {
                return $esResult['hits'];
            }

            // Fallback ke MySQL
            Log::info('Elasticsearch unavailable, using MySQL search fallback', [
                'query' => $query,
            ]);

            return $this->searchUsingMysql($query);
        });
    }

    // ===== SEARCH PAGINATED WITH ELASTICSEARCH (FALLBACK MYSQL) =====

    public function searchPaginated(string $query, int $page = 1, int $perPage = 10): array
    {
        $cacheKey = self::CACHE_PREFIX_SEARCH . md5("{$query}:{$page}:{$perPage}");

        return $this->rememberCache($cacheKey, function () use ($query, $page, $perPage) {
            $from = ($page - 1) * $perPage;

            // Coba Elasticsearch dulu
            $esResult = $this->searchUsingElasticsearch($query, $perPage, $from);

            if ($esResult !== null) {
                $total = $esResult['total'] ?? 0;
                return [
                    'data'         => $esResult['hits'],
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'has_more'     => ($from + $perPage) < $total,
                ];
            }

            // Fallback ke MySQL
            Log::info('Elasticsearch unavailable, using MySQL paginated search fallback', [
                'query' => $query,
            ]);

            return $this->searchPaginatedUsingMysql($query, $page, $perPage);
        });
    }

    // ===== GET LIST WITH CACHE =====

    public function getList(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildListQuery($filters, $perPage);
    }

    // ===== ELASTICSEARCH SEARCH ENGINE =====

    /**
     * Search using Elasticsearch with multi_match query and fuzzy matching.
     * Returns null if Elasticsearch is not available, so caller can fallback to MySQL.
     */
    private function searchUsingElasticsearch(string $query, int $size, int $from): ?array
    {
        if (!$this->elasticsearch) {
            return null;
        }

        try {
            $lowerQuery = strtolower($query);

            $response = $this->elasticsearch->search([
                'index' => self::ELASTICSEARCH_INDEX,
                'body'  => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                [
                                    'match' => [
                                        'nik' => [
                                            'query'    => $query,
                                            'analyzer' => 'standard',
                                            'boost'    => 3.0,
                                        ],
                                    ],
                                ],
                                [
                                    'wildcard' => [
                                        'nik' => [
                                            'value'            => "*{$query}*",
                                            'boost'            => 3.0,
                                            'case_insensitive' => true,
                                        ],
                                    ],
                                ],
                                [
                                    'match' => [
                                        'full_name' => [
                                            'query'    => $query,
                                            'analyzer' => 'standard',
                                            'boost'    => 2.0,
                                        ],
                                    ],
                                ],
                                [
                                    'wildcard' => [
                                        'full_name' => [
                                            'value'            => "*{$lowerQuery}*",
                                            'boost'            => 2.0,
                                            'case_insensitive' => true,
                                        ],
                                    ],
                                ],
                                [
                                    'match' => [
                                        'family_card_number' => [
                                            'query'    => $query,
                                            'analyzer' => 'standard',
                                            'boost'    => 1.0,
                                        ],
                                    ],
                                ],
                                [
                                    'wildcard' => [
                                        'family_card_number' => [
                                            'value'            => "*{$query}*",
                                            'boost'            => 1.0,
                                            'case_insensitive' => true,
                                        ],
                                    ],
                                ],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                    '_source' => self::SEARCHABLE_COLUMNS,
                    'from'    => $from,
                    'size'    => $size,
                    'sort'    => [
                        '_score' => ['order' => 'desc'],
                    ],
                ],
            ]);

            return [
                'hits'  => array_map(
                    fn(array $hit): array => $hit['_source'],
                    $response['hits']['hits']
                ),
                'total' => $response['hits']['total']['value'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ===== MYSQL FALLBACKS =====

    /**
     * MySQL fallback search — digunakan jika Elasticsearch tidak tersedia.
     */
    private function searchUsingMysql(string $query): array
    {
        return Citizen::where('nik', 'like', "%{$query}%")
            ->orWhere('full_name', 'like', "%{$query}%")
            ->limit(10)
            ->get(self::SEARCHABLE_COLUMNS)
            ->toArray();
    }

    /**
     * MySQL fallback paginated search.
     */
    private function searchPaginatedUsingMysql(string $query, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $queryBuilder = Citizen::where('nik', 'like', "%{$query}%")
            ->orWhere('full_name', 'like', "%{$query}%")
            ->select(self::SEARCHABLE_COLUMNS)
            ->orderBy('full_name');

        $total = $queryBuilder->count();

        $results = $queryBuilder
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->toArray();

        return [
            'data'         => $results,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'has_more'     => ($offset + $perPage) < $total,
        ];
    }

    // ===== PRIVATE HELPERS =====

    private function buildListQuery(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Citizen::query();

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nik', 'like', "%{$searchTerm}%")
                  ->orWhere('full_name', 'like', "%{$searchTerm}%");
            });
        }

        return $query
            ->select(self::SEARCHABLE_COLUMNS)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Cache remember dengan fallback — kalau cache driver gagal, langsung query.
     */
    private function rememberCache(string $key, callable $callback): mixed
    {
        try {
            return Cache::remember($key, self::CACHE_TTL_SECONDS, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache failed, using direct query', [
                'key'   => $key,
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    private function forgetCache(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Exception) {
            // Cache driver tidak mendukung — ignore
        }
    }

    private function invalidateListCache(): void
    {
        try {
            // Flush by tag (Redis only)
            Cache::tags(['citizen_list'])->flush();
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate list cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}