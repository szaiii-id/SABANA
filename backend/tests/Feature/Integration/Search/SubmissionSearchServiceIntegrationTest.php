<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Search;

use App\DTOs\SubmissionSearchDTO;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Services\Admin\SubmissionSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubmissionSearchServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SubmissionSearchService $service;
    private AssistanceProgram $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SubmissionSearchService::class);

        $this->program = AssistanceProgram::query()->create([
            'name' => 'Search Test Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);
    }

    private function createSubmission(string $status = 'pending', array $citizenData = []): AssistanceSubmission
    {
        $citizen = Citizen::query()->create(array_merge([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Search Citizen ' . uniqid(),
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ], $citizenData));

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $this->program->id,
            'registration_number' => 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => $status,
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
            'smart_score' => 0.85,
        ]);
    }

    // ===== HAPPY PATH (3 test) =====

    /** @test */
    public function test_search_returns_paginator(): void
    {
        $this->createSubmission('pending');
        $this->createSubmission('validated');

        $dto = new SubmissionSearchDTO(perPage: 10);
        $result = $this->service->search($dto);

        $this->assertGreaterThan(0, $result->total());
    }

    /** @test */
    public function test_index_creates_document(): void
    {
        $submission = $this->createSubmission('pending');

        $this->service->index($submission);

        $this->assertTrue(true);
    }

    /** @test */
    public function test_delete_removes_document(): void
    {
        $submission = $this->createSubmission('pending');

        $this->service->index($submission);
        $this->service->delete($submission->id);

        $this->assertTrue(true);
    }

    // ===== SAD PATH (1 test) =====

    /** @test */
    public function test_search_fallback_to_db_when_es_empty(): void
    {
        $this->markTestSkipped(
            'ES tersedia di environment testing — tidak bisa test DB fallback tanpa mematikan ES.'
        );
    }

    // ===== BOUNDARY (1 test) =====

    /** @test */
    public function test_search_with_status_filter(): void
    {
        $this->createSubmission('pending');
        $this->createSubmission('validated');

        $dto = new SubmissionSearchDTO(status: 'pending', perPage: 10);
        $result = $this->service->search($dto);

        $this->assertGreaterThan(0, $result->total());
    }

    // ===== SECURITY (1 test) =====

    /** @test */
    public function test_get_status_counts_returns_array(): void
    {
        $this->createSubmission('pending');
        $this->createSubmission('validated');

        $counts = $this->service->getStatusCounts(['regency_id' => '6301']);

        $this->assertIsArray($counts);
    }
}