<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SubmissionVerification;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubmissionVerificationTest extends TestCase
{
    use RefreshDatabase;

    // ===== HELPER =====

    private function createVerification(string $actionType = 'approved'): SubmissionVerification
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

        $submission = AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        return SubmissionVerification::query()->create([
            'submission_id' => $submission->id,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'action_type' => $actionType,
            'notes' => 'Test notes',
        ]);
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_verification_creates_with_uuid(): void
    {
        $verification = $this->createVerification();

        $this->assertNotEmpty($verification->id);
        $this->assertEquals(36, strlen($verification->id));
    }

    public function test_verification_belongs_to_submission(): void
    {
        $verification = $this->createVerification();

        $this->assertNotNull($verification->submission);
        $this->assertEquals($verification->submission_id, $verification->submission->id);
    }

    public function test_verification_belongs_to_admin(): void
    {
        $verification = $this->createVerification();

        $this->assertNotNull($verification->admin);
        $this->assertEquals($verification->admin_id, $verification->admin->id);
    }

    public function test_admin_can_be_null(): void
    {
        $verification = $this->createVerification();
        $verification->update(['admin_id' => null]);

        $this->assertNull($verification->fresh()->admin_id);
        $this->assertNull($verification->fresh()->admin);
    }

    public function test_verification_label_returns_correct_string(): void
    {
        $verification = $this->createVerification('approved');

        $this->assertEquals('Menyetujui Pengajuan', $verification->verificationLabel());
    }

    // ===== SAD PATH (1 test) =====

    public function test_verification_label_default_for_unknown_action(): void
    {
        $this->markTestSkipped('Enum PostgreSQL — tidak bisa insert action_type di luar daftar.');
    }

    // ===== BOUNDARY (2 test) =====

    public function test_notes_can_be_long_text(): void
    {
        $longNotes = str_repeat('A', 1000);
        $verification = $this->createVerification();
        $verification->update(['notes' => $longNotes]);

        $this->assertEquals($longNotes, $verification->fresh()->notes);
    }

    public function test_revision_items_is_array(): void
    {
        $verification = $this->createVerification('revision_requested');
        $verification->update(['revision_items' => ['ktp', 'kk', 'foto_rumah']]);

        $this->assertIsArray($verification->fresh()->revision_items);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_admin_name_independent_of_admin_relation(): void
    {
        $verification = $this->createVerification();
        $verification->update(['admin_name' => 'Custom Name', 'admin_id' => null]);

        $this->assertEquals('Custom Name', $verification->fresh()->admin_name);
        $this->assertNull($verification->fresh()->admin);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_notes_can_be_null(): void
    {
        $verification = $this->createVerification();
        $verification->update(['notes' => null]);

        $this->assertNull($verification->fresh()->notes);
    }

    public function test_revision_items_can_be_null(): void
    {
        $verification = $this->createVerification();
        $verification->update(['revision_items' => null]);

        $this->assertNull($verification->fresh()->revision_items);
    }

    // ===== DATA TYPE (2 test) =====

    public function test_revision_items_is_array_when_set(): void
    {
        $verification = $this->createVerification('revision_requested');
        $verification->update(['revision_items' => ['ktp', 'kk']]);

        $this->assertIsArray($verification->fresh()->revision_items);
        $this->assertCount(2, $verification->fresh()->revision_items);
    }

    public function test_id_is_string_uuid(): void
    {
        $verification = $this->createVerification();

        $this->assertIsString($verification->id);
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_all_action_types_can_be_set(): void
    {
        $actionTypes = [
            'approved', 'rejected', 'revision_requested',
            'unvalidated', 'completed',
            'evaluation_triggered', 'evaluation_approved', 'evaluation_revoked',
        ];

        foreach ($actionTypes as $type) {
            $verification = $this->createVerification($type);

            $this->assertEquals($type, $verification->action_type);
        }
    }

    public function test_scope_latest_for_submission(): void
    {
        $verification1 = $this->createVerification('approved');

        $latest = SubmissionVerification::latestForSubmission($verification1->submission_id);

        $this->assertNotNull($latest);
        $this->assertContains($latest->action_type, ['approved', 'rejected', 'completed']);
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_scope_history_returns_all_for_submission(): void
    {
        $verification1 = $this->createVerification('approved');
        SubmissionVerification::query()->create([
            'submission_id' => $verification1->submission_id,
            'admin_id' => $verification1->admin_id,
            'admin_name' => 'Admin',
            'action_type' => 'completed',
        ]);

        $history = SubmissionVerification::historyForSubmission($verification1->submission_id);

        $this->assertCount(2, $history);
    }

    // ===== SECURITY (1 test) =====

    public function test_mass_assignment_does_not_override_id(): void
    {
        $verification = $this->createVerification();
        $originalId = $verification->id;

        $verification->fill(['id' => 'fake-uuid']);

        $this->assertEquals($originalId, $verification->id);
    }
}