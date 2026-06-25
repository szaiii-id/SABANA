<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Evaluation;

use App\Enums\EvaluationStatus;
use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\EvaluationLog;
use App\Services\Admin\EvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class EvaluationServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private EvaluationService $service;
    private Admin $admin;
    private AssistanceProgram $program;
    private Citizen $citizen;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->service = app(EvaluationService::class);

        $this->admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Evaluator',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Eval Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $this->program = AssistanceProgram::query()->create([
            'name' => 'Eval Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);
    }

    private function createEvaluationWithSubmission(): EvaluationLog
    {
        $oldSubmission = AssistanceSubmission::query()->create([
            'citizen_id' => $this->citizen->id,
            'program_id' => $this->program->id,
            'registration_number' => 'SBN-OLD-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'evaluation_pending',
            'submission_data' => ['name' => 'Old'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $newSubmission = AssistanceSubmission::query()->create([
            'citizen_id' => $this->citizen->id,
            'program_id' => $this->program->id,
            'registration_number' => 'SBN-NEW-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['name' => 'New'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        return EvaluationLog::query()->create([
            'submission_id' => $oldSubmission->id,
            'new_submission_id' => $newSubmission->id,
            'program_id' => $this->program->id,
            'citizen_id' => $this->citizen->id,
            'village_id' => '6301010001',
            'district_id' => '6301010',
            'regency_id' => '6301',
            'status' => EvaluationStatus::UPDATED,
            'triggered_by' => 'system',
            'triggered_at' => now(),
        ]);
    }

    // ===== HAPPY PATH (2 test) =====

    /** @test */
    public function test_approve_evaluation_completes_flow(): void
    {
        $log = $this->createEvaluationWithSubmission();

        $result = $this->service->approve($log->id, $this->admin);

        $this->assertEquals('validated', $result->status);
        $this->assertEquals(EvaluationStatus::APPROVED, $log->fresh()->status);
    }

    /** @test */
    public function test_revoke_evaluation_stops_flow(): void
    {
        $log = $this->createEvaluationWithSubmission();

        $result = $this->service->revoke($log->id, 'Tidak layak menerima bantuan', $this->admin);

        $this->assertEquals('rejected', $result->status);
        $this->assertEquals(EvaluationStatus::REVOKED, $log->fresh()->status);
    }

    // ===== SAD PATH (3 test) =====

    /** @test */
    public function test_approve_throws_for_invalid_log(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Log evaluasi tidak ditemukan atau sudah diputuskan.');

        $this->service->approve('00000000-0000-0000-0000-000000000000', $this->admin);
    }

    /** @test */
    public function test_revoke_throws_without_notes(): void
    {
        $log = $this->createEvaluationWithSubmission();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Alasan penolakan evaluasi wajib diisi.');

        $this->service->revoke($log->id, '', $this->admin);
    }

    /** @test */
    public function test_approve_throws_for_already_decided(): void
    {
        $log = $this->createEvaluationWithSubmission();
        $log->update(['status' => EvaluationStatus::APPROVED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Log evaluasi tidak ditemukan atau sudah diputuskan.');

        $this->service->approve($log->id, $this->admin);
    }

    // ===== BOUNDARY (1 test) =====

    /** @test */
    public function test_get_list_returns_paginator(): void
    {
        $this->createEvaluationWithSubmission();

        $result = $this->service->getList($this->admin, []);

        $this->assertGreaterThan(0, $result->total());
    }
}