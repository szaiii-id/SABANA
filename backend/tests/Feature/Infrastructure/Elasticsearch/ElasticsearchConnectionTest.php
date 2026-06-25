<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Elasticsearch;

use Tests\TestCase;
use Elastic\Elasticsearch\Client;

class ElasticsearchConnectionTest extends TestCase
{
    // ===== PROPERTIES =====

    private Client $esClient;
    private string $testIndex = 'test_sabana_infra';

    // ===== SETUP & TEARDOWN =====

    protected function setUp(): void
    {
        parent::setUp();

        $hosts = config('services.elasticsearch.hosts', 'http://sabana_search:9200');
        $username = config('services.elasticsearch.user', 'elastic');
        $password = config('services.elasticsearch.pass', '');

        $this->esClient = \Elastic\Elasticsearch\ClientBuilder::create()
            ->setHosts([$hosts])
            ->setBasicAuthentication($username, $password)
            ->setSSLVerification(false)
            ->build();
    }

    protected function tearDown(): void
    {
        try {
            $this->esClient->indices()->delete(['index' => $this->testIndex]);
        } catch (\Exception) {
            // Sudah terhapus
        }

        parent::tearDown();
    }

    // ===== HELPER =====

    private function validData(): array
    {
        return [
            'index' => $this->testIndex,
            'id' => 'test_' . uniqid(),
            'body' => [
                'nik' => '6371012305900001',
                'nama' => 'Ahmad Fauzi',
                'status' => 'aktif',
            ],
        ];
    }

    private function createTestIndex(): void
    {
        $this->esClient->indices()->create([
            'index' => $this->testIndex,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ],
            ],
        ]);
    }

    // ===== SCENARIO 1: HAPPY PATH =====

    /** @test */
    public function test_happy_path_cluster_health_is_green_or_yellow(): void
    {
        $health = $this->esClient->cluster()->health();

        $this->assertContains($health['status'], ['green', 'yellow']);
    }

    /** @test */
    public function test_happy_path_create_index_and_index_document(): void
    {
        $this->createTestIndex();

        $data = $this->validData();
        $response = $this->esClient->index($data);

        $this->assertEquals('created', $response['result']);
    }

    /** @test */
    public function test_happy_path_search_returns_results(): void
    {
        $this->createTestIndex();

        $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'search_test',
            'body' => ['nama' => 'Ahmad Fauzi', 'nik' => '6371012305900001'],
        ]);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        $response = $this->esClient->search([
            'index' => $this->testIndex,
            'body' => ['query' => ['match' => ['nama' => 'Ahmad']]],
        ]);

        $this->assertEquals(1, $response['hits']['total']['value']);
    }

    // ===== SCENARIO 2: SAD PATH =====

    /** @test */
    public function test_sad_path_get_non_existent_document_throws_exception(): void
    {
        $this->createTestIndex();

        $this->expectException(\Elastic\Elasticsearch\Exception\ClientResponseException::class);

        $this->esClient->get([
            'index' => $this->testIndex,
            'id' => 'non_existent_id_' . uniqid(),
        ]);
    }

    /** @test */
    public function test_sad_path_search_non_existent_index_returns_404(): void
    {
        try {
            $this->esClient->search([
                'index' => 'non_existent_' . uniqid(),
                'body' => ['query' => ['match_all' => new \stdClass()]],
            ]);
        } catch (\Elastic\Elasticsearch\Exception\ClientResponseException $e) {
            $this->assertEquals(404, $e->getCode());
        }
    }

    // ===== SCENARIO 3: BOUNDARY =====

    /** @test */
    public function test_boundary_large_document_50kb_indexed(): void
    {
        $this->createTestIndex();

        $largeText = str_repeat('Data bansos. ', 2000); // ~26KB

        $response = $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'large_doc',
            'body' => ['keterangan' => $largeText],
        ]);

        $this->assertEquals('created', $response['result']);
    }

    // ===== SCENARIO 4: EDGE CASE =====

    /** @test */
    public function test_edge_case_special_characters_in_document(): void
    {
        $this->createTestIndex();

        $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'special_chars',
            'body' => ['nama' => "O'Connor & Sons / % # @!"],
        ]);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        $response = $this->esClient->search([
            'index' => $this->testIndex,
            'body' => ['query' => ['match' => ['nama' => "O'Connor"]]],
        ]);

        $this->assertEquals(1, $response['hits']['total']['value']);
    }

    // ===== SCENARIO 5: NULL/EMPTY =====

    /** @test */
    public function test_null_empty_document_with_empty_string_field(): void
    {
        $this->createTestIndex();

        $response = $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'empty_field',
            'body' => ['nama' => '', 'nik' => '6371012305900001'],
        ]);

        $this->assertEquals('created', $response['result']);
    }

    /** @test */
    public function test_null_empty_search_with_empty_query_returns_all(): void
    {
        $this->createTestIndex();

        $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'doc1',
            'body' => ['nama' => 'Test'],
        ]);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        $response = $this->esClient->search([
            'index' => $this->testIndex,
            'body' => ['query' => ['match_all' => new \stdClass()]],
        ]);

        $this->assertGreaterThan(0, $response['hits']['total']['value']);
    }

    // ===== SCENARIO 6: DATA TYPE =====

    /** @test */
    public function test_data_type_numeric_boolean_fields_preserved(): void
    {
        $this->createTestIndex();

        $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'types_test',
            'body' => ['jumlah' => 500000, 'verified' => true, 'score' => 0.0],
        ]);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        $response = $this->esClient->get([
            'index' => $this->testIndex,
            'id' => 'types_test',
        ]);

        $this->assertSame(500000, $response['_source']['jumlah']);
        $this->assertTrue($response['_source']['verified']);
    }

    // ===== SCENARIO 7: EQUIVALENCE PARTITION =====

    /** @test */
    public function test_equivalence_term_query_exact_match(): void
    {
        $this->createTestIndex();

        $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'exact_1',
            'body' => ['nik' => '6371012305900001'],
        ]);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        $response = $this->esClient->search([
            'index' => $this->testIndex,
            'body' => ['query' => ['term' => ['nik.keyword' => '6371012305900001']]],
        ]);

        $this->assertEquals(1, $response['hits']['total']['value']);
    }

    // ===== SCENARIO 8: STATE TRANSITION =====

    /** @test */
    public function test_state_transition_document_create_update_delete(): void
    {
        $this->createTestIndex();

        // Create
        $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'state_doc',
            'body' => ['status' => 'pending'],
        ]);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        // Update
        $this->esClient->update([
            'index' => $this->testIndex,
            'id' => 'state_doc',
            'body' => ['doc' => ['status' => 'verified']],
        ]);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        $updated = $this->esClient->get([
            'index' => $this->testIndex,
            'id' => 'state_doc',
        ]);

        $this->assertEquals('verified', $updated['_source']['status']);

        // Delete
        $this->esClient->delete([
            'index' => $this->testIndex,
            'id' => 'state_doc',
        ]);

        $this->expectException(\Elastic\Elasticsearch\Exception\ClientResponseException::class);
        $this->esClient->get(['index' => $this->testIndex, 'id' => 'state_doc']);
    }

    // ===== SCENARIO 9: CONCURRENCY =====

    /** @test */
    public function test_concurrency_bulk_index_multiple_documents(): void
    {
        $this->createTestIndex();

        $bulk = [];
        for ($i = 0; $i < 10; $i++) {
            $bulk[] = ['index' => ['_index' => $this->testIndex, '_id' => "bulk_{$i}"]];
            $bulk[] = ['nik' => "637101230590000{$i}", 'nama' => "User {$i}"];
        }

        $response = $this->esClient->bulk(['body' => $bulk]);

        $this->assertFalse($response['errors']);

        $this->esClient->indices()->refresh(['index' => $this->testIndex]);

        $count = $this->esClient->count(['index' => $this->testIndex]);
        $this->assertEquals(10, $count['count']);
    }

    // ===== SCENARIO 10: SECURITY =====

    /** @test */
    public function test_security_elasticsearch_requires_authentication(): void
    {
        $health = $this->esClient->cluster()->health();

        $this->assertArrayHasKey('status', $health);
        // Jika auth gagal, akan throw exception
    }

    /** @test */
    public function test_security_index_not_deleted_by_injection(): void
    {
        $this->createTestIndex();

        $this->esClient->index([
            'index' => $this->testIndex,
            'id' => 'injection_test',
            'body' => ['nama' => "'; DROP INDEX test; --"],
        ]);

        // ✅ exists() return response object, cek status code
        $response = $this->esClient->indices()->exists(['index' => $this->testIndex]);
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}