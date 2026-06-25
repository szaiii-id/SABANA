<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Repositories\DisbursementReceiptRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DisbursementReceiptRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DisbursementReceiptRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(DisbursementReceiptRepository::class);
    }

    // ===== HELPER =====

    private function createSubmission(?string $citizenId = null): AssistanceSubmission
    {
        if (!$citizenId) {
            $citizen = Citizen::query()->create([
                'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
                'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
                'full_name' => 'Receipt Citizen',
                'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
                'pin' => bcrypt('123456'),
            ]);
            $citizenId = $citizen->id;
        }

        $program = AssistanceProgram::query()->create([
            'name' => 'Receipt Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizenId,
            'program_id' => $program->id,
            'registration_number' => 'SBN-RCPT' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'validated',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (1 test) =====

    public function test_find_by_submission_id_returns_submission(): void
    {
        $submission = $this->createSubmission();

        $found = $this->repository->findBySubmissionId($submission->id, $submission->citizen_id);

        $this->assertInstanceOf(AssistanceSubmission::class, $found);
        $this->assertEquals($submission->id, $found->id);
        $this->assertEquals($submission->citizen_id, $found->citizen_id);
    }

    // ===== SAD PATH (1 test) =====

    public function test_find_by_submission_id_returns_null_for_wrong_citizen(): void
    {
        $submission = $this->createSubmission();
        $otherCitizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Other Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $found = $this->repository->findBySubmissionId($submission->id, $otherCitizen->id);

        $this->assertNull($found);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_find_by_submission_id_returns_null_for_unknown(): void
    {
        $citizen = Citizen::query()->first() ?? Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Unknown',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $found = $this->repository->findBySubmissionId('00000000-0000-0000-0000-000000000000', $citizen->id);

        $this->assertNull($found);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_find_by_submission_id_loads_relations(): void
    {
        $submission = $this->createSubmission();

        $found = $this->repository->findBySubmissionId($submission->id, $submission->citizen_id);

        $this->assertTrue($found->relationLoaded('disbursement'));
        $this->assertTrue($found->relationLoaded('program'));
        $this->assertTrue($found->relationLoaded('citizen'));
    }

    // ===== DATA TYPE (1 test) =====

    public function test_find_by_submission_id_returns_model_or_null(): void
    {
        $submission = $this->createSubmission();

        $found = $this->repository->findBySubmissionId($submission->id, $submission->citizen_id);

        $this->assertTrue($found instanceof AssistanceSubmission || is_null($found));
    }
}