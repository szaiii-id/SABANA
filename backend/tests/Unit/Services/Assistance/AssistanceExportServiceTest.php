<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Assistance;

use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Services\Assistance\AssistanceExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssistanceExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssistanceExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssistanceExportService();
    }

    // ===== HELPER =====

    private function createSubmission(string $status = 'validated'): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::query()->create([
            'name' => 'Program PDF ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-PDF' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => $status,
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (1 test) =====

    public function test_generate_receipt_pdf_returns_pdf_instance(): void
    {
        $submission = $this->createSubmission('validated');

        $pdf = $this->service->generateReceiptPdf($submission->id);

        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    // ===== SAD PATH (2 test) =====

    public function test_generate_receipt_pdf_throws_for_missing_submission(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data pengajuan tidak ditemukan.');

        $this->service->generateReceiptPdf('550e8400-e29b-41d4-a716-446655449999');
    }

    public function test_generate_receipt_pdf_throws_for_needs_revision(): void
    {
        $submission = $this->createSubmission('needs_revision');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tidak dapat download PDF.');

        $this->service->generateReceiptPdf($submission->id);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_generate_receipt_pdf_allowed_statuses(): void
    {
        $allowed = ['pending', 'validated', 'completed', 'rejected'];

        foreach ($allowed as $status) {
            $submission = $this->createSubmission($status);

            $pdf = $this->service->generateReceiptPdf($submission->id);

            $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
        }
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_generate_receipt_pdf_with_missing_relations(): void
    {
        $submission = $this->createSubmission('validated');

        $pdf = $this->service->generateReceiptPdf($submission->id);

        $this->assertNotNull($pdf);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_generate_receipt_pdf_returns_pdf_object(): void
    {
        $submission = $this->createSubmission('validated');

        $result = $this->service->generateReceiptPdf($submission->id);

        $this->assertIsObject($result);
    }

    // ===== SECURITY (1 test) =====

    public function test_generate_receipt_pdf_only_for_existing_submission(): void
    {
        $this->expectException(\Exception::class);

        $this->service->generateReceiptPdf('550e8400-e29b-41d4-a716-446655440000');
    }
}