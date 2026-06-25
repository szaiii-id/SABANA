<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Disbursement;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DisbursementTest extends TestCase
{
    use RefreshDatabase;

    // ===== HELPER =====

    private function createDisbursement(array $overrides = []): Disbursement
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
            'status' => 'validated',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Disbursement',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        return Disbursement::query()->create(array_merge([
            'submission_id' => $submission->id,
            'program_id' => $program->id,
            'citizen_id' => $citizen->id,
            'amount' => 500000,
            'disbursed_at' => now()->toDateString(),
            'method' => 'bpd_transfer',
            'reference_number' => 'REF-' . strtoupper(substr(uniqid(), -8)),
            'disbursed_by' => $admin->id,
        ], $overrides));
    }

    // ===== HAPPY PATH (4 test) =====

    public function test_disbursement_creates_with_uuid(): void
    {
        $disbursement = $this->createDisbursement();

        $this->assertNotEmpty($disbursement->id);
        $this->assertEquals(36, strlen($disbursement->id));
    }

    public function test_disbursement_belongs_to_submission(): void
    {
        $disbursement = $this->createDisbursement();

        $this->assertNotNull($disbursement->submission);
        $this->assertEquals($disbursement->submission_id, $disbursement->submission->id);
    }

    public function test_disbursement_belongs_to_citizen(): void
    {
        $disbursement = $this->createDisbursement();

        $this->assertNotNull($disbursement->citizen);
        $this->assertEquals($disbursement->citizen_id, $disbursement->citizen->id);
    }

    public function test_disbursement_belongs_to_officer(): void
    {
        $disbursement = $this->createDisbursement();

        $this->assertNotNull($disbursement->officer);
        $this->assertEquals($disbursement->disbursed_by, $disbursement->officer->id);
    }

    // ===== SAD PATH (1 test) =====

    public function test_reference_number_is_unique(): void
    {
        $disbursement1 = $this->createDisbursement();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->createDisbursement(['reference_number' => $disbursement1->reference_number]);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_amount_is_decimal(): void
    {
        $disbursement = $this->createDisbursement(['amount' => 1234567.89]);

        $this->assertEquals(1234567.89, $disbursement->amount);
    }

    public function test_disbursed_at_is_date(): void
    {
        $disbursement = $this->createDisbursement(['disbursed_at' => '2026-06-15']);

        $this->assertEquals('2026-06-15', $disbursement->disbursed_at->format('Y-m-d'));
    }

    // ===== EDGE CASE (1 test) =====

    public function test_auto_generates_reference_number(): void
    {
        $disbursement = $this->createDisbursement(['reference_number' => null]);

        $this->assertNotNull($disbursement->reference_number);
        $this->assertStringStartsWith('SBN-DSB-', $disbursement->reference_number);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_notes_can_be_null(): void
    {
        $disbursement = $this->createDisbursement(['notes' => null]);

        $this->assertNull($disbursement->notes);
    }

    // ===== DATA TYPE (2 test) =====

    public function test_amount_is_float(): void
    {
        $disbursement = $this->createDisbursement(['amount' => 750000.50]);

        $this->assertIsString($disbursement->amount); // decimal cast return string
        $this->assertEquals('750000.50', $disbursement->amount);
    }
    
    public function test_id_is_string_uuid(): void
    {
        $disbursement = $this->createDisbursement();

        $this->assertIsString($disbursement->id);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_all_methods_can_be_set(): void
    {
        $methods = ['bpd_transfer', 'village_cash'];

        foreach ($methods as $method) {
            $disbursement = $this->createDisbursement(['method' => $method]);

            $this->assertEquals($method, $disbursement->method);
        }
    }

    // ===== SECURITY (2 test) =====

    public function test_mass_assignment_does_not_override_id(): void
    {
        $disbursement = $this->createDisbursement();
        $originalId = $disbursement->id;

        $disbursement->fill(['id' => 'fake-uuid']);

        $this->assertEquals($originalId, $disbursement->id);
    }

    public function test_disbursed_at_auto_sets_today(): void
    {
        $disbursement = $this->createDisbursement(['disbursed_at' => null]);

        $this->assertEquals(now()->toDateString(), $disbursement->disbursed_at->format('Y-m-d'));
    }
}