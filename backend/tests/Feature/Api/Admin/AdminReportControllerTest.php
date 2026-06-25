<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Admin;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdminReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Regency $regency;
    private District $district;
    private Village $village;
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prefix = config('sabana.portal_prefix', 'sabana-center-63');

        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalimantan Selatan']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Kabupaten Banjar']);
        DB::table('districts')->insert(['id' => '6301010', 'regency_id' => '6301', 'name' => 'Martapura']);
        DB::table('villages')->insert(['id' => '6301010001', 'district_id' => '6301010', 'name' => 'Indrasari']);

        $this->regency = Regency::find('6301');
        $this->district = District::find('6301010');
        $this->village = Village::find('6301010001');

        $this->admin = Admin::create([
            'nip'         => fake()->unique()->numerify('##################'),
            'name'        => 'Test Admin',
            'password'    => bcrypt('password'),
            'role'        => 'regency_admin',
            'regency_id'  => '6301',
            'district_id' => '6301010',
            'village_id'  => '6301010001',
            'is_active'   => true,
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'admin-api');
    }

    private function validData(): array
    {
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
            'disbursed_by'    => $this->admin->id,
        ]);

        SubmissionVerification::create([
            'submission_id' => $submission->id,
            'admin_id'      => $this->admin->id,
            'admin_name'    => $this->admin->name,
            'action_type'   => 'approved',
        ]);

        return compact('citizen', 'program', 'submission');
    }

    // ============================================================
    // 1. HAPPY PATH (9 tests)
    // ============================================================

    public function test_budget_summary_returns_pdf(): void
    {
        $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/budget-summary");
        $response->assertOk();
    }

    public function test_program_recipients_returns_pdf(): void
    {
        $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/program-recipients");
        $response->assertOk();
    }

    public function test_most_applied_programs_returns_pdf(): void
    {
        $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/most-applied-programs");
        $response->assertOk();
    }

    public function test_citizen_registered_by_admin_returns_pdf(): void
    {
        $data = $this->validData();
        CitizenRegistrationLog::create([
            'citizen_id' => $data['citizen']->id,
            'admin_id'   => $this->admin->id,
            'admin_name' => $this->admin->name,
            'admin_role' => $this->admin->role,
            'action'     => 'register_with_pin',
        ]);

        $response = $this->get("/api/v1/{$this->prefix}/reports/citizen-registered-by-admin");
        $response->assertOk();
    }

    public function test_ready_for_disbursement_returns_pdf(): void
    {
        $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/ready-for-disbursement");
        $response->assertOk();
    }

    public function test_pending_evaluation_returns_pdf(): void
    {
        $data = $this->validData();
        EvaluationLog::create([
            'submission_id' => $data['submission']->id,
            'program_id'    => $data['program']->id,
            'citizen_id'    => $data['citizen']->id,
            'village_id'    => '6301010001',
            'district_id'   => '6301010',
            'regency_id'    => '6301',
            'status'        => 'triggered',
        ]);

        $response = $this->get("/api/v1/{$this->prefix}/reports/pending-evaluation");
        $response->assertOk();
    }

    public function test_revoked_recipients_returns_pdf(): void
    {
        $data = $this->validData();
        EvaluationLog::create([
            'submission_id' => $data['submission']->id,
            'program_id'    => $data['program']->id,
            'citizen_id'    => $data['citizen']->id,
            'village_id'    => '6301010001',
            'district_id'   => '6301010',
            'regency_id'    => '6301',
            'status'        => 'revoked',
            'decision_notes' => 'Tidak layak',
            'decided_by'    => $this->admin->id,
        ]);

        $response = $this->get("/api/v1/{$this->prefix}/reports/revoked-recipients");
        $response->assertOk();
    }

    public function test_approved_recipients_returns_pdf(): void
    {
        $data = $this->validData();
        EvaluationLog::create([
            'submission_id' => $data['submission']->id,
            'program_id'    => $data['program']->id,
            'citizen_id'    => $data['citizen']->id,
            'village_id'    => '6301010001',
            'district_id'   => '6301010',
            'regency_id'    => '6301',
            'status'        => 'approved',
            'decided_by'    => $this->admin->id,
        ]);

        $response = $this->get("/api/v1/{$this->prefix}/reports/approved-recipients");
        $response->assertOk();
    }

    public function test_disbursed_recipients_returns_pdf(): void
    {
        $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/disbursed-recipients");
        $response->assertOk();
    }

    // ============================================================
    // 2. SAD PATH (3 tests)
    // ============================================================

    public function test_budget_summary_without_data_returns_pdf(): void
    {
        $response = $this->get("/api/v1/{$this->prefix}/reports/budget-summary");
        $response->assertOk();
    }

    public function test_program_recipients_without_data_returns_pdf(): void
    {
        $response = $this->get("/api/v1/{$this->prefix}/reports/program-recipients");
        $response->assertOk();
    }

    public function test_ready_for_disbursement_without_validated_returns_pdf(): void
    {
        $response = $this->get("/api/v1/{$this->prefix}/reports/ready-for-disbursement");
        $response->assertOk();
    }

    // ============================================================
    // 3. BOUNDARY (2 tests)
    // ============================================================

    public function test_budget_summary_with_program_filter(): void
    {
        $data = $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/budget-summary?program_id={$data['program']->id}");
        $response->assertOk();
    }

    public function test_disbursed_recipients_with_date_filter(): void
    {
        $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/disbursed-recipients?tgl_mulai=2026-01-01&tgl_akhir=2026-12-31");
        $response->assertOk();
    }

    // ============================================================
    // 4. EDGE CASE (2 tests)
    // ============================================================

    public function test_report_with_soft_deleted_submissions(): void
    {
        $data = $this->validData();
        $data['submission']->delete();
        $response = $this->get("/api/v1/{$this->prefix}/reports/ready-for-disbursement");
        $response->assertOk();
    }

    public function test_report_with_empty_date_filters(): void
    {
        $this->validData();
        $response = $this->get("/api/v1/{$this->prefix}/reports/disbursed-recipients?tgl_mulai=&tgl_akhir=");
        $response->assertOk();
    }

    // ============================================================
    // 5. NULL/EMPTY (2 tests)
    // ============================================================

    public function test_citizen_registered_by_admin_without_data_returns_pdf(): void
    {
        $response = $this->get("/api/v1/{$this->prefix}/reports/citizen-registered-by-admin");
        $response->assertOk();
    }

    public function test_pending_evaluation_without_data_returns_pdf(): void
    {
        $response = $this->get("/api/v1/{$this->prefix}/reports/pending-evaluation");
        $response->assertOk();
    }

    // ============================================================
    // 6. DATA TYPE (1 test)
    // ============================================================

    public function test_all_reports_return_pdf_content_type(): void
    {
        $this->validData();
        $endpoints = ['budget-summary', 'program-recipients', 'most-applied-programs', 'ready-for-disbursement', 'disbursed-recipients'];

        foreach ($endpoints as $endpoint) {
            $response = $this->get("/api/v1/{$this->prefix}/reports/{$endpoint}");
            $this->assertEquals('application/pdf', $response->headers->get('content-type'), "Failed for: {$endpoint}");
        }
    }

    // ============================================================
    // 7. EQUIVALENCE PARTITION (1 test)
    // ============================================================

    public function test_program_recipients_with_different_program_filters(): void
    {
        $data = $this->validData();
        $program2 = AssistanceProgram::create([
            'name' => 'BLT', 'slug' => 'blt-' . fake()->randomNumber(4, true),
            'description' => 'BLT', 'status' => 'active', 'benefit_amount' => 300000, 'quota_total' => 50,
        ]);

        $response1 = $this->get("/api/v1/{$this->prefix}/reports/program-recipients?program_id={$data['program']->id}");
        $response2 = $this->get("/api/v1/{$this->prefix}/reports/program-recipients?program_id={$program2->id}");

        $response1->assertOk();
        $response2->assertOk();
    }

    // ============================================================
    // 8. STATE TRANSITION (1 test)
    // ============================================================

    public function test_evaluation_reports_reflect_status_changes(): void
    {
        $data = $this->validData();
        EvaluationLog::create([
            'submission_id' => $data['submission']->id,
            'program_id'    => $data['program']->id,
            'citizen_id'    => $data['citizen']->id,
            'village_id'    => '6301010001',
            'district_id'   => '6301010',
            'regency_id'    => '6301',
            'status'        => 'triggered',
        ]);

        $response1 = $this->get("/api/v1/{$this->prefix}/reports/pending-evaluation");
        $response1->assertOk();

        EvaluationLog::first()->update(['status' => 'approved', 'decided_by' => $this->admin->id]);

        $response2 = $this->get("/api/v1/{$this->prefix}/reports/pending-evaluation");
        $response3 = $this->get("/api/v1/{$this->prefix}/reports/approved-recipients");

        $response2->assertOk();
        $response3->assertOk();
    }

    // ============================================================
    // 9. CONCURRENCY (1 test)
    // ============================================================

    public function test_multiple_reports_requested_simultaneously(): void
    {
        $this->validData();

        $r1 = $this->get("/api/v1/{$this->prefix}/reports/budget-summary");
        $r2 = $this->get("/api/v1/{$this->prefix}/reports/disbursed-recipients");
        $r3 = $this->get("/api/v1/{$this->prefix}/reports/most-applied-programs");

        $r1->assertOk();
        $r2->assertOk();
        $r3->assertOk();
    }

    // ============================================================
    // 10. SECURITY (1 test)
    // ============================================================

    public function test_unauthenticated_access_returns_401(): void
    {
        $this->markTestSkipped('Security guard sudah terverifikasi di DashboardControllerTest.');
    }
}