<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Repositories\AssistanceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class AssistanceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AssistanceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AssistanceRepository();
    }

    // ===== HELPER =====

    private function createCitizen(array $overrides = []): Citizen
    {
        return Citizen::query()->create(array_merge([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ], $overrides));
    }

    private function createProgram(array $overrides = []): AssistanceProgram
    {
        return AssistanceProgram::query()->create(array_merge([
            'name' => 'Program Test ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ], $overrides));
    }

    private function createSubmission(array $overrides = []): AssistanceSubmission
    {
        $citizen = $this->createCitizen();
        $program = $this->createProgram();

        return AssistanceSubmission::query()->create(array_merge([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'REG-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ], $overrides));
    }

    // ===== HAPPY PATH (8 test) =====

    public function test_create_submission_returns_submission(): void
    {
        $citizen = $this->createCitizen();
        $program = $this->createProgram();

        $submission = $this->repository->createSubmission([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'REG-20260101-ABC123',
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $this->assertInstanceOf(AssistanceSubmission::class, $submission);
    }

    public function test_find_active_submission_returns_submission(): void
    {
        $submission = $this->createSubmission(['status' => 'pending']);

        $found = $this->repository->findActiveSubmission($submission->citizen_id);

        $this->assertNotNull($found);
        $this->assertEquals($submission->id, $found->id);
    }

    public function test_find_by_registration_number_returns_submission(): void
    {
        $submission = $this->createSubmission(['registration_number' => 'REG-20260101-UNIQUE']);

        $found = $this->repository->findByRegistrationNumber('REG-20260101-UNIQUE');

        $this->assertEquals($submission->id, $found->id);
    }

    public function test_get_history_by_citizen_id_returns_paginator(): void
    {
        $submission = $this->createSubmission();

        $result = $this->repository->getHistoryByCitizenId($submission->citizen_id, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
    }

    public function test_get_all_history_by_citizen_id_returns_collection(): void
    {
        $submission = $this->createSubmission();

        $result = $this->repository->getAllHistoryByCitizenId($submission->citizen_id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->count());
    }

    public function test_find_by_id_returns_submission(): void
    {
        $submission = $this->createSubmission();

        $found = $this->repository->findById($submission->id);

        $this->assertEquals($submission->id, $found->id);
    }

    public function test_has_active_submission_returns_true(): void
    {
        $submission = $this->createSubmission(['status' => 'pending']);

        $result = $this->repository->hasActiveSubmission($submission->citizen_id, $submission->program_id);

        $this->assertTrue($result);
    }

    public function test_count_active_by_program_returns_count(): void
    {
        $submission = $this->createSubmission(['status' => 'pending']);

        $count = $this->repository->countActiveByProgram($submission->program_id);

        $this->assertEquals(1, $count);
    }

    // ===== SAD PATH (2 test) =====

    public function test_find_active_submission_returns_null_when_none(): void
    {
        $citizen = $this->createCitizen();

        $found = $this->repository->findActiveSubmission($citizen->id);

        $this->assertNull($found);
    }

    public function test_has_active_submission_returns_false(): void
    {
        $citizen = $this->createCitizen();
        $program = $this->createProgram();

        $result = $this->repository->hasActiveSubmission($citizen->id, $program->id);

        $this->assertFalse($result);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_get_history_per_page_respected(): void
    {
        $citizen = $this->createCitizen();
        $program = $this->createProgram();

        foreach (range(1, 5) as $i) {
            AssistanceSubmission::query()->create([
                'citizen_id' => $citizen->id,
                'program_id' => $program->id,
                'registration_number' => 'REG-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)) . $i,
                'regency_id' => '6301',
                'district_id' => '6301010',
                'village_id' => '6301010001',
                'status' => 'pending',
                'submission_data' => ['name' => 'Test'],
                'disbursement_method' => 'bpd_transfer',
            ]);
        }

        $result = $this->repository->getHistoryByCitizenId($citizen->id, 2);

        $this->assertEquals(2, $result->perPage());
        $this->assertEquals(5, $result->total());
    }

    public function test_count_by_village_today_returns_count(): void
    {
        $this->createSubmission(['village_id' => '6301010001']);

        $count = $this->repository->countByVillageToday('6301010001');

        $this->assertEquals(1, $count);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_delete_by_registration_number_soft_deletes(): void
    {
        $submission = $this->createSubmission(['registration_number' => 'REG-DELETE-001']);

        $this->repository->deleteByRegistrationNumber('REG-DELETE-001', $submission->citizen_id);

        $this->assertSoftDeleted('assistance_submissions', ['id' => $submission->id]);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_find_active_submission_ignores_inactive_statuses(): void
    {
        $submission = $this->createSubmission(['status' => 'completed']);

        $found = $this->repository->findActiveSubmission($submission->citizen_id);

        $this->assertNull($found);
    }

    // ===== DATA TYPE (2 test) =====

    public function test_get_history_returns_paginator(): void
    {
        $submission = $this->createSubmission();

        $result = $this->repository->getHistoryByCitizenId($submission->citizen_id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_get_all_history_returns_collection(): void
    {
        $submission = $this->createSubmission();

        $result = $this->repository->getAllHistoryByCitizenId($submission->citizen_id);

        $this->assertInstanceOf(Collection::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_count_active_by_program_all_statuses(): void
    {
        $program = $this->createProgram();
        $citizen = $this->createCitizen();

        foreach (['pending', 'validated', 'needs_revision'] as $status) {
            AssistanceSubmission::query()->create([
                'citizen_id' => $citizen->id,
                'program_id' => $program->id,
                'registration_number' => 'REG-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'regency_id' => '6301',
                'district_id' => '6301010',
                'village_id' => '6301010001',
                'status' => $status,
                'submission_data' => ['name' => 'Test'],
                'disbursement_method' => 'bpd_transfer',
            ]);
        }

        $count = $this->repository->countActiveByProgram($program->id);

        $this->assertEquals(3, $count);
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_store_evidence_creates_record(): void
    {
        $submission = $this->createSubmission();

        $this->repository->storeEvidence([
            'submission_id' => $submission->id,
            'image_type' => 'ktp',
            'image_url' => 'https://example.com/ktp.jpg',
            'cloud_public_id' => 'ktp_123',
        ]);

        $this->assertDatabaseHas('assistance_evidences', ['submission_id' => $submission->id]);
    }

    // ===== SECURITY (1 test) =====

    public function test_delete_only_own_submission(): void
    {
        $submission = $this->createSubmission(['registration_number' => 'REG-OWNER-001']);
        $otherCitizen = $this->createCitizen();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Coba hapus submission dengan citizen_id yang salah
        $this->repository->deleteByRegistrationNumber('REG-OWNER-001', $otherCitizen->id);
    }
}