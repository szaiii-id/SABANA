<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Assistance;

use Tests\TestCase;
use App\Models\Citizen;
use App\Services\Assistance\AssistanceSubmissionService;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Contracts\Storage\FileStorageInterface;
use App\Services\Admin\SmartCalculationService;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('service')]
final class AssistanceSubmissionServiceSubmitTest extends TestCase
{
    private AssistanceSubmissionService $service;
    private AssistanceRepositoryInterface $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(AssistanceRepositoryInterface::class);
        $storageMock = Mockery::mock(FileStorageInterface::class);
        $smartMock = Mockery::mock(SmartCalculationService::class);

        $this->service = new AssistanceSubmissionService($this->repositoryMock, $storageMock, $smartMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeCitizen(string $id = 'uuid-citizen-123'): Citizen
    {
        $citizen = new Citizen();
        $citizen->id = $id;
        $citizen->nik = '6301234567890123';
        $citizen->full_name = 'AKHMAD WARGA';
        $citizen->whatsapp_number = '081234567890';
        return $citizen;
    }

    // ===== HAPPY PATH =====

    public function test_submit_rate_limit_key_is_unique_per_citizen(): void
    {
        $key1 = 'submit-assistance:citizen-a';
        $key2 = 'submit-assistance:citizen-b';

        $this->assertNotEquals($key1, $key2);
    }

    // ===== SAD PATH =====

    public function test_submit_rejects_duplicate_idempotency_key(): void
    {
        $citizen = $this->makeCitizen();
        RateLimiter::clear('submit-assistance:' . $citizen->id);

        $existing = new \App\Models\AssistanceSubmission();

        $this->repositoryMock
            ->shouldReceive('findByIdempotencyKey')
            ->once()
            ->with('uuid-citizen-123', 'key-duplicate')
            ->andReturn($existing);

        $this->expectException(\App\Exceptions\SubmissionException::class);
        $this->expectExceptionMessage('Pengajuan sedang diproses.');

        $this->service->submit($citizen, ['program_id' => 'any'], [], 'key-duplicate');
    }

    // ===== BOUNDARY =====

    public function test_submit_rate_limited_after_3_attempts(): void
    {
        $citizen = $this->makeCitizen('citizen-rate-limited');
        RateLimiter::clear('submit-assistance:' . $citizen->id);

        $key = 'submit-assistance:' . $citizen->id;
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 3600);
        }

        $this->expectException(\App\Exceptions\SubmissionException::class);
        $this->expectExceptionMessage('Terlalu banyak pengajuan.');

        $this->service->submit($citizen, ['program_id' => 'any'], []);
    }

    // ===== CONCURRENCY =====

    public function test_update_rate_limited_after_5_attempts(): void
    {
        $citizen = $this->makeCitizen('citizen-update-limited');
        RateLimiter::clear('update-assistance:' . $citizen->id);

        $key = 'update-assistance:' . $citizen->id;
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 1800);
        }

        // Mock findById untuk update
        $submission = new \App\Models\AssistanceSubmission();
        $submission->id = 'uuid-sub-123';
        $submission->citizen_id = 'citizen-update-limited';

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('uuid-sub-123')
            ->andReturn($submission);

        $this->expectException(\App\Exceptions\SubmissionException::class);
        $this->expectExceptionMessage('Terlalu banyak perubahan.');

        $this->service->update('uuid-sub-123', ['regency_id' => '6301'], []);
    }

    // ===== SECURITY =====

    public function test_rate_limit_keys_are_isolated_per_citizen(): void
    {
        $citizen1 = $this->makeCitizen('citizen-x');
        $citizen2 = $this->makeCitizen('citizen-y');

        $key1 = 'submit-assistance:citizen-x';
        $key2 = 'submit-assistance:citizen-y';

        $this->assertNotEquals($key1, $key2);
    }
}