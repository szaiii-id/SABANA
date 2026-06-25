<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Repositories\DisbursementRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class DisbursementRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DisbursementRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(DisbursementRepository::class);
    }

    // ===== HELPER =====

    private function createAdmin(string $role = 'super_admin'): Admin
    {
        return Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Test',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createValidatedSubmission(?string $registrationNumber = null): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen ' . uniqid(),
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::query()->create([
            'name' => 'Program Test ' . uniqid(),
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
            'registration_number' => $registrationNumber ?? 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'validated',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_get_by_wilayah_returns_paginator(): void
    {
        $admin = $this->createAdmin('super_admin');
        $this->createValidatedSubmission();

        $result = $this->repository->getByWilayah($admin, [], 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
    }

    public function test_create_disbursement_inserts_record(): void
    {
        $submission = $this->createValidatedSubmission();
        $admin = $this->createAdmin('super_admin');

        $disbursement = $this->repository->create([
            'submission_id' => $submission->id,
            'program_id' => $submission->program_id,
            'citizen_id' => $submission->citizen_id,
            'amount' => 500000,
            'disbursed_at' => now()->toDateString(),
            'method' => 'bpd_transfer',
            'reference_number' => 'REF-TEST-001',
            'disbursed_by' => $admin->id,
        ]);

        $this->assertNotNull($disbursement->id);
        $this->assertDatabaseHas('disbursements', ['reference_number' => 'REF-TEST-001']);
    }

    public function test_get_by_wilayah_only_shows_validated(): void
    {
        $admin = $this->createAdmin('super_admin');
        $this->createValidatedSubmission();
        
        // Buat submission pending — tidak muncul
        $citizen = Citizen::query()->first();
        $program = AssistanceProgram::query()->first();
        AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-PENDING01',
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $result = $this->repository->getByWilayah($admin, [], 15);

        $this->assertEquals(1, $result->total());
    }

    // ===== SAD PATH (1 test) =====

    public function test_get_by_wilayah_excludes_already_disbursed(): void
    {
        $admin = $this->createAdmin('super_admin');
        $submission = $this->createValidatedSubmission();

        // Buat disbursement untuk submission
        \App\Models\Disbursement::query()->create([
            'submission_id' => $submission->id,
            'program_id' => $submission->program_id,
            'citizen_id' => $submission->citizen_id,
            'amount' => 500000,
            'disbursed_at' => now()->toDateString(),
            'method' => 'bpd_transfer',
            'reference_number' => 'REF-EXCLUDE',
            'disbursed_by' => $admin->id,
        ]);

        $result = $this->repository->getByWilayah($admin, [], 15);

        $this->assertEquals(0, $result->total());
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_by_wilayah_with_program_filter(): void
    {
        $admin = $this->createAdmin('super_admin');
        $submission = $this->createValidatedSubmission();

        $result = $this->repository->getByWilayah($admin, ['program_id' => $submission->program_id], 15);

        $this->assertEquals(1, $result->total());
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_get_by_wilayah_with_empty_filters(): void
    {
        $admin = $this->createAdmin('super_admin');
        $this->createValidatedSubmission();

        $result = $this->repository->getByWilayah($admin, [], 15);

        $this->assertGreaterThan(0, $result->total());
    }

    // ===== DATA TYPE (1 test) =====

    public function test_get_by_wilayah_returns_paginator_instance(): void
    {
        $admin = $this->createAdmin('super_admin');

        $result = $this->repository->getByWilayah($admin, [], 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_get_by_wilayah_with_search(): void
    {
        $admin = $this->createAdmin('super_admin');
        $this->createValidatedSubmission();

        $result = $this->repository->getByWilayah($admin, ['search' => 'Test Citizen'], 15);

        $this->assertGreaterThan(0, $result->total());
    }

    // ===== SECURITY (1 test) =====

    public function test_get_by_wilayah_respects_role_hierarchy(): void
    {
        $superAdmin = $this->createAdmin('super_admin');
        $regencyAdmin = $this->createAdmin('regency_admin');

        $this->createValidatedSubmission();

        // Super admin lihat semua
        $result1 = $this->repository->getByWilayah($superAdmin, [], 15);
        $this->assertEquals(1, $result1->total());

        // Regency admin tanpa regency_id lihat kosong
        $result2 = $this->repository->getByWilayah($regencyAdmin, [], 15);
        $this->assertEquals(0, $result2->total());
    }
}