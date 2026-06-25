<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Disbursement;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\Disbursement;
use App\Services\Assistance\DisbursementReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DisbursementReceiptServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private DisbursementReceiptService $service;
    private Citizen $citizen;
    private AssistanceSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DisbursementReceiptService::class);

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
            'name' => 'Admin Disbursement',
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
    }

    // ===== HAPPY PATH (2 test) =====

    /** @test */
    public function test_get_receipt_returns_submission_with_disbursement(): void
    {
        $result = $this->service->getReceipt($this->submission->id, $this->citizen->id);

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
        $this->assertNotNull($result->disbursement);
        $this->assertEquals($this->submission->id, $result->id);
    }

    /** @test */
    public function test_generate_pdf_returns_pdf_and_registration(): void
    {
        $result = $this->service->generatePdf($this->submission->id, $this->citizen->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pdf', $result);
        $this->assertArrayHasKey('registration_number', $result);
        $this->assertEquals($this->submission->registration_number, $result['registration_number']);
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $result['pdf']);
    }

    // ===== SAD PATH (2 test) =====

    /** @test */
    public function test_get_receipt_throws_for_wrong_citizen(): void
    {
        $otherCitizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Other Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pengajuan tidak ditemukan.');

        $this->service->getReceipt($this->submission->id, $otherCitizen->id);
    }

    /** @test */
    public function test_get_receipt_throws_for_submission_without_disbursement(): void
    {
        $newSubmission = AssistanceSubmission::query()->create([
            'citizen_id' => $this->citizen->id,
            'program_id' => $this->submission->program_id,
            'registration_number' => 'SBN-NO-DSB-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'validated',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bantuan belum disalurkan.');

        $this->service->getReceipt($newSubmission->id, $this->citizen->id);
    }

    // ===== SECURITY (1 test) =====

    /** @test */
    public function test_get_receipt_only_accessible_by_owner(): void
    {
        $result = $this->service->getReceipt($this->submission->id, $this->citizen->id);

        $this->assertNotNull($result);
        $this->assertEquals($this->citizen->id, $result->citizen_id);
    }
}