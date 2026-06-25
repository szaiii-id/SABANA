<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\EvaluationLog;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\Admin;
use App\Enums\EvaluationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EvaluationLogTest extends TestCase
{
    use RefreshDatabase;

    // ===== HELPER =====

    private function createEvaluationLog(array $overrides = []): EvaluationLog
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

        return EvaluationLog::query()->create(array_merge([
            'submission_id' => $submission->id,
            'program_id' => $program->id,
            'citizen_id' => $citizen->id,
            'village_id' => '6301010001',
            'district_id' => '6301010',
            'regency_id' => '6301',
            'status' => EvaluationStatus::TRIGGERED,
            'triggered_by' => 'system',
            'triggered_at' => now(),
        ], $overrides));
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_evaluation_log_creates_with_uuid(): void
    {
        $log = $this->createEvaluationLog();

        $this->assertNotEmpty($log->id);
        $this->assertEquals(36, strlen($log->id));
    }

    public function test_evaluation_log_belongs_to_submission(): void
    {
        $log = $this->createEvaluationLog();

        $this->assertNotNull($log->submission);
        $this->assertEquals($log->submission_id, $log->submission->id);
    }

    public function test_evaluation_log_belongs_to_citizen(): void
    {
        $log = $this->createEvaluationLog();

        $this->assertNotNull($log->citizen);
        $this->assertEquals($log->citizen_id, $log->citizen->id);
    }

    public function test_status_label_returns_string(): void
    {
        $log = $this->createEvaluationLog(['status' => EvaluationStatus::TRIGGERED]);

        $this->assertIsString($log->status_label);
    }

    public function test_status_color_returns_string(): void
    {
        $log = $this->createEvaluationLog(['status' => EvaluationStatus::TRIGGERED]);

        $this->assertIsString($log->status_color);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_status_enum_casts_correctly(): void
    {
        $log = $this->createEvaluationLog(['status' => EvaluationStatus::APPROVED]);

        $this->assertInstanceOf(EvaluationStatus::class, $log->status);
        $this->assertEquals(EvaluationStatus::APPROVED, $log->status);
    }

    public function test_old_data_is_array(): void
    {
        $log = $this->createEvaluationLog(['old_data' => ['usia' => 30, 'penghasilan' => 1500000]]);

        $this->assertIsArray($log->old_data);
        $this->assertEquals(30, $log->old_data['usia']);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_new_submission_can_be_null(): void
    {
        $log = $this->createEvaluationLog(['new_submission_id' => null]);

        $this->assertNull($log->new_submission_id);
        $this->assertNull($log->newSubmission);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_decision_notes_can_be_null(): void
    {
        $log = $this->createEvaluationLog(['decision_notes' => null]);

        $this->assertNull($log->decision_notes);
    }

    public function test_decided_by_can_be_null(): void
    {
        $log = $this->createEvaluationLog(['decided_by' => null]);

        $this->assertNull($log->decided_by);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_triggered_at_is_datetime(): void
    {
        $log = $this->createEvaluationLog();

        $this->assertInstanceOf(\DateTime::class, $log->triggered_at);
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_scope_pending_decision(): void
    {
        $this->createEvaluationLog(['status' => EvaluationStatus::UPDATED]);
        $this->createEvaluationLog(['status' => EvaluationStatus::TRIGGERED]);

        $pending = EvaluationLog::pendingDecision()->count();

        $this->assertEquals(1, $pending);
    }

    public function test_scope_active(): void
    {
        $this->createEvaluationLog(['status' => EvaluationStatus::TRIGGERED]);
        $this->createEvaluationLog(['status' => EvaluationStatus::UPDATED]);
        $this->createEvaluationLog(['status' => EvaluationStatus::APPROVED]);

        $active = EvaluationLog::active()->count();

        $this->assertGreaterThan(0, $active);
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_status_can_transition(): void
    {
        $log = $this->createEvaluationLog(['status' => EvaluationStatus::TRIGGERED]);

        $log->update(['status' => EvaluationStatus::UPDATED]);

        $this->assertEquals(EvaluationStatus::UPDATED, $log->fresh()->status);
    }

    // ===== SECURITY (1 test) =====

    public function test_mass_assignment_does_not_override_id(): void
    {
        $log = $this->createEvaluationLog();
        $originalId = $log->id;

        $log->fill(['id' => 'fake-uuid']);

        $this->assertEquals($originalId, $log->id);
    }
}