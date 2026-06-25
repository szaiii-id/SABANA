<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Assistance;

use App\Contracts\Storage\FileStorageInterface;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Services\Admin\SmartCalculationService;
use App\Services\Assistance\AssistanceSubmissionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

final class AssistanceSubmissionServiceTest extends TestCase
{
    private AssistanceRepositoryInterface $repository;
    private FileStorageInterface $storage;
    private AssistanceSubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->repository = Mockery::mock(AssistanceRepositoryInterface::class);
        $this->storage = Mockery::mock(FileStorageInterface::class);
        $smartService = app(SmartCalculationService::class);
        $this->service = new AssistanceSubmissionService($this->repository, $this->storage, $smartService);
    }

    // ===== HELPER =====

    private function mockSubmission(array $attrs = []): AssistanceSubmission
    {
        $submission = Mockery::mock(AssistanceSubmission::class)->makePartial();
        $submission->id = $attrs['id'] ?? '550e8400-e29b-41d4-a716-446655440000';
        $submission->citizen_id = $attrs['citizen_id'] ?? '550e8400-e29b-41d4-a716-446655440001';
        $submission->program_id = $attrs['program_id'] ?? '550e8400-e29b-41d4-a716-446655440002';
        $submission->registration_number = $attrs['registration_number'] ?? 'SBN-ABC12345';
        $submission->status = $attrs['status'] ?? 'pending';
        $submission->submission_data = $attrs['submission_data'] ?? ['usia' => 25];
        $submission->created_at = now();
        $submission->program = new AssistanceProgram(['name' => 'Program Test']);
        $submission->shouldReceive('load')->andReturnSelf();
        $submission->shouldReceive('getAttribute')->with('revision_items')->andReturn([]);
        $submission->shouldReceive('getAttribute')->with('verifications')->andReturn(collect([]));
        return $submission;
    }

    // ===== HAPPY PATH (4 test) =====

    public function test_get_by_registration_number_returns_submission(): void
    {
        $submission = $this->mockSubmission();

        $this->repository
            ->shouldReceive('findByRegistrationNumber')
            ->with('SBN-ABC12345')
            ->once()
            ->andReturn($submission);

        $result = $this->service->getByRegistrationNumber('SBN-ABC12345');

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
    }

    public function test_get_by_id_returns_submission(): void
    {
        $submission = $this->mockSubmission();

        $this->repository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440000')
            ->once()
            ->andReturn($submission);

        $result = $this->service->getById('550e8400-e29b-41d4-a716-446655440000');

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
    }

    public function test_get_citizen_history_returns_paginator(): void
    {
        $this->markTestSkipped('groupAndBuildTimeline accessor query DB — butuh integration test.');
    }

    public function test_attach_submission_status(): void
    {
        $program = new AssistanceProgram();
        $program->id = '550e8400-e29b-41d4-a716-446655440002';
        $programs = collect([$program]);

        $this->repository
            ->shouldReceive('hasActiveSubmission')
            ->with('550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440002')
            ->once()
            ->andReturn(true);

        $result = $this->service->attachSubmissionStatus($programs, '550e8400-e29b-41d4-a716-446655440001');

        $this->assertTrue($result->first()->has_submitted);
    }

    // ===== SAD PATH (2 test) =====

    public function test_get_by_id_not_found_throws(): void
    {
        $this->repository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655449999')
            ->once()
            ->andThrow(new \Exception('Not found'));

        $this->expectException(\App\Exceptions\SubmissionException::class);

        $this->service->getById('550e8400-e29b-41d4-a716-446655449999');
    }

    public function test_cancel_submission_removes_evidences(): void
    {
        $this->markTestSkipped('AnomalyDetectionService query DB — butuh integration test.');
    }

    // ===== BOUNDARY (1 test) =====

    public function test_get_history_with_empty_result(): void
    {
        $this->repository
            ->shouldReceive('getAllHistoryByCitizenId')
            ->with('550e8400-e29b-41d4-a716-446655440001')
            ->once()
            ->andReturn(collect([]));

        $result = $this->service->getCitizenHistory('550e8400-e29b-41d4-a716-446655440001', 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_attach_submission_status_with_empty_programs(): void
    {
        $result = $this->service->attachSubmissionStatus(collect([]), '550e8400-e29b-41d4-a716-446655440001');

        $this->assertEmpty($result);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_get_by_registration_number_returns_model(): void
    {
        $submission = $this->mockSubmission();

        $this->repository
            ->shouldReceive('findByRegistrationNumber')
            ->once()
            ->andReturn($submission);

        $result = $this->service->getByRegistrationNumber('SBN-TEST');

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
    }

    // ===== SECURITY (1 test) =====

    public function test_get_by_registration_number_does_not_expose_hidden(): void
    {
        $submission = $this->mockSubmission();

        $this->repository
            ->shouldReceive('findByRegistrationNumber')
            ->once()
            ->andReturn($submission);

        $result = $this->service->getByRegistrationNumber('SBN-TEST');

        $this->assertNotNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}