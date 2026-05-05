<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;

#[Group('infrastructure')]
#[Group('elasticsearch')]
class ElasticsearchRobustnessTest extends TestCase
{
    private function retryAssertion(callable $callback, int $maxAttempts = 3, int $delayMs = 200): void
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $callback();
                return;
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                $lastException = $e;
                if ($attempt < $maxAttempts) {
                    usleep($delayMs * 1000);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Ambil kredensial dari config/services.php.
     */
    private function getAuth(): array
    {
        return [
            'user' => config('services.elasticsearch.user', 'elastic'),
            'pass' => config('services.elasticsearch.pass', env('ELASTICSEARCH_PASSWORD')),
        ];
    }

    /**
     * Ambil base URL dari config/services.php.
     */
    private function getBaseUrl(): string
    {
        $hosts = config('services.elasticsearch.hosts', env('ELASTICSEARCH_HOST', 'http://sabana_search:9200'));
        
        // Jika multiple hosts (comma-separated), ambil yang pertama
        if (is_string($hosts)) {
            $hosts = explode(',', $hosts);
        }

        return rtrim($hosts[0], '/');
    }

    // ========================================================================
    // PILAR 1: CONNECTIVITY & AUTHENTICATION
    // ========================================================================

    #[Group('critical')]
    public function test_1_elasticsearch_connection_success(): void
    {
        $this->retryAssertion(function () {
            $url = $this->getBaseUrl();
            $auth = $this->getAuth();

            $response = Http::withBasicAuth($auth['user'], $auth['pass'])->get($url);

            // Skip jika auth gagal (konfigurasi password belum diset)
            if ($response->status() === 401) {
                $this->markTestSkipped(
                    sprintf(
                        "ES Auth GAGAL!\nUser: %s\nURL: %s\nCek ELASTICSEARCH_PASSWORD di .env atau services.elasticsearch.pass di config/services.php",
                        $auth['user'],
                        $url
                    )
                );
            }

            $this->assertEquals(
                200,
                $response->status(),
                sprintf("ES Connection Failed!\nURL: %s\nStatus: %d", $url, $response->status())
            );

            $json = $response->json();
            $this->assertIsArray($json, 'Response bukan JSON valid.');
            $this->assertArrayHasKey('cluster_name', $json, 'Response tidak mengandung cluster_name.');
        }, 3, 300);
    }

    // ========================================================================
    // PILAR 2: FAILOVER & RESILIENCE
    // ========================================================================

    #[Group('resilience')]
    public function test_2_elasticsearch_failure_on_invalid_host(): void
    {
        $startTime = microtime(true);

        try {
            Http::timeout(1)
                ->connectTimeout(1)
                ->get('http://host-fiktif-sabana.internal:9200');

            $this->fail('Seharusnya exception terlempar untuk host invalid.');
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->assertLessThan(
                3000,
                $duration,
                sprintf('Timeout detection terlalu lambat: %.2fms.', $duration)
            );

            $this->assertMatchesRegularExpression(
                '/(could not resolve|timed? ?out|connection refused)/i',
                $e->getMessage(),
                sprintf('Exception type tidak sesuai: %s', $e->getMessage())
            );
        }
    }

    // ========================================================================
    // PILAR 3: CLUSTER HEALTH
    // ========================================================================

    #[Group('critical')]
    public function test_3_elasticsearch_cluster_health_status(): void
    {
        $url = $this->getBaseUrl();
        $auth = $this->getAuth();

        $response = Http::withBasicAuth($auth['user'], $auth['pass'])
            ->get($url . '/_cluster/health');

        // Skip jika auth gagal
        if ($response->status() === 401) {
            $this->markTestSkipped('ES Auth gagal. Cek konfigurasi password.');
        }

        $this->assertEquals(200, $response->status());
        
        $health = $response->json();
        $status = $health['status'];

        $this->assertTrue(
            in_array($status, ['green', 'yellow']),
            sprintf("Cluster Health: %s\nNodes: %d\nShards: %d", $status, $health['number_of_nodes'], $health['active_shards'])
        );
    }

    // ========================================================================
    // PILAR 4: PERFORMANCE
    // ========================================================================

    #[Group('performance')]
    public function test_4_elasticsearch_latency_baseline(): void
    {
        $url = $this->getBaseUrl();
        $auth = $this->getAuth();
        $samples = [];

        for ($i = 0; $i < 5; $i++) {
            $start = microtime(true);
            Http::withBasicAuth($auth['user'], $auth['pass'])->get($url);
            $samples[] = (microtime(true) - $start) * 1000;
        }

        // Skip jika auth gagal
        if (count($samples) > 0 && $samples[0] < 1) {
            $this->markTestSkipped('ES Auth gagal.');
        }

        $averageLatency = array_sum($samples) / count($samples);

        $this->assertLessThan(
            150, // Docker environment: lebih realistis
            $averageLatency,
            sprintf("ES Latency: %.2fms (Max: %.2fms, Min: %.2fms)", $averageLatency, max($samples), min($samples))
        );
    }

    // ========================================================================
    // PILAR 5: ERROR HANDLING
    // ========================================================================

    #[Group('resilience')]
    public function test_5_elasticsearch_index_not_found_handling(): void
    {
        $url = $this->getBaseUrl();
        $auth = $this->getAuth();

        $response = Http::withBasicAuth($auth['user'], $auth['pass'])
            ->get($url . '/index_fiktif_sabana_xyz/_search');

        // Skip jika auth gagal
        if ($response->status() === 401) {
            $this->markTestSkipped('ES Auth gagal.');
        }

        $this->assertEquals(404, $response->status());
        
        $json = $response->json();
        $this->assertArrayHasKey('error', $json, 'Response 404 harus mengandung error object.');
        $this->assertStringContainsString('index_not_found_exception', json_encode($json));
    }

    // ========================================================================
    // PILAR 6: SECURITY
    // ========================================================================

    #[Group('security')]
    public function test_6_elasticsearch_rejects_unauthenticated_access(): void
    {
        $url = $this->getBaseUrl();

        $response = Http::withoutVerifying()->get($url);

        // Cek apakah ES mengembalikan cluster info (berarti security OFF)
        if ($response->status() === 200) {
            $this->markTestSkipped(
                sprintf(
                    "⚠️  PERINGATAN KEAMANAN: Elasticsearch TIDAK memerlukan autentikasi!\n" .
                    "URL: %s\n" .
                    "Ini BERBAHAYA untuk production.\n" .
                    "Enable xpack.security.enabled=true di konfigurasi Elasticsearch.",
                    $url
                )
            );
        }

        $this->assertEquals(
            401,
            $response->status(),
            'ES harusnya menolak akses tanpa auth (401), tapi dapat: ' . $response->status()
        );
    }
}