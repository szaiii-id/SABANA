<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\EvaluationLog;
use App\Enums\EvaluationStatus;
use App\Repositories\EvaluationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class EvaluationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EvaluationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(EvaluationRepository::class);
    }

    // ===== HELPER =====

    private function createAdmin(string $role = 'super_admin'): Admin
    {
        return Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Test',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createEvaluationLog(string $status = 'triggered'): EvaluationLog
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Eval Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::query()->create([
            'name' => 'Eval Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

        $submission = AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-EVAL-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'evaluation_pending',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        return EvaluationLog::query()->create([
            'submission_id' => $submission->id,
            'program_id' => $program->id,
            'citizen_id' => $citizen->id,
            'village_id' => '6301010001',
            'district_id' => '6301010',
            'regency_id' => '6301',
            'status' => $status,
            'triggered_by' => 'system',
            'triggered_at' => now(),
        ]);
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_get_by_wilayah_returns_paginator(): void
    {
        $admin = $this->createAdmin('super_admin');
        $this->createEvaluationLog();

        $result = $this->repository->getByWilayah($admin, [], 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertGreaterThan(0, $result->total());
    }

    public function test_find_by_submission_returns_log(): void
    {
        $log = $this->createEvaluationLog('triggered');

        $found = $this->repository->findBySubmission($log->submission_id);

        $this->assertInstanceOf(EvaluationLog::class, $found);
        $this->assertEquals($log->id, $found->id);
    }

    public function test_update_status_changes_record(): void
    {
        $log = $this->createEvaluationLog('triggered');

        $this->repository->updateStatus($log, [
            'status' => EvaluationStatus::UPDATED->value,
            'decision_notes' => 'Updated by test',
        ]);

        $this->assertEquals(EvaluationStatus::UPDATED, $log->fresh()->status);
    }

    // ===== SAD PATH (1 test) =====

    public function test_find_by_submission_returns_null_for_inactive(): void
    {
        $log = $this->createEvaluationLog('revoked');

        $found = $this->repository->findBySubmission($log->submission_id);

        $this->assertNull($found);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_by_wilayah_with_status_filter(): void
    {
        $admin = $this->createAdmin('super_admin');
        $this->createEvaluationLog('triggered');
        $this->createEvaluationLog('updated');

        $result = $this->repository->getByWilayah($admin, ['status' => 'triggered'], 15);

        $this->assertEquals(1, $result->total());
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_get_by_wilayah_with_empty_filters(): void
    {
        $admin = $this->createAdmin('super_admin');
        $this->createEvaluationLog();

        $result = $this->repository->getByWilayah($admin, [], 15);

        $this->assertGreaterThan(0, $result->total());
    }

    // ===== DATA TYPE (1 test) =====

    public function test_find_by_submission_returns_evaluation_log_or_null(): void
    {
        $log = $this->createEvaluationLog('triggered');

        $found = $this->repository->findBySubmission($log->submission_id);

        $this->assertTrue($found instanceof EvaluationLog || is_null($found));
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====
    public function test_find_by_new_submission_returns_log(): void
    {
        $log = $this->createEvaluationLog('updated');

        // Buat submission baru untuk foreign key
        $newSubmission = AssistanceSubmission::query()->create([
            'citizen_id' => $log->citizen_id,
            'program_id' => $log->program_id,
            'registration_number' => 'SBN-NEW-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['name' => 'New'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $log->update(['new_submission_id' => $newSubmission->id]);

        $found = $this->repository->findByNewSubmission($newSubmission->id);

        $this->assertInstanceOf(EvaluationLog::class, $found);
    }

    // ===== SECURITY (1 test) =====

    public function test_get_by_wilayah_respects_role(): void
    {
        $regencyAdmin = $this->createAdmin('regency_admin');
        $this->createEvaluationLog();

        $result = $this->repository->getByWilayah($regencyAdmin, [], 15);

        $this->assertEquals(0, $result->total());
    }
}