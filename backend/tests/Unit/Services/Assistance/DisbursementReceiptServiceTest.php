<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Assistance;

use App\Models\AssistanceSubmission;
use App\Repositories\Contracts\DisbursementReceiptRepositoryInterface;
use App\Services\Assistance\DisbursementReceiptService;
use Mockery;
use Tests\TestCase;

final class DisbursementReceiptServiceTest extends TestCase
{
    private DisbursementReceiptRepositoryInterface $repository;
    private DisbursementReceiptService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(DisbursementReceiptRepositoryInterface::class);
        $this->service = new DisbursementReceiptService($this->repository);
    }

    // ===== HELPER =====

    private function mockSubmission(bool $withDisbursement = true): AssistanceSubmission
    {
        $submission = Mockery::mock(AssistanceSubmission::class)->makePartial();
        $submission->id = '550e8400-e29b-41d4-a716-446655440000';
        $submission->registration_number = 'SBN-RCPT001';
        $submission->citizen_id = 'citizen-uuid';

        if ($withDisbursement) {
            $disbursement = new \App\Models\Disbursement();
            $disbursement->id = 'disb-uuid';
            $disbursement->amount = '500000.00';
            $disbursement->reference_number = 'REF-001';
            $submission->setRelation('disbursement', $disbursement);
        } else {
            $submission->setRelation('disbursement', null);
        }

        return $submission;
    }

    // ===== HAPPY PATH (2 test) =====

    public function test_get_receipt_returns_submission(): void
    {
        $submission = $this->mockSubmission();

        $this->repository
            ->shouldReceive('findBySubmissionId')
            ->with('550e8400-e29b-41d4-a716-446655440000', 'citizen-uuid')
            ->once()
            ->andReturn($submission);

        $result = $this->service->getReceipt('550e8400-e29b-41d4-a716-446655440000', 'citizen-uuid');

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
    }

    public function test_generate_pdf_returns_pdf_and_registration(): void
    {
        $this->markTestSkipped('generatePdf() pakai Pdf::loadView — butuh Laravel view + DomPDF. Gunakan integration test.');
    }

    // ===== SAD PATH (2 test) =====

    public function test_get_receipt_throws_for_not_found(): void
    {
        $this->repository
            ->shouldReceive('findBySubmissionId')
            ->with('nonexistent', 'citizen-uuid')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pengajuan tidak ditemukan.');

        $this->service->getReceipt('nonexistent', 'citizen-uuid');
    }

    public function test_get_receipt_throws_for_no_disbursement(): void
    {
        $submission = $this->mockSubmission(false);

        $this->repository
            ->shouldReceive('findBySubmissionId')
            ->once()
            ->andReturn($submission);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bantuan belum disalurkan.');

        $this->service->getReceipt('id', 'citizen-uuid');
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_receipt_with_valid_data(): void
    {
        $submission = $this->mockSubmission();

        $this->repository
            ->shouldReceive('findBySubmissionId')
            ->once()
            ->andReturn($submission);

        $result = $this->service->getReceipt('id', 'citizen-uuid');

        $this->assertNotNull($result);
        $this->assertEquals('SBN-RCPT001', $result->registration_number);
    }

    // ===== SECURITY (1 test) =====

    public function test_get_receipt_only_for_own_submission(): void
    {
        $submission = $this->mockSubmission();

        $this->repository
            ->shouldReceive('findBySubmissionId')
            ->with('submission-id', 'correct-citizen')
            ->once()
            ->andReturn($submission);

        $result = $this->service->getReceipt('submission-id', 'correct-citizen');

        $this->assertNotNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}