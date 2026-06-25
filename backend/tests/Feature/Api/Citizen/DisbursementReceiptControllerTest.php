<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Citizen;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\Disbursement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DisbursementReceiptControllerTest extends TestCase
{
    use RefreshDatabase;

    private Citizen $citizen;
    private AssistanceSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Receipt Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::query()->create([
            'name' => 'Receipt Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

        $admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Officer',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->submission = AssistanceSubmission::query()->create([
            'citizen_id' => $this->citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-RCPT-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'completed',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        Disbursement::query()->create([
            'submission_id' => $this->submission->id,
            'program_id' => $program->id,
            'citizen_id' => $this->citizen->id,
            'amount' => 500000,
            'disbursed_at' => now()->toDateString(),
            'method' => 'bpd_transfer',
            'reference_number' => 'REF-RCPT-001',
            'disbursed_by' => $admin->id,
        ]);

        Sanctum::actingAs($this->citizen, ['*'], 'api');
    }

    // ===== HAPPY PATH (2 test) =====

    public function test_show_returns_receipt(): void
    {
        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$this->submission->id}/receipt");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.registration_number', $this->submission->registration_number);
        $response->assertJsonPath('data.amount', '500000.00');
    }

    public function test_pdf_downloads_file(): void
    {
        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$this->submission->id}/receipt/pdf");

        // PDF return download response — bisa 200 atau binary
        $this->assertContains($response->status(), [200, 201]);
    }

    // ===== SAD PATH (2 test) =====

    public function test_show_returns_404_for_unknown(): void
    {
        $response = $this->getJson('/api/v1/citizen/assistance/submissions/00000000-0000-0000-0000-000000000000/receipt');

        $response->assertStatus(404);
    }

    public function test_show_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$this->submission->id}/receipt");

        $response->assertStatus(401);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_show_contains_officer_name(): void
    {
        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$this->submission->id}/receipt");

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.officer_name'));
    }

    // ===== SECURITY (1 test) =====

    public function test_cannot_access_other_citizen_receipt(): void
    {
        $otherCitizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Other Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        Sanctum::actingAs($otherCitizen, ['*'], 'api');

        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$this->submission->id}/receipt");

        $response->assertStatus(404);
    }
}