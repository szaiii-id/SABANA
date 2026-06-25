<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Ai;

use Tests\TestCase;
use App\Services\Ai\AiAnalysisService;
use Illuminate\Support\Facades\Http;

final class AiAnalysisServiceTest extends TestCase
{
    private AiAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AiAnalysisService();
    }

    // ===== [1] HAPPY PATH =====
    public function test_extract_text_success(): void
    {
        Http::fake([
            '*/ocr/extract' => Http::response([
                'success'   => true,
                'full_text' => 'NIK: 6301234567890123',
                'matches'   => [],
            ], 200),
        ]);

        $result = $this->service->extractText('https://example.com/image.jpg', ['nik']);

        $this->assertTrue($result['success']);
        $this->assertEquals('NIK: 6301234567890123', $result['full_text']);
    }

    public function test_match_fields_success(): void
    {
        Http::fake([
            '*/nlp/match' => Http::response([
                'success' => true,
                'results' => [
                    ['label' => 'NIK', 'key' => 'nik', 'match_status' => 'cocok', 'match_score' => 95],
                ],
                'summary' => ['total' => 1, 'cocok' => 1],
            ], 200),
        ]);

        $result = $this->service->matchFields('NIK: 6301234567890123', [
            ['key' => 'nik', 'label' => 'NIK', 'value' => '6301234567890123'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['results']);
    }

    public function test_analyze_evidence_full_pipeline(): void
    {
        Http::fake([
            '*/ocr/extract' => Http::response([
                'success'   => true,
                'full_text' => 'NIK: 6301234567890123',
                'matches'   => [],
            ], 200),
            '*/nlp/match' => Http::response([
                'success' => true,
                'results' => [['label' => 'NIK', 'key' => 'nik', 'match_status' => 'cocok', 'match_score' => 95]],
                'summary' => ['total' => 1, 'cocok' => 1],
            ], 200),
        ]);

        $result = $this->service->analyzeEvidence('https://example.com/image.jpg', [
            ['key' => 'nik', 'label' => 'NIK', 'value' => '6301234567890123'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['ocr_text']);
    }

    // ===== [2] SAD PATH =====
    public function test_extract_text_failure(): void
    {
        Http::fake([
            '*/ocr/extract' => Http::response(['success' => false], 500),
        ]);

        $result = $this->service->extractText('https://example.com/image.jpg', []);

        $this->assertFalse($result['success']);
    }

    public function test_match_fields_failure(): void
    {
        Http::fake([
            '*/nlp/match' => Http::response(null, 500),
        ]);

        $result = $this->service->matchFields('text', []);

        $this->assertFalse($result['success']);
    }

    // ===== [3] NULL / EMPTY =====
    public function test_analyze_evidence_ocr_failure_returns_error(): void
    {
        Http::fake([
            '*/ocr/extract' => Http::response(['success' => false], 500),
        ]);

        $result = $this->service->analyzeEvidence('https://example.com/image.jpg', []);

        $this->assertFalse($result['success']);
        $this->assertNull($result['ocr_text']);
    }
}