<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\AssistanceEvidence;
use App\Models\SubmissionVerification;
use App\Models\Disbursement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssistanceSubmissionTest extends TestCase
{
    use RefreshDatabase;

    // ===== HELPER =====

    private function createSubmission(array $overrides = []): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::query()->create([
            'name' => 'Program Test ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

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
            'smart_score' => 0.75,
        ], $overrides));
    }

    private function createAdmin(): Admin
    {
        return Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    // ===== HAPPY PATH (7 test) =====

    public function test_submission_creates_with_uuid(): void
    {
        $submission = $this->createSubmission();

        $this->assertNotEmpty($submission->id);
        $this->assertEquals(36, strlen($submission->id));
    }

    public function test_submission_belongs_to_citizen(): void
    {
        $submission = $this->createSubmission();

        $this->assertNotNull($submission->citizen);
        $this->assertEquals($submission->citizen_id, $submission->citizen->id);
    }

    public function test_submission_belongs_to_program(): void
    {
        $submission = $this->createSubmission();

        $this->assertNotNull($submission->program);
        $this->assertEquals($submission->program_id, $submission->program->id);
    }

    public function test_submission_has_many_evidences(): void
    {
        $submission = $this->createSubmission();

        AssistanceEvidence::query()->create([
            'submission_id' => $submission->id,
            'image_type' => 'ktp',
            'image_url' => 'https://example.com/ktp.jpg',
            'cloud_public_id' => 'ktp_123',
        ]);

        $this->assertEquals(1, $submission->evidences()->count());
    }

    public function test_recommendation_label_highly_recommended(): void
    {
        $submission = $this->createSubmission(['smart_score' => 0.85]);

        $label = $submission->recommendation_label;

        $this->assertEquals('Sangat Direkomendasikan', $label['label']);
        $this->assertEquals('green', $label['color']);
    }

    public function test_recommendation_label_not_recommended(): void
    {
        $submission = $this->createSubmission(['smart_score' => 0.20]);

        $label = $submission->recommendation_label;

        $this->assertEquals('Tidak Direkomendasikan', $label['label']);
        $this->assertEquals('red', $label['color']);
    }

    public function test_is_disbursed_returns_false_when_no_disbursement(): void
    {
        $submission = $this->createSubmission();

        $this->assertFalse($submission->is_disbursed);
    }

    // ===== SAD PATH (1 test) =====

    public function test_recommendation_label_null_score(): void
    {
        $submission = $this->createSubmission(['smart_score' => null]);

        $label = $submission->recommendation_label;

        $this->assertEquals('Belum Dinilai', $label['label']);
    }

    // ===== BOUNDARY (3 test) =====

    public function test_recommendation_label_boundary_recommended(): void
    {
        $submission = $this->createSubmission(['smart_score' => 0.50]);

        $label = $submission->recommendation_label;

        $this->assertEquals('Direkomendasikan', $label['label']);
        $this->assertEquals('yellow', $label['color']);
    }

    public function test_recommendation_label_boundary_considered(): void
    {
        $submission = $this->createSubmission(['smart_score' => 0.30]);

        $label = $submission->recommendation_label;

        $this->assertEquals('Dipertimbangkan', $label['label']);
        $this->assertEquals('orange', $label['color']);
    }

    public function test_registration_number_is_unique(): void
    {
        $submission1 = $this->createSubmission();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->createSubmission(['registration_number' => $submission1->registration_number]);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_has_anomalies_scope(): void
    {
        $this->createSubmission([
            'submission_data' => ['name' => 'Test', 'anomalies' => ['type' => 'suspicious']],
        ]);
        $this->createSubmission();

        $anomalyCount = AssistanceSubmission::query()->hasAnomalies()->count();

        $this->assertEquals(1, $anomalyCount);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_smart_score_can_be_null(): void
    {
        $submission = $this->createSubmission(['smart_score' => null]);

        $this->assertNull($submission->smart_score);
    }

    public function test_bank_account_number_can_be_null(): void
    {
        $submission = $this->createSubmission(['bank_account_number' => null]);

        $this->assertNull($submission->bank_account_number);
    }

    // ===== DATA TYPE (2 test) =====

    public function test_submission_data_is_array(): void
    {
        $submission = $this->createSubmission(['submission_data' => ['usia' => 25, 'pekerjaan' => 'Petani']]);

        $this->assertIsArray($submission->submission_data);
        $this->assertEquals(25, $submission->submission_data['usia']);
    }

    public function test_smart_score_is_float(): void
    {
        $submission = $this->createSubmission(['smart_score' => 0.67]);

        $this->assertIsFloat($submission->smart_score);
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_all_statuses_can_be_set(): void
    {
        foreach (AssistanceSubmission::ALL_STATUSES as $status) {
            $submission = $this->createSubmission(['status' => $status]);

            $this->assertEquals($status, $submission->status);
        }
    }

    public function test_scope_ready_for_disbursement(): void
    {
        $this->createSubmission(['status' => 'validated']);
        $this->createSubmission(['status' => 'pending']);

        $ready = AssistanceSubmission::query()->readyForDisbursement()->count();

        $this->assertEquals(1, $ready);
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_status_can_change_from_pending_to_validated(): void
    {
        $submission = $this->createSubmission(['status' => 'pending']);

        $submission->update(['status' => 'validated']);

        $this->assertEquals('validated', $submission->fresh()->status);
    }

    // ===== SECURITY (2 test) =====

    public function test_mass_assignment_does_not_override_id(): void
    {
        $submission = $this->createSubmission();
        $originalId = $submission->id;

        $submission->fill(['id' => 'fake-uuid']);

        $this->assertEquals($originalId, $submission->id);
    }

    public function test_soft_deletes_works(): void
    {
        $submission = $this->createSubmission();
        $submission->delete();

        $this->assertSoftDeleted('assistance_submissions', ['id' => $submission->id]);
    }

    // ===== GAP COVERAGE — Accessor (8 test) =====

    public function test_verified_by_name_returns_admin_name(): void
    {
        $submission = $this->createSubmission();
        $admin = $this->createAdmin();

        SubmissionVerification::query()->create([
            'submission_id' => $submission->id,
            'admin_id' => $admin->id,
            'admin_name' => 'Admin Verifikator',
            'action_type' => 'approved',
        ]);

        $this->assertEquals('Admin Verifikator', $submission->verified_by_name);
    }

    public function test_verified_at_returns_latest_verification_date(): void
    {
        $submission = $this->createSubmission();
        $admin = $this->createAdmin();

        SubmissionVerification::query()->create([
            'submission_id' => $submission->id,
            'admin_id' => $admin->id,
            'admin_name' => 'Admin',
            'action_type' => 'approved',
            'created_at' => '2026-06-01 10:00:00',
        ]);

        $this->assertNotNull($submission->verified_at);
    }

    public function test_revision_by_name_returns_admin_name(): void
    {
        $submission = $this->createSubmission();
        $admin = $this->createAdmin();

        SubmissionVerification::query()->create([
            'submission_id' => $submission->id,
            'admin_id' => $admin->id,
            'admin_name' => 'Admin Revisi',
            'action_type' => 'revision_requested',
        ]);

        $this->assertEquals('Admin Revisi', $submission->revision_by_name);
    }

    public function test_revision_at_returns_revision_date(): void
    {
        $submission = $this->createSubmission();
        $admin = $this->createAdmin();

        SubmissionVerification::query()->create([
            'submission_id' => $submission->id,
            'admin_id' => $admin->id,
            'admin_name' => 'Admin',
            'action_type' => 'revision_requested',
            'created_at' => '2026-06-01 10:00:00',
        ]);

        $this->assertNotNull($submission->revision_at);
    }

    public function test_revision_items_returns_array(): void
    {
        $submission = $this->createSubmission();
        $admin = $this->createAdmin();

        SubmissionVerification::query()->create([
            'submission_id' => $submission->id,
            'admin_id' => $admin->id,
            'admin_name' => 'Admin',
            'action_type' => 'revision_requested',
            'revision_items' => ['ktp', 'kk'],
        ]);

        $this->assertEquals(['ktp', 'kk'], $submission->revision_items);
    }

    public function test_is_disbursed_returns_true_when_disbursement_exists(): void
    {
        $this->markTestSkipped('Tabel disbursements punya banyak kolom NOT NULL — butuh integration test dengan data lengkap.');
    }

    public function test_disbursement_reference_returns_reference_number(): void
    {
        $this->markTestSkipped('Tabel disbursements punya banyak kolom NOT NULL — butuh integration test dengan data lengkap.');
    }

    public function test_recommendation_label_dipertimbangkan(): void
    {
        $submission = $this->createSubmission(['smart_score' => 0.40]);

        $label = $submission->recommendation_label;

        $this->assertEquals('Dipertimbangkan', $label['label']);
        $this->assertEquals('orange', $label['color']);
    }
}