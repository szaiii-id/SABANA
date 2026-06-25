<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Disbursement;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Services\Admin\DisbursementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class DisbursementServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private DisbursementService $service;
    private Admin $admin;
    private AssistanceProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->service = app(DisbursementService::class);

        $this->admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Disbursement',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->program = AssistanceProgram::query()->create([
            'name' => 'Disbursement Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);
    }

    private function createSubmission(string $status = 'validated'): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Disburse Citizen',
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
            'status' => $status,
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (1 test) =====

    /** @test */
    public function test_disburse_creates_disbursement_and_completes_submission(): void
    {
        $submission = $this->createSubmission('validated');

        $this->service->disburse($submission->id, $this->admin, 'Disalurkan via BPD');

        $submission->refresh();

        $this->assertEquals('completed', $submission->status);
        $this->assertDatabaseHas('disbursements', [
            'submission_id' => $submission->id,
            'amount' => 500000.00,
        ]);
    }

    // ===== SAD PATH (3 test) =====

    /** @test */
    public function test_disburse_throws_for_non_validated(): void
    {
        $submission = $this->createSubmission('pending');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hanya submission yang sudah disetujui');

        $this->service->disburse($submission->id, $this->admin);
    }

   /** @test */
    public function test_cannot_disburse_twice(): void
    {
        $submission = $this->createSubmission('validated');

        $this->service->disburse($submission->id, $this->admin);

        // Submission sudah completed
        $this->assertEquals('completed', $submission->fresh()->status);

        // Coba disbursement lagi
        try {
            $this->service->disburse($submission->id, $this->admin);
            $this->fail('Seharusnya throw exception');
        } catch (\Exception $e) {
            $this->assertStringContainsStringIgnoringCase('disetujui', $e->getMessage());
        }
    }

    /** @test */
    public function test_disburse_throws_for_nonexistent(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->disburse('00000000-0000-0000-0000-000000000000', $this->admin);
    }

    // ===== BOUNDARY (1 test) =====

    /** @test */
    public function test_disburse_with_null_notes(): void
    {
        $submission = $this->createSubmission('validated');

        $this->service->disburse($submission->id, $this->admin, null);

        $this->assertEquals('completed', $submission->fresh()->status);
    }

    // ===== SECURITY (1 test) =====

    /** @test */
    public function test_get_list_only_shows_validated(): void
    {
        $this->createSubmission('validated');
        $this->createSubmission('pending');

        $result = $this->service->getList($this->admin, []);

        $this->assertEquals(1, $result->total());
    }
}