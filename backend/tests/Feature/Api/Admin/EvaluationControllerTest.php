<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\EvaluationStatus;
use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\EvaluationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EvaluationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private AssistanceProgram $program;
    private Citizen $citizen;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

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

        Sanctum::actingAs($this->admin, ['admin'], 'admin-api');
    }

    private function baseUrl(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/evaluations";
    }

    private function createEvaluationLog(): EvaluationLog
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

    // ===== HAPPY PATH (3 test) =====

    public function test_index_returns_list(): void
    {
        $this->createEvaluationLog();

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_approve_evaluation(): void
    {
        $log = $this->createEvaluationLog();

        $response = $this->postJson("{$this->baseUrl()}/{$log->id}/approve");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Evaluasi disetujui. Bantuan dilanjutkan.');
    }

    public function test_revoke_evaluation(): void
    {
        $log = $this->createEvaluationLog();

        $response = $this->postJson("{$this->baseUrl()}/{$log->id}/revoke", [
            'notes' => 'Tidak layak menerima bantuan',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Evaluasi ditolak. Bantuan dihentikan.');
    }

    // ===== SAD PATH (3 test) =====

    public function test_approve_invalid_returns_422(): void
    {
        $response = $this->postJson("{$this->baseUrl()}/00000000-0000-0000-0000-000000000000/approve");

        $response->assertStatus(422);
    }

    public function test_revoke_without_notes_returns_422(): void
    {
        $log = $this->createEvaluationLog();

        $response = $this->postJson("{$this->baseUrl()}/{$log->id}/revoke", []);

        $response->assertStatus(422);
    }

    public function test_revoke_short_notes_returns_422(): void
    {
        $log = $this->createEvaluationLog();

        $response = $this->postJson("{$this->baseUrl()}/{$log->id}/revoke", [
            'notes' => 'Singkat',
        ]);

        $response->assertStatus(422);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_index_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    // ===== SECURITY (1 test) =====

    public function test_approve_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->postJson("{$this->baseUrl()}/some-id/approve");

        $this->assertContains($response->status(), [401, 404]);
    }
}