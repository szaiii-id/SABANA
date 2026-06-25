<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Assistance;

use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\AssistanceSubmission;
use App\Services\Assistance\AssistanceSubmissionService;
use App\Exceptions\SubmissionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AssistanceSubmissionServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private AssistanceSubmissionService $service;
    private Citizen $citizen;
    private AssistanceProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Storage::fake('cloudinary');

        $this->service = app(AssistanceSubmissionService::class);

        $this->citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Integration Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $this->program = AssistanceProgram::query()->create([
            'name' => 'Integration Program ' . uniqid(),
            'description' => 'Deskripsi integration',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
            'criteria' => [
                'inputs' => [
                    ['key' => 'usia', 'label' => 'Usia', 'type' => 'number'],
                    ['key' => 'penghasilan', 'label' => 'Penghasilan', 'type' => 'currency'],
                ],
            ],
        ]);
    }

    // ===== HAPPY PATH (3 test) =====

    /** @test */
    public function test_submit_creates_submission_successfully(): void
    {
        $payload = [
            'program_id' => $this->program->id,
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'disbursement_method' => 'bpd_transfer',
            'bank_account_number' => '1234567890',
            'usia' => 30,
            'penghasilan' => 1500000,
        ];

        $submission = $this->service->submit($this->citizen, $payload, []);

        $this->assertInstanceOf(AssistanceSubmission::class, $submission);
        $this->assertEquals('pending', $submission->status);
        $this->assertStringStartsWith('SBN-', $submission->registration_number);
        $this->assertDatabaseHas('assistance_submissions', ['id' => $submission->id]);
    }

    /** @test */
    public function test_submit_with_files_creates_evidences(): void
    {
        $payload = [
            'program_id' => $this->program->id,
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'disbursement_method' => 'village_cash',
            'usia' => 25,
            'penghasilan' => 2000000,
        ];

        $files = [
            'ktp' => UploadedFile::fake()->image('ktp.jpg', 100, 100),
            'kk' => UploadedFile::fake()->image('kk.jpg', 100, 100),
        ];

        $submission = $this->service->submit($this->citizen, $payload, $files);

        $this->assertDatabaseHas('assistance_evidences', ['submission_id' => $submission->id]);
        $this->assertEquals(2, $submission->evidences()->count());
    }

    /** @test */
    public function test_cancel_submission_soft_deletes(): void
    {
        $submission = AssistanceSubmission::query()->create([
            'citizen_id' => $this->citizen->id,
            'program_id' => $this->program->id,
            'registration_number' => 'SBN-CANCEL01',
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['usia' => 30],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $this->service->cancelSubmission('SBN-CANCEL01', $this->citizen->id);

        $this->assertSoftDeleted('assistance_submissions', ['id' => $submission->id]);
    }

    // ===== SAD PATH (3 test) =====

    /** @test */
    public function test_submit_throws_for_program_not_found(): void
    {
        $payload = [
            'program_id' => '550e8400-e29b-41d4-a716-446655449999',
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'disbursement_method' => 'bpd_transfer',
            'bank_account_number' => '1234567890',
        ];

        $this->expectException(SubmissionException::class);

        $this->service->submit($this->citizen, $payload, []);
    }

    /** @test */
    public function test_submit_throws_when_quota_full(): void
    {
        $fullProgram = AssistanceProgram::query()->create([
            'name' => 'Full Quota Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 1,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

        // Isi kuota
        AssistanceSubmission::query()->create([
            'citizen_id' => $this->citizen->id,
            'program_id' => $fullProgram->id,
            'registration_number' => 'SBN-FULL01',
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['usia' => 30],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $payload = [
            'program_id' => $fullProgram->id,
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'disbursement_method' => 'bpd_transfer',
            'bank_account_number' => '1234567890',
        ];

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('Kuota program sudah penuh');

        $this->service->submit($this->citizen, $payload, []);
    }

    /** @test */
    public function test_submit_throws_for_program_not_started(): void
    {
        $futureProgram = AssistanceProgram::query()->create([
            'name' => 'Future Program ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

        $payload = [
            'program_id' => $futureProgram->id,
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'disbursement_method' => 'bpd_transfer',
            'bank_account_number' => '1234567890',
        ];

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('Program belum dibuka');

        $this->service->submit($this->citizen, $payload, []);
    }

    // ===== BOUNDARY (1 test) =====

    /** @test */
    public function test_submit_idempotency_prevents_duplicate(): void
    {
        $payload = [
            'program_id' => $this->program->id,
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'disbursement_method' => 'bpd_transfer',
            'bank_account_number' => '1234567890',
            'usia' => 30,
            'penghasilan' => 1500000,
        ];

        $this->service->submit($this->citizen, $payload, [], 'IDEMPOTENCY-KEY-001');

        $this->expectException(SubmissionException::class);
        $this->expectExceptionMessage('Pengajuan sedang diproses');

        $this->service->submit($this->citizen, $payload, [], 'IDEMPOTENCY-KEY-001');
    }


}