<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ElasticsearchRobustnessTest extends TestCase
{
    private function getAuth()
    {
        // Mengambil kredensial dari .env
        return [
            'user' => 'elastic', // Username default elasticsearch
            'pass' => env('ES_PASSWORD', 'sabana01!')
        ];
    }

    public function test_1_elasticsearch_connection_success(): void
    {
        $url = env('ELASTICSEARCH_HOST', 'http://sabana_search:9200');
        $auth = $this->getAuth();

        // Tambahkan withBasicAuth
        $response = Http::withBasicAuth($auth['user'], $auth['pass'])->get($url);
        
        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('cluster_name', $response->json());
    }

    public function test_2_elasticsearch_failure_on_invalid_host(): void
    {
        $this->expectException(\Exception::class);
        Http::timeout(1)->get('http://host_fiktif_sabana:9200');
    }

    public function test_3_elasticsearch_cluster_health_status(): void
    {
        $url = env('ELASTICSEARCH_HOST', 'http://sabana_search:9200');
        $auth = $this->getAuth();

        $response = Http::withBasicAuth($auth['user'], $auth['pass'])->get($url . '/_cluster/health');
        
        $this->assertEquals(200, $response->status());
        $status = $response->json()['status'];
        $this->assertContains($status, ['green', 'yellow'], "Cluster Health Error! Status: $status");
    }

    public function test_4_elasticsearch_latency_under_50ms(): void
    {
        $url = env('ELASTICSEARCH_HOST', 'http://sabana_search:9200');
        $auth = $this->getAuth();

        $start = microtime(true);
        Http::withBasicAuth($auth['user'], $auth['pass'])->get($url);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $duration, "Elasticsearch terlalu lambat! Durasi: {$duration}ms");
    }

    public function test_5_elasticsearch_index_not_found_exception(): void
    {
        $url = env('ELASTICSEARCH_HOST', 'http://sabana_search:9200');
        $auth = $this->getAuth();

        // Sekarang 401 harusnya sudah tidak ada, yang muncul harus 404 (Not Found)
        $response = Http::withBasicAuth($auth['user'], $auth['pass'])->get($url . '/index_fiktif_sabana/_search');
        
        $this->assertEquals(404, $response->status());
    }
}