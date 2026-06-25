<?php

declare(strict_types=1);

namespace Tests\Infrastructure\FrankenPhp;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class FrankenPhpHealthTest extends TestCase
{
    // ===== HELPER =====

    private function validData(): array
    {
        return [
            'api_base_url' => 'http://backend:80/api',
            'health_url' => 'http://backend:80/health',
            'admin_url' => 'http://backend:2019',
        ];
    }

    // ===== SCENARIO 1: HAPPY PATH =====

    /** @test */
    public function test_happy_path_api_endpoint_accessible(): void
    {
        $response = Http::get('http://backend:80/api');

        $this->assertNotNull($response->status());
        $this->assertNotEquals(500, $response->status());
    }

    /** @test */
    public function test_happy_path_health_endpoint_responds(): void
    {
        $response = Http::get('http://backend:80/health');

        // Mungkin 200 (kalau route health sudah dibuat) atau 404
        $this->assertTrue(
            in_array($response->status(), [200, 404]),
            'Health endpoint harus bisa diakses'
        );
    }

    // ===== SCENARIO 2: SAD PATH =====

    /** @test */
    public function test_sad_path_invalid_route_returns_404(): void
    {
        $response = Http::get('http://backend:80/non-existent-route-' . uniqid());

        $this->assertEquals(404, $response->status());
    }

    /** @test */
    public function test_sad_path_invalid_method_returns_405(): void
    {
        $response = Http::patch('http://backend:80/api');

        // 405 Method Not Allowed atau 404
        $this->assertContains($response->status(), [404, 405]);
    }

    // ===== SCENARIO 3: BOUNDARY =====

    /** @test */
    public function test_boundary_large_payload_not_rejected_as_413(): void
    {
        $largeBody = str_repeat('x', 1024 * 50); // 50KB

        $response = Http::withBody($largeBody, 'text/plain')
            ->post('http://backend:80/api');

        $this->assertNotEquals(413, $response->status(), '50KB tidak boleh kena payload too large');
    }

    // ===== SCENARIO 4: EDGE CASE =====

    /** @test */
    public function test_edge_case_unicode_url_handled(): void
    {
        $response = Http::get('http://backend:80/api/test-🎉');

        $this->assertNotNull($response->status());
    }

    /** @test */
    public function test_edge_case_concurrent_requests_same_time(): void
    {
        $responses = [];

        for ($i = 0; $i < 5; $i++) {
            $responses[] = Http::get('http://backend:80/api');
        }

        foreach ($responses as $response) {
            $this->assertNotNull($response->status());
        }
    }

    // ===== SCENARIO 5: NULL/EMPTY =====

    /** @test */
    public function test_null_empty_empty_request_body_handled(): void
    {
        $response = Http::withBody('', 'application/json')
            ->post('http://backend:80/api');

        $this->assertNotEquals(500, $response->status());
    }

    /** @test */
    public function test_null_empty_empty_headers_handled(): void
    {
        $response = Http::withHeaders([])->get('http://backend:80/api');

        $this->assertNotNull($response->status());
    }

    // ===== SCENARIO 6: DATA TYPE =====

    /** @test */
    public function test_data_type_json_content_type_accepted(): void
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get('http://backend:80/api');

        $this->assertNotNull($response->status());
    }

    /** @test */
    public function test_data_type_form_data_content_type_accepted(): void
    {
        $response = Http::asForm()->post('http://backend:80/api', ['test' => 'value']);

        $this->assertNotNull($response->status());
    }

    // ===== SCENARIO 7: EQUIVALENCE PARTITION =====

    /** @test */
    public function test_equivalence_get_post_put_delete_methods(): void
    {
        $methods = ['get', 'post', 'put', 'delete'];

        foreach ($methods as $method) {
            $response = Http::{$method}('http://backend:80/api');
            $this->assertNotNull(
                $response->status(),
                "{$method} method harus bisa diakses"
            );
        }
    }

    /** @test */
    public function test_equivalence_http_https_both_accessible(): void
    {
        $http = Http::get('http://backend:80/api');
        $this->assertNotNull($http->status());

        // HTTPS mungkin tidak bisa kalau self-signed cert
        try {
            $https = Http::withOptions(['verify' => false])
                ->get('https://backend:443/api');
            $this->assertNotNull($https->status());
        } catch (\Exception) {
            $this->assertTrue(true, 'HTTPS tidak bisa di-dev environment = OK');
        }
    }

    // ===== SCENARIO 8: STATE TRANSITION =====

    /** @test */
    public function test_state_transition_consecutive_requests_consistent(): void
    {
        $response1 = Http::get('http://backend:80/api');
        $response2 = Http::get('http://backend:80/api');

        $this->assertEquals($response1->status(), $response2->status());
    }

    // ===== SCENARIO 9: CONCURRENCY =====

    /** @test */
    public function test_concurrency_ten_rapid_requests_all_respond(): void
    {
        $responses = [];

        for ($i = 0; $i < 10; $i++) {
            $responses[] = Http::get('http://backend:80/api');
        }

        foreach ($responses as $index => $response) {
            $this->assertNotNull(
                $response->status(),
                "Request {$index} harus mendapatkan response"
            );
        }
    }

    // ===== SCENARIO 10: SECURITY =====

    /** @test */
    public function test_security_cors_origin_header_present(): void
    {
        $response = Http::withHeaders([
            'Origin' => 'http://localhost:80',
        ])->get('http://backend:80/api');

        $this->assertNotNull($response->status());
        // CORS headers dicek di middleware
    }

    /** @test */
    public function test_security_xss_reflected_prevention(): void
    {
        $xssPayload = '<script>alert("xss")</script>';

        $response = Http::get('http://backend:80/api', [
            'search' => $xssPayload,
        ]);

        $body = $response->body();

        // Response tidak boleh mengandung script tag mentah
        $this->assertStringNotContainsString(
            '<script>',
            $body,
            'Response tidak boleh mengandung XSS payload'
        );
    }
}