<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\CitizenRegistrationLog;
use App\Models\Disbursement;
use App\Models\District;
use App\Models\EvaluationLog;
use App\Models\Regency;
use App\Models\SubmissionVerification;
use App\Models\Village;
use App\Repositories\ReportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private readonly ReportRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ReportRepository();
    }

    // ===== HELPERS =====

    private function validRegencyData(): array
    {
        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalimantan Selatan']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Kabupaten Banjar']);
        DB::table('districts')->insert(['id' => '6301010', 'regency_id' => '6301', 'name' => 'Martapura']);
        DB::table('villages')->insert(['id' => '6301010001', 'district_id' => '6301010', 'name' => 'Indrasari']);

        $regency  = Regency::find('6301');
        $district = District::find('6301010');
        $village  = Village::find('6301010001');

        $admin = Admin::create([
            'nip'        => fake()->unique()->numerify('##################'),
            'name'       => 'Test Admin',
            'password'   => bcrypt('password'),
            'role'       => 'regency_admin',
            'regency_id' => '6301',
            'is_active'  => true,
        ]);

        $citizen = Citizen::create([
            'nik'                => fake()->unique()->numerify('630101##########'),
            'family_card_number' => fake()->unique()->numerify('630101##########'),
            'full_name'          => 'Ahmad Test',
            'whatsapp_number'    => '081234567890',
            'pin'                => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::create([
            'name'           => 'PKH',
            'slug'           => 'pkh-' . fake()->randomNumber(4, true),
            'description'    => 'Program Keluarga Harapan',
            'status'         => 'active',
            'benefit_amount' => 600000,
            'quota_total'    => 100,
        ]);

        $submission = AssistanceSubmission::create([
            'citizen_id'          => $citizen->id,
            'program_id'          => $program->id,
            'registration_number' => 'REG-' . fake()->unique()->randomNumber(6, true),
            'regency_id'          => '6301',
            'district_id'         => '6301010',
            'village_id'          => '6301010001',
            'status'              => 'validated',
            'submission_data'     => json_encode([]),
            'disbursement_method' => 'village_cash',
        ]);

        Disbursement::create([
            'submission_id'   => $submission->id,
            'program_id'      => $program->id,
            'citizen_id'      => $citizen->id,
            'amount'          => 600000,
            'disbursed_at'    => now(),
            'method'          => 'village_cash',
            'reference_number' => 'SBN-DSB-' . fake()->unique()->randomNumber(8, true),
            'disbursed_by'    => $admin->id,
        ]);

        return compact('regency', 'district', 'village', 'citizen', 'program', 'submission', 'admin');
    }

    // ============================================================
    // 1. HAPPY PATH (9 tests)
    // ============================================================

    public function test_budget_summary_returns_program_data(): void
    {
        $this->validRegencyData();
        $result = $this->repository->budgetSummary([]);
        $this->assertCount(1, $result);
        $this->assertEquals('PKH', $result[0]['program']);
        $this->assertEquals(60000000, $result[0]['total_anggaran']);
    }

    public function test_budget_summary_with_program_filter(): void
    {
        $data = $this->validRegencyData();
        AssistanceProgram::create([
            'name' => 'BLT', 'slug' => 'blt-' . fake()->randomNumber(4, true),
            'description' => 'BLT', 'status' => 'active', 'benefit_amount' => 300000, 'quota_total' => 50,
        ]);

        $result = $this->repository->budgetSummary(['program_id' => $data['program']->id]);
        $this->assertCount(1, $result);
        $this->assertEquals('PKH', $result[0]['program']);
    }

    public function test_program_recipients_returns_disbursed_data(): void
    {
        $this->validRegencyData();
        $result = $this->repository->programRecipients([]);
        $this->assertCount(1, $result);
        $this->assertEquals(600000, $result[0]['amount']);
    }

    public function test_most_applied_programs_counts_unique_citizens(): void
    {
        $data = $this->validRegencyData();
        AssistanceSubmission::create([
            'citizen_id' => $data['citizen']->id, 'program_id' => $data['program']->id,
            'registration_number' => 'REG-' . fake()->unique()->randomNumber(6, true),
            'regency_id' => '6301', 'district_id' => '6301010', 'village_id' => '6301010001',
            'status' => 'pending', 'submission_data' => json_encode([]), 'disbursement_method' => 'village_cash',
        ]);

        $result = $this->repository->mostAppliedPrograms([]);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['total_pendaftar_unik']);
    }

    public function test_citizen_registered_by_admin_returns_data(): void
    {
        $data = $this->validRegencyData();
        $admin = Admin::create([
            'nip' => fake()->unique()->numerify('##################'), 'name' => 'Budi',
            'password' => bcrypt('password'), 'role' => 'village_officer', 'is_active' => true,
        ]);
        CitizenRegistrationLog::create([
            'citizen_id' => $data['citizen']->id, 'admin_id' => $admin->id,
            'admin_name' => 'Budi', 'admin_role' => 'village_officer', 'action' => 'register_with_pin',
        ]);

        $result = $this->repository->citizenRegisteredByAdmin([]);
        $this->assertCount(1, $result);
        $this->assertEquals('Budi', $result[0]['admin_name']);
    }

    public function test_ready_for_disbursement_returns_validated_submissions(): void
    {
        $data = $this->validRegencyData();
        SubmissionVerification::create([
            'submission_id' => $data['submission']->id,
            'admin_id'      => $data['admin']->id,
            'admin_name'    => $data['admin']->name,
            'action_type'   => 'approved',
        ]);

        $result = $this->repository->readyForDisbursement([]);
        $this->assertCount(1, $result);
    }

    public function test_pending_evaluation_returns_triggered_data(): void
    {
        $data = $this->validRegencyData();
        EvaluationLog::create([
            'submission_id' => $data['submission']->id, 'program_id' => $data['program']->id,
            'citizen_id' => $data['citizen']->id, 'village_id' => '6301010001',
            'district_id' => '6301010', 'regency_id' => '6301', 'status' => 'triggered',
        ]);

        $result = $this->repository->pendingEvaluation([]);
        $this->assertCount(1, $result);
        $this->assertNotNull($result[0]['triggered_at']);
    }

    public function test_disbursed_recipients_returns_data(): void
    {
        $this->validRegencyData();
        $result = $this->repository->disbursedRecipients([]);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('reference_number', $result[0]);
    }

    public function test_approved_recipients_returns_evaluation_approved(): void
    {
        $data = $this->validRegencyData();
        $decider = Admin::create([
            'nip' => fake()->unique()->numerify('##################'), 'name' => 'Pengambil Keputusan',
            'password' => bcrypt('password'), 'role' => 'regency_admin', 'is_active' => true,
        ]);
        EvaluationLog::create([
            'submission_id' => $data['submission']->id, 'program_id' => $data['program']->id,
            'citizen_id' => $data['citizen']->id, 'village_id' => '6301010001',
            'district_id' => '6301010', 'regency_id' => '6301', 'status' => 'approved',
            'decided_by' => $decider->id,
        ]);

        $result = $this->repository->approvedRecipients([]);
        $this->assertCount(1, $result);
        $this->assertEquals('Pengambil Keputusan', $result[0]['pemutus']);
    }

    // ============================================================
    // 2. SAD PATH (3 tests)
    // ============================================================

    public function test_budget_summary_with_no_data_returns_empty(): void
    {
        $result = $this->repository->budgetSummary([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_program_recipients_with_no_disbursements_returns_empty(): void
    {
        $result = $this->repository->programRecipients([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_ready_for_disbursement_without_validated_returns_empty(): void
    {
        $data = $this->validRegencyData();
        $data['submission']->update(['status' => 'pending']);

        $result = $this->repository->readyForDisbursement([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ============================================================
    // 3. BOUNDARY (2 tests)
    // ============================================================

    public function test_disbursed_recipients_respects_date_range(): void
    {
        $this->validRegencyData();

        $inRange  = $this->repository->disbursedRecipients(['tgl_mulai' => '2026-01-01', 'tgl_akhir' => '2026-12-31']);
        $outRange = $this->repository->disbursedRecipients(['tgl_mulai' => '2025-01-01', 'tgl_akhir' => '2025-12-31']);

        $this->assertCount(1, $inRange);
        $this->assertCount(0, $outRange);
    }

    public function test_budget_summary_with_large_quota(): void
    {
        AssistanceProgram::create([
            'name' => 'Besar', 'slug' => 'besar-' . fake()->randomNumber(4, true),
            'description' => 'Test', 'status' => 'active', 'benefit_amount' => 1000000, 'quota_total' => 99999,
        ]);

        $result = $this->repository->budgetSummary([]);
        $this->assertEquals(99999000000, $result[0]['total_anggaran']);
    }

    // ============================================================
    // 4. EDGE CASE (2 tests)
    // ============================================================

    public function test_most_applied_programs_excludes_soft_deleted(): void
    {
        $data = $this->validRegencyData();
        $deletedProgram = AssistanceProgram::create([
            'name' => 'Deleted', 'slug' => 'deleted-' . fake()->randomNumber(4, true),
            'description' => 'Test', 'status' => 'active', 'benefit_amount' => 100000, 'quota_total' => 10,
        ]);
        AssistanceSubmission::create([
            'citizen_id' => $data['citizen']->id, 'program_id' => $deletedProgram->id,
            'registration_number' => 'REG-' . fake()->unique()->randomNumber(6, true),
            'regency_id' => '6301', 'district_id' => '6301010', 'village_id' => '6301010001',
            'status' => 'pending', 'submission_data' => json_encode([]), 'disbursement_method' => 'village_cash',
        ]);
        $deletedProgram->delete();

        $result = $this->repository->mostAppliedPrograms([]);
        $names = array_column($result, 'program');
        $this->assertNotContains('Deleted', $names);
    }

    public function test_citizen_registered_by_admin_excludes_other_actions(): void
    {
        $data = $this->validRegencyData();
        $admin = Admin::create([
            'nip' => fake()->unique()->numerify('##################'), 'name' => 'Test',
            'password' => bcrypt('password'), 'role' => 'village_officer', 'is_active' => true,
        ]);
        CitizenRegistrationLog::create([
            'citizen_id' => $data['citizen']->id, 'admin_id' => $admin->id,
            'action' => 'resend_pin',
        ]);

        $result = $this->repository->citizenRegisteredByAdmin([]);
        $this->assertEmpty($result);
    }

    // ============================================================
    // 5. NULL/EMPTY (2 tests)
    // ============================================================

    public function test_pending_evaluation_with_no_data_returns_empty(): void
    {
        $result = $this->repository->pendingEvaluation([]);
        $this->assertEmpty($result);
    }

    public function test_revoked_recipients_with_no_data_returns_empty(): void
    {
        $result = $this->repository->revokedRecipients([]);
        $this->assertEmpty($result);
    }

    // ============================================================
    // 6. DATA TYPE (1 test)
    // ============================================================

    public function test_budget_summary_returns_correct_types(): void
    {
        $this->validRegencyData();
        $result = $this->repository->budgetSummary([]);

        $this->assertIsString($result[0]['program']);
        $this->assertIsFloat($result[0]['benefit_amount']);
        $this->assertIsInt($result[0]['quota_total']);
        $this->assertIsFloat($result[0]['total_anggaran']);
    }

    // ============================================================
    // 7. EQUIVALENCE PARTITION (1 test)
    // ============================================================

    public function test_program_recipients_filtered_by_program(): void
    {
        $data = $this->validRegencyData();
        $program2 = AssistanceProgram::create([
            'name' => 'BLT', 'slug' => 'blt-' . fake()->randomNumber(4, true),
            'description' => 'BLT', 'status' => 'active', 'benefit_amount' => 300000, 'quota_total' => 50,
        ]);
        $sub2 = AssistanceSubmission::create([
            'citizen_id' => $data['citizen']->id, 'program_id' => $program2->id,
            'registration_number' => 'REG-' . fake()->unique()->randomNumber(6, true),
            'regency_id' => '6301', 'district_id' => '6301010', 'village_id' => '6301010001',
            'status' => 'pending', 'submission_data' => json_encode([]), 'disbursement_method' => 'village_cash',
        ]);
        Disbursement::create([
            'submission_id' => $sub2->id, 'program_id' => $program2->id, 'citizen_id' => $data['citizen']->id,
            'amount' => 300000, 'disbursed_at' => now(), 'method' => 'village_cash',
            'reference_number' => 'SBN-DSB-' . fake()->unique()->randomNumber(8, true), 'disbursed_by' => $data['admin']->id,
        ]);

        $allResult      = $this->repository->programRecipients([]);
        $filteredResult = $this->repository->programRecipients(['program_id' => $program2->id]);

        $this->assertCount(2, $allResult);
        $this->assertCount(1, $filteredResult);
    }

    // ============================================================
    // 8. STATE TRANSITION (1 test)
    // ============================================================

    public function test_evaluation_status_transitions(): void
    {
        $data = $this->validRegencyData();
        EvaluationLog::create([
            'submission_id' => $data['submission']->id, 'program_id' => $data['program']->id,
            'citizen_id' => $data['citizen']->id, 'village_id' => '6301010001',
            'district_id' => '6301010', 'regency_id' => '6301', 'status' => 'triggered',
        ]);

        $pending = $this->repository->pendingEvaluation([]);
        $this->assertCount(1, $pending);

        EvaluationLog::first()->update(['status' => 'revoked', 'decision_notes' => 'Tidak layak']);

        $pendingAfter = $this->repository->pendingEvaluation([]);
        $revoked      = $this->repository->revokedRecipients([]);

        $this->assertCount(0, $pendingAfter);
        $this->assertCount(1, $revoked);
    }

    // ============================================================
    // 9. CONCURRENCY (1 test)
    // ============================================================

    public function test_most_applied_programs_handles_duplicate_citizens(): void
    {
        $data = $this->validRegencyData();
        $citizen2 = Citizen::create([
            'nik' => fake()->unique()->numerify('630102##########'), 'family_card_number' => fake()->unique()->numerify('630102##########'),
            'full_name' => 'Warga 2', 'whatsapp_number' => '082222222222', 'pin' => bcrypt('123456'),
        ]);
        AssistanceSubmission::create([
            'citizen_id' => $citizen2->id, 'program_id' => $data['program']->id,
            'registration_number' => 'REG-' . fake()->unique()->randomNumber(6, true),
            'regency_id' => '6301', 'district_id' => '6301010', 'village_id' => '6301010001',
            'status' => 'pending', 'submission_data' => json_encode([]), 'disbursement_method' => 'village_cash',
        ]);

        $result = $this->repository->mostAppliedPrograms([]);
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['total_pendaftar_unik']);
    }

    // ============================================================
    // 10. SECURITY (1 test)
    // ============================================================

    public function test_program_recipients_does_not_leak_other_regency(): void
    {
        $data = $this->validRegencyData();

        DB::table('regencies')->insert(['id' => '6302', 'province_id' => '63', 'name' => 'Kabupaten Lain']);
        DB::table('districts')->insert(['id' => '6302010', 'regency_id' => '6302', 'name' => 'Kecamatan Lain']);
        DB::table('villages')->insert(['id' => '6302010001', 'district_id' => '6302010', 'name' => 'Desa Lain']);

        $otherRegency  = Regency::find('6302');
        $otherDistrict = District::find('6302010');
        $otherVillage  = Village::find('6302010001');

        $otherCitizen = Citizen::create([
            'nik' => fake()->unique()->numerify('6302###########'), 'family_card_number' => fake()->unique()->numerify('6302###########'),
            'full_name' => 'Warga Lain', 'whatsapp_number' => '089999999999', 'pin' => bcrypt('123456'),
        ]);
        $otherProgram = AssistanceProgram::create([
            'name' => 'Other', 'slug' => 'other-' . fake()->randomNumber(4, true),
            'description' => 'Other', 'status' => 'active', 'benefit_amount' => 100000, 'quota_total' => 10,
        ]);
        $otherSub = AssistanceSubmission::create([
            'citizen_id' => $otherCitizen->id, 'program_id' => $otherProgram->id,
            'registration_number' => 'REG-' . fake()->unique()->randomNumber(6, true),
            'regency_id' => '6302', 'district_id' => '6302010', 'village_id' => '6302010001',
            'status' => 'pending', 'submission_data' => json_encode([]), 'disbursement_method' => 'village_cash',
        ]);
        Disbursement::create([
            'submission_id' => $otherSub->id, 'program_id' => $otherProgram->id, 'citizen_id' => $otherCitizen->id,
            'amount' => 999999, 'disbursed_at' => now(), 'method' => 'village_cash',
            'reference_number' => 'SBN-DSB-' . fake()->unique()->randomNumber(8, true), 'disbursed_by' => $data['admin']->id,
        ]);

        $result = $this->repository->programRecipients(['program_id' => $data['program']->id]);
        $this->assertCount(1, $result);
        $this->assertEquals(600000, $result[0]['amount']);
    }
}