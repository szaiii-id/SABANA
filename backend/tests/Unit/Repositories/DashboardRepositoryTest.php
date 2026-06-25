<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\Disbursement;
use App\Repositories\DashboardRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(DashboardRepository::class);
    }

    // ===== HELPER =====

    private function seedData(): void
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Dashboard Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::query()->create([
            'name' => 'Dashboard Program',
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
            'registration_number' => 'SBN-DASH-' . strtoupper(substr(uniqid(), -6)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'validated',
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Disbursement::query()->create([
            'submission_id' => $submission->id,
            'program_id' => $program->id,
            'citizen_id' => $citizen->id,
            'amount' => 500000,
            'disbursed_at' => now()->toDateString(),
            'method' => 'bpd_transfer',
            'reference_number' => 'REF-DASH-001',
            'disbursed_by' => $admin->id,
        ]);
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_get_regency_stats_returns_data(): void
    {
        $this->seedData();

        $result = $this->repository->getRegencyStats('6301');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalPenerima', $result);
        $this->assertArrayHasKey('totalDanaTersalurkan', $result);
        $this->assertArrayHasKey('programAktif', $result);
        $this->assertArrayHasKey('antreanVerifikasi', $result);
    }

    public function test_get_district_distribution_returns_array(): void
    {
        $this->seedData();

        $result = $this->repository->getDistrictDistribution('6301', []);

        $this->assertIsArray($result);
    }

    public function test_get_monthly_trend_returns_array(): void
    {
        $this->seedData();

        $result = $this->repository->getMonthlyTrend('6301', []);

        $this->assertIsArray($result);
    }

    public function test_get_verification_status_returns_array(): void
    {
        $this->seedData();

        $result = $this->repository->getVerificationStatus('6301');

        $this->assertIsArray($result);
    }

    public function test_get_top_villages_returns_array(): void
    {
        $this->seedData();

        $result = $this->repository->getTopVillages('6301', []);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
    }

    // ===== DISTRICT (3 test) =====

    public function test_get_district_stats_returns_data(): void
    {
        $this->seedData();

        $result = $this->repository->getDistrictStats('6301010');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalPenerima', $result);
    }

    public function test_get_village_distribution_returns_array(): void
    {
        $this->seedData();

        $result = $this->repository->getVillageDistribution('6301010', []);

        $this->assertIsArray($result);
    }

    public function test_get_district_verification_status_returns_array(): void
    {
        $this->seedData();

        $result = $this->repository->getDistrictVerificationStatus('6301010');

        $this->assertIsArray($result);
    }

    // ===== VILLAGE (1 test) =====

    public function test_get_village_stats_returns_data(): void
    {
        $this->seedData();

        $result = $this->repository->getVillageStats('6301010001');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalWarga', $result);
    }

    // ===== SAD PATH (1 test) =====

    public function test_get_regency_stats_returns_zero_for_empty_data(): void
    {
        $result = $this->repository->getRegencyStats('9999');

        $this->assertEquals(0, $result['totalPenerima']);
        $this->assertEquals(0.0, $result['totalDanaTersalurkan']);
    }
}