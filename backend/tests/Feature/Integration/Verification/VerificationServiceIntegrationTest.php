<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Verification;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Services\Admin\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class VerificationServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private VerificationService $service;
    private Admin $admin;
    private AssistanceProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->service = app(VerificationService::class);

        $this->admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Verifikator',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->program = AssistanceProgram::query()->create([
            'name' => 'Integration Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 10,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);
    }

    private function createSubmission(string $status = 'pending'): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Integration Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $this->program->id,
            'registration_number' => 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => $status,
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (5 test) =====

    /** @test */
    public function test_approve_changes_status_to_validated(): void
    {
        $submission = $this->createSubmission('pending');

        $result = $this->service->approve($submission->id, $this->admin);

        $this->assertEquals('validated', $result->status);
        $this->assertDatabaseHas('submission_verifications', [
            'submission_id' => $submission->id,
            'action_type' => 'approved',
        ]);
    }

    /** @test */
    public function test_reject_changes_status_to_rejected(): void
    {
        $submission = $this->createSubmission('pending');

        $result = $this->service->reject($submission->id, 'Tidak memenuhi syarat', $this->admin);

        $this->assertEquals('rejected', $result->status);
        $this->assertDatabaseHas('submission_verifications', [
            'submission_id' => $submission->id,
            'action_type' => 'rejected',
        ]);
    }

    /** @test */
    public function test_request_revision_changes_status(): void
    {
        $submission = $this->createSubmission('pending');

        $result = $this->service->requestRevision($submission->id, 'Lengkapi dokumen', $this->admin, ['ktp', 'kk']);

        $this->assertEquals('needs_revision', $result->status);
        $this->assertDatabaseHas('submission_verifications', [
            'submission_id' => $submission->id,
            'action_type' => 'revision_requested',
        ]);
    }

    /** @test */
    public function test_complete_changes_status(): void
    {
        $submission = $this->createSubmission('validated');

        $result = $this->service->complete($submission->id, $this->admin);

        $this->assertEquals('completed', $result->status);
    }

    /** @test */
    public function test_unvalidate_returns_to_pending(): void
    {
        $submission = $this->createSubmission('validated');

        $result = $this->service->unvalidate($submission->id, 'Data tidak valid', $this->admin);

        $this->assertEquals('pending', $result->status);
    }

    // ===== SAD PATH (4 test) =====

    /** @test */
    public function test_approve_throws_for_non_pending(): void
    {
        $submission = $this->createSubmission('validated');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Hanya pengajuan dengan status 'pending' yang bisa disetujui");

        $this->service->approve($submission->id, $this->admin);
    }

    /** @test */
    public function test_reject_throws_without_notes(): void
    {
        $submission = $this->createSubmission('pending');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Alasan penolakan wajib diisi');

        $this->service->reject($submission->id, '', $this->admin);
    }

    /** @test */
    public function test_request_revision_throws_without_notes(): void
    {
        $submission = $this->createSubmission('pending');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Catatan revisi wajib diisi');

        $this->service->requestRevision($submission->id, '', $this->admin);
    }

    /** @test */
    public function test_unvalidate_throws_for_non_validated(): void
    {
        $submission = $this->createSubmission('pending');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Hanya pengajuan dengan status 'validated' yang bisa dibatalkan persetujuannya");

        $this->service->unvalidate($submission->id, 'Alasan', $this->admin);
    }

    // ===== BOUNDARY (1 test) =====

    /** @test */
    public function test_bulk_complete_processes_multiple(): void
    {
        $sub1 = $this->createSubmission('validated');
        $sub2 = $this->createSubmission('validated');

        $count = $this->service->bulkComplete([$sub1->id, $sub2->id], $this->admin);

        $this->assertEquals(2, $count);
        $this->assertEquals('completed', $sub1->fresh()->status);
        $this->assertEquals('completed', $sub2->fresh()->status);
    }

    // ===== SECURITY (1 test) =====

    /** @test */
    public function test_approve_throws_for_nonexistent_submission(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pengajuan tidak ditemukan');

        $this->service->approve('00000000-0000-0000-0000-000000000000', $this->admin);
    }
}