<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Fonnte;

use Tests\TestCase;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Http;

final class FonnteServiceTest extends TestCase
{
    private FonnteService $service;
    private string $testTarget = '6281234567890';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FonnteService();
        Http::preventStrayRequests(); // Cegah request beneran
    }

    protected function tearDown(): void
    {
        Http::allowStrayRequests(); // Reset
        parent::tearDown();
    }

    // ===== HAPPY PATH =====

    /** @test */
    public function test_send_message_returns_true_on_success(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result = $this->service->sendMessage($this->testTarget, 'Test message');

        $this->assertTrue($result);
    }

    /** @test */
    public function test_send_message_posts_to_correct_endpoint(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $this->service->sendMessage($this->testTarget, 'Hello');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request['target'] === '6281234567890'
                && $request['message'] === 'Hello'
                && $request['countryCode'] === '62';
        });
    }

    // ===== SAD PATH =====

    /** @test */
    public function test_send_message_returns_false_on_failure(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['reason' => 'Invalid token'], 401),
        ]);

        $result = $this->service->sendMessage($this->testTarget, 'Test');

        $this->assertFalse($result);
    }

    /** @test */
    public function test_send_message_handles_timeout(): void
    {
        Http::fake([
            'api.fonnte.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $result = $this->service->sendMessage($this->testTarget, 'Test');

        $this->assertFalse($result);
    }

    // ===== BOUNDARY =====

    /** @test */
    public function test_send_message_with_long_text(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $longMessage = str_repeat('A', 1000);
        $result = $this->service->sendMessage($this->testTarget, $longMessage);

        $this->assertTrue($result);
    }

    /** @test */
    public function test_send_message_with_special_characters(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $result = $this->service->sendMessage($this->testTarget, "Halo\n*Bold*\n_Italic_\n~Strikethrough~");

        $this->assertTrue($result);
    }

    // ===== SECURITY =====

    /** @test */
    public function test_send_message_includes_auth_header(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $this->service->sendMessage($this->testTarget, 'Test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization');
        });
    }
}