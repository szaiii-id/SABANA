<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Repositories\VerificationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VerificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private VerificationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalsel']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Tanah Laut']);

        $this->repository = app(VerificationRepository::class);
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

    private function createSubmission(array $overrides = []): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen',
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

        return AssistanceSubmission::query()->create(array_merge([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ], $overrides));
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_find_by_id_returns_submission(): void
    {
        $submission = $this->createSubmission();

        $found = $this->repository->findById($submission->id);

        $this->assertInstanceOf(AssistanceSubmission::class, $found);
        $this->assertEquals($submission->id, $found->id);
    }

    public function test_find_by_id_with_lock_returns_submission(): void
    {
        $submission = $this->createSubmission();

        $found = $this->repository->findByIdWithLock($submission->id);

        $this->assertInstanceOf(AssistanceSubmission::class, $found);
    }

    public function test_update_status_changes_status(): void
    {
        $submission = $this->createSubmission(['status' => 'pending']);

        $this->repository->updateStatus($submission, 'validated');

        $this->assertEquals('validated', $submission->fresh()->status);
    }

    public function test_create_verification_record_inserts_to_db(): void
    {
        $submission = $this->createSubmission();
        $admin = $this->createAdmin();

        $this->repository->createVerificationRecord([
            'submission_id' => $submission->id,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'action_type' => 'approved',
            'notes' => 'Test',
        ]);

        $this->assertDatabaseHas('submission_verifications', [
            'submission_id' => $submission->id,
            'action_type' => 'approved',
        ]);
    }

    public function test_clear_verification_list_cache_flushes(): void
    {
        \Illuminate\Support\Facades\Cache::shouldReceive('tags')
            ->with(['verification-list'])
            ->once()
            ->andReturn(new class {
                public function flush() {}
            });

        $this->repository->clearVerificationListCache();

        $this->assertTrue(true);
    }

    // ===== SAD PATH (1 test) =====

    public function test_find_by_id_returns_null_for_unknown(): void
    {
        $found = $this->repository->findById('00000000-0000-0000-0000-000000000000');

        $this->assertNull($found);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_find_by_id_with_lock_returns_null_for_unknown(): void
    {
        $found = $this->repository->findByIdWithLock('00000000-0000-0000-0000-000000000000');

        $this->assertNull($found);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_update_status_returns_boolean(): void
    {
        $submission = $this->createSubmission();

        $result = $this->repository->updateStatus($submission, 'validated');

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}