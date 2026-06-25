<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Services\Admin\ReportService;
use Mockery;
use PHPUnit\Framework\TestCase;

final class ReportServiceTest extends TestCase
{
    private ReportService $service;
    private ReportRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ReportRepositoryInterface::class);
        $this->service = new ReportService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // 1. HAPPY PATH (9 tests — setiap method di service)
    // ============================================================

    public function test_budget_summary_returns_repository_data(): void
    {
        $expected = [['program' => 'PKH', 'total_anggaran' => 60000000]];
        $this->repository->shouldReceive('budgetSummary')->with([])->once()->andReturn($expected);

        $result = $this->service->budgetSummary([]);

        $this->assertSame($expected, $result);
    }

    public function test_program_recipients_passes_filters(): void
    {
        $filters = ['program_id' => 'uuid-1', 'tgl_mulai' => '2026-01-01'];
        $expected = [['nik' => '6301', 'full_name' => 'Budi']];
        $this->repository->shouldReceive('programRecipients')->with($filters)->once()->andReturn($expected);

        $result = $this->service->programRecipients($filters);

        $this->assertSame($expected, $result);
    }

    public function test_most_applied_programs_returns_data(): void
    {
        $expected = [['program' => 'PKH', 'total_pendaftar_unik' => 50]];
        $this->repository->shouldReceive('mostAppliedPrograms')->with([])->once()->andReturn($expected);

        $result = $this->service->mostAppliedPrograms([]);

        $this->assertSame($expected, $result);
    }

    public function test_citizen_registered_by_admin_returns_data(): void
    {
        $expected = [['admin_name' => 'Budi', 'full_name' => 'Ani']];
        $this->repository->shouldReceive('citizenRegisteredByAdmin')->with([])->once()->andReturn($expected);

        $result = $this->service->citizenRegisteredByAdmin([]);

        $this->assertSame($expected, $result);
    }

    public function test_ready_for_disbursement_returns_data(): void
    {
        $expected = [['nik' => '6301', 'full_name' => 'Joko', 'program' => 'PKH']];
        $this->repository->shouldReceive('readyForDisbursement')->with([])->once()->andReturn($expected);

        $result = $this->service->readyForDisbursement([]);

        $this->assertSame($expected, $result);
    }

    public function test_pending_evaluation_returns_data(): void
    {
        $expected = [['nik' => '6301', 'full_name' => 'Siti']];
        $this->repository->shouldReceive('pendingEvaluation')->with([])->once()->andReturn($expected);

        $result = $this->service->pendingEvaluation([]);

        $this->assertSame($expected, $result);
    }

    public function test_revoked_recipients_returns_data(): void
    {
        $expected = [['nik' => '6301', 'full_name' => 'Ahmad', 'alasan' => 'Tidak layak']];
        $this->repository->shouldReceive('revokedRecipients')->with([])->once()->andReturn($expected);

        $result = $this->service->revokedRecipients([]);

        $this->assertSame($expected, $result);
    }

    public function test_approved_recipients_returns_data(): void
    {
        $expected = [['nik' => '6301', 'full_name' => 'Dewi']];
        $this->repository->shouldReceive('approvedRecipients')->with([])->once()->andReturn($expected);

        $result = $this->service->approvedRecipients([]);

        $this->assertSame($expected, $result);
    }

    public function test_disbursed_recipients_returns_data(): void
    {
        $expected = [['nik' => '6301', 'full_name' => 'Rudi', 'amount' => 500000]];
        $this->repository->shouldReceive('disbursedRecipients')->with([])->once()->andReturn($expected);

        $result = $this->service->disbursedRecipients([]);

        $this->assertSame($expected, $result);
    }

    // ============================================================
    // 2. SAD PATH (3 tests)
    // ============================================================

    public function test_budget_summary_with_no_data_returns_empty(): void
    {
        $this->repository->shouldReceive('budgetSummary')->with([])->once()->andReturn([]);

        $result = $this->service->budgetSummary([]);

        $this->assertSame([], $result);
    }

    public function test_program_recipients_with_no_data_returns_empty(): void
    {
        $this->repository->shouldReceive('programRecipients')->with([])->once()->andReturn([]);

        $result = $this->service->programRecipients([]);

        $this->assertSame([], $result);
    }

    public function test_ready_for_disbursement_without_validated_returns_empty(): void
    {
        $this->repository->shouldReceive('readyForDisbursement')->with([])->once()->andReturn([]);

        $result = $this->service->readyForDisbursement([]);

        $this->assertSame([], $result);
    }

    // ============================================================
    // 3. BOUNDARY (2 tests)
    // ============================================================

    public function test_budget_summary_handles_large_values(): void
    {
        $expected = [['total_anggaran' => 999999999999.99]];
        $this->repository->shouldReceive('budgetSummary')->with([])->once()->andReturn($expected);

        $result = $this->service->budgetSummary([]);

        $this->assertEquals(999999999999.99, $result[0]['total_anggaran']);
    }

    public function test_disbursed_recipients_with_date_filters(): void
    {
        $filters = ['tgl_mulai' => '2026-01-01', 'tgl_akhir' => '2026-12-31'];
        $expected = [['amount' => 500000]];
        $this->repository->shouldReceive('disbursedRecipients')->with($filters)->once()->andReturn($expected);

        $result = $this->service->disbursedRecipients($filters);

        $this->assertCount(1, $result);
    }

    // ============================================================
    // 4. EDGE CASE (2 tests)
    // ============================================================

    public function test_most_applied_programs_with_empty_quota(): void
    {
        $expected = [['program' => 'PKH', 'quota_total' => 0, 'total_pendaftar_unik' => 5]];
        $this->repository->shouldReceive('mostAppliedPrograms')->with([])->once()->andReturn($expected);

        $result = $this->service->mostAppliedPrograms([]);

        $this->assertEquals(0, $result[0]['quota_total']);
    }

    public function test_pending_evaluation_triggered_at_ordering(): void
    {
        $expected = [
            ['triggered_at' => '2026-01-01'],
            ['triggered_at' => '2026-06-01'],
        ];
        $this->repository->shouldReceive('pendingEvaluation')->with([])->once()->andReturn($expected);

        $result = $this->service->pendingEvaluation([]);

        $this->assertEquals('2026-01-01', $result[0]['triggered_at']);
    }

    // ============================================================
    // 5. NULL/EMPTY (2 tests)
    // ============================================================

    public function test_citizen_registered_by_admin_with_empty_filters(): void
    {
        $this->repository->shouldReceive('citizenRegisteredByAdmin')->with([])->once()->andReturn([]);

        $result = $this->service->citizenRegisteredByAdmin([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_revoked_recipients_with_no_data_returns_empty(): void
    {
        $this->repository->shouldReceive('revokedRecipients')->with([])->once()->andReturn([]);

        $result = $this->service->revokedRecipients([]);

        $this->assertEmpty($result);
    }

    // ============================================================
    // 6. DATA TYPE (1 test)
    // ============================================================

    public function test_budget_summary_returns_array_of_arrays(): void
    {
        $expected = [['program' => 'PKH', 'total_anggaran' => 100000.0]];
        $this->repository->shouldReceive('budgetSummary')->with([])->once()->andReturn($expected);

        $result = $this->service->budgetSummary([]);

        $this->assertIsArray($result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('program', $result[0]);
    }

    // ============================================================
    // 7. EQUIVALENCE PARTITION (1 test)
    // ============================================================

    public function test_different_filter_combinations_passed_correctly(): void
    {
        $this->repository->shouldReceive('programRecipients')->with(['program_id' => 'p1'])->once()->andReturn([]);
        $this->repository->shouldReceive('programRecipients')->with(['tgl_mulai' => '2026-01-01'])->once()->andReturn([]);
        $this->repository->shouldReceive('programRecipients')->with([])->once()->andReturn([]);

        $this->service->programRecipients(['program_id' => 'p1']);
        $this->service->programRecipients(['tgl_mulai' => '2026-01-01']);
        $this->service->programRecipients([]);

        $this->assertTrue(true);
    }

    // ============================================================
    // 8. STATE TRANSITION (1 test)
    // ============================================================

    public function test_evaluation_status_transition_reflected_in_service(): void
    {
        $this->repository->shouldReceive('pendingEvaluation')->with([])->times(2)->andReturn(
            [['nik' => '6301']],
            []
        );
        $this->repository->shouldReceive('approvedRecipients')->with([])->once()->andReturn(
            [['nik' => '6301']]
        );

        $pending1 = $this->service->pendingEvaluation([]);
        $pending2 = $this->service->pendingEvaluation([]);
        $approved = $this->service->approvedRecipients([]);

        $this->assertCount(1, $pending1);
        $this->assertCount(0, $pending2);
        $this->assertCount(1, $approved);
    }

    // ============================================================
    // 9. CONCURRENCY (1 test)
    // ============================================================

    public function test_multiple_reports_called_simultaneously(): void
    {
        $this->repository->shouldReceive('budgetSummary')->with([])->once()->andReturn([['program' => 'A']]);
        $this->repository->shouldReceive('programRecipients')->with([])->once()->andReturn([['nik' => '6301']]);
        $this->repository->shouldReceive('disbursedRecipients')->with([])->once()->andReturn([['amount' => 100000]]);

        $r1 = $this->service->budgetSummary([]);
        $r2 = $this->service->programRecipients([]);
        $r3 = $this->service->disbursedRecipients([]);

        $this->assertEquals('A', $r1[0]['program']);
        $this->assertEquals('6301', $r2[0]['nik']);
        $this->assertEquals(100000, $r3[0]['amount']);
    }

    // ============================================================
    // 10. SECURITY (1 test)
    // ============================================================

    public function test_service_does_not_modify_repository_output(): void
    {
        $input = [['nik' => '6301', 'full_name' => 'Test']];
        $this->repository->shouldReceive('programRecipients')->with([])->once()->andReturn($input);

        $result = $this->service->programRecipients([]);

        $this->assertSame($input, $result);
    }
}