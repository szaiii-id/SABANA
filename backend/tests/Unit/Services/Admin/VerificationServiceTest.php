<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Repositories\Contracts\VerificationRepositoryInterface;
use App\Services\Admin\SmartCalculationService;
use App\Services\Admin\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

final class VerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private VerificationRepositoryInterface $repository;
    private VerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->repository = Mockery::mock(VerificationRepositoryInterface::class);
        $smartService = app(SmartCalculationService::class);
        $this->service = new VerificationService($this->repository, $smartService);
    }

    // ===== HELPER =====

    private function createAdmin(): Admin
    {
        return Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Verifikator',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function createSubmission(string $status = 'pending'): AssistanceSubmission
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

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => $status,
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_get_list_delegates_to_repository(): void
    {
        $admin = $this->createAdmin();
        $paginator = new LengthAwarePaginator(collect(), 0, 15);

        $this->repository
            ->shouldReceive('getByWilayah')
            ->with($admin, [])
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($admin, []);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_get_detail_delegates_to_repository(): void
    {
        $submission = $this->createSubmission();

        $this->repository
            ->shouldReceive('findById')
            ->with($submission->id)
            ->once()
            ->andReturn($submission);

        $result = $this->service->getDetail($submission->id);

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
    }

    public function test_get_recommendation_returns_correct_label(): void
    {
        $result = $this->service->getRecommendation(0.85);

        $this->assertEquals('Sangat Direkomendasikan', $result['label']);
        $this->assertEquals('green', $result['color']);
    }

    // ===== SAD PATH (4 test) =====

    public function test_get_recommendation_null_score(): void
    {
        $result = $this->service->getRecommendation(null);

        $this->assertEquals('Belum Dinilai', $result['label']);
        $this->assertEquals('gray', $result['color']);
    }

    public function test_get_recommendation_not_recommended(): void
    {
        $result = $this->service->getRecommendation(0.10);

        $this->assertEquals('Tidak Direkomendasikan', $result['label']);
        $this->assertEquals('red', $result['color']);
    }

    public function test_get_detail_returns_null_for_unknown(): void
    {
        $this->repository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $result = $this->service->getDetail('nonexistent');

        $this->assertNull($result);
    }

    public function test_approve_throws_for_wrong_status(): void
    {
        $this->markTestSkipped('Method approve memanggil findByIdWithLock + transaksi — butuh integration test dengan DB asli.');
    }

    // ===== BOUNDARY (2 test) =====

    public function test_get_recommendation_boundary_recommended(): void
    {
        $result = $this->service->getRecommendation(0.50);

        $this->assertEquals('Direkomendasikan', $result['label']);
        $this->assertEquals('yellow', $result['color']);
    }

    public function test_get_recommendation_boundary_considered(): void
    {
        $result = $this->service->getRecommendation(0.30);

        $this->assertEquals('Dipertimbangkan', $result['label']);
        $this->assertEquals('orange', $result['color']);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_get_recommendation_with_custom_thresholds(): void
    {
        $customThresholds = [
            'highly_recommended' => 0.80,
            'recommended'        => 0.60,
            'considered'         => 0.40,
        ];

        $result = $this->service->getRecommendation(0.75, $customThresholds);

        $this->assertEquals('Direkomendasikan', $result['label']);
    }

    // ===== SECURITY (1 test) =====

    public function test_get_recommendation_does_not_throw_on_zero_score(): void
    {
        $result = $this->service->getRecommendation(0.0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('color', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}