<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DisbursementControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private AssistanceProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Disbursement',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->program = AssistanceProgram::query()->create([
            'name' => 'Program Test ' . uniqid(),
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
        return "/api/v1/{$prefix}/disbursements";
    }

    private function createValidatedSubmission(): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $this->program->id,
            'registration_number' => 'SBN-DSB-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'validated',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_index_returns_list(): void
    {
        $this->createValidatedSubmission();

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_store_disburses_submission(): void
    {
        $submission = $this->createValidatedSubmission();

        $response = $this->postJson($this->baseUrl(), [
            'submission_id' => $submission->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Bantuan berhasil disalurkan.');
    }

    public function test_bulk_store_processes_multiple(): void
    {
        $sub1 = $this->createValidatedSubmission();
        $sub2 = $this->createValidatedSubmission();

        $response = $this->postJson("{$this->baseUrl()}/bulk", [
            'submission_ids' => [$sub1->id, $sub2->id],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('count'));
    }

    // ===== SAD PATH (3 test) =====

    public function test_store_without_submission_id_returns_422(): void
    {
        $response = $this->postJson($this->baseUrl(), []);

        $response->assertStatus(422);
    }

    public function test_store_with_invalid_submission_id_returns_422(): void
    {
        $response = $this->postJson($this->baseUrl(), [
            'submission_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertStatus(422);
    }

    public function test_bulk_store_with_empty_array_returns_422(): void
    {
        $response = $this->postJson("{$this->baseUrl()}/bulk", [
            'submission_ids' => [],
        ]);

        $response->assertStatus(422);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_store_with_notes_max_500_chars(): void
    {
        $submission = $this->createValidatedSubmission();

        $response = $this->postJson($this->baseUrl(), [
            'submission_id' => $submission->id,
            'notes' => str_repeat('A', 500),
        ]);

        $response->assertStatus(201);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_index_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    public function test_store_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->postJson($this->baseUrl(), ['submission_id' => 'some-id']);

        $response->assertStatus(401);
    }

    // ===== SECURITY (1 test) =====

    public function test_bulk_store_exceeds_max_50_returns_422(): void
    {
        $ids = array_fill(0, 51, '00000000-0000-0000-0000-000000000000');

        $response = $this->postJson("{$this->baseUrl()}/bulk", [
            'submission_ids' => $ids,
        ]);

        $response->assertStatus(422);
    }
}