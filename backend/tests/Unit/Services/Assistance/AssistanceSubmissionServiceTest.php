<?php

namespace Tests\Unit\Services\Assistance;

use Tests\TestCase;
use App\Models\Citizen;
use App\Models\AssistanceSubmission;
use App\Services\Assistance\AssistanceSubmissionService;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Contracts\Storage\FileStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('service')]
final class AssistanceSubmissionServiceTest extends TestCase
{
    private AssistanceSubmissionService $service;
    private $repositoryMock;
    private $storageMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(AssistanceRepositoryInterface::class);
        $this->storageMock = Mockery::mock(FileStorageInterface::class);

        $this->service = new AssistanceSubmissionService(
            $this->repositoryMock,
            $this->storageMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeCitizen(): Citizen
    {
        $citizen = new Citizen([
            'id'              => 'uuid-citizen-123',
            'nik'             => '6301234567890123',
            'full_name'       => 'AKHMAD WARGA',
            'whatsapp_number' => '081234567890',
        ]);
        $citizen->id = 'uuid-citizen-123';
        return $citizen;
    }

    private function makeSubmission(): AssistanceSubmission
    {
        $submission = new AssistanceSubmission([
            'id'                  => 'uuid-sub-123',
            'citizen_id'          => 'uuid-citizen-123',
            'program_id'          => 'uuid-prog-1',
            'registration_number' => 'SBN-ABC12345',
            'status'              => 'pending',
        ]);
        $submission->id = 'uuid-sub-123';
        $submission->registration_number = 'SBN-ABC12345';
        return $submission;
    }

    private function validPayload(): array
    {
        return [
            'program_id'          => 'uuid-prog-1',
            'regency_id'          => '6301',
            'district_id'         => '6301001',
            'village_id'          => '6301001001',
            'disbursement_method' => 'village_cash',
        ];
    }

    // ========================================================================
    // SUBMIT: SUCCESS (TANPA FILE)
    // ========================================================================

    #[Group('critical')]
    public function test_submit_creates_submission_without_files(): void
    {
        $citizen = $this->makeCitizen();
        $payload = $this->validPayload();
        $files = [];
        $submission = $this->makeSubmission();

        $this->repositoryMock
            ->shouldReceive('createSubmission')
            ->once()
            ->andReturn($submission);

        $result = $this->service->submit($citizen, $payload, $files);

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
        $this->assertEquals('pending', $result->status);
    }

    // ========================================================================
    // SUBMIT: SUCCESS (DENGAN FILE)
    // ========================================================================

    public function test_submit_creates_submission_with_files(): void
    {
        $citizen = $this->makeCitizen();
        $payload = $this->validPayload();
        $file = UploadedFile::fake()->image('ktp.jpg', 200, 200);
        $files = ['ktp' => $file];
        $submission = $this->makeSubmission();

        $this->repositoryMock
            ->shouldReceive('createSubmission')
            ->once()
            ->andReturn($submission);

        $this->storageMock
            ->shouldReceive('upload')
            ->once()
            ->andReturn([
                'url'       => 'https://cloudinary.com/ktp.jpg',
                'public_id' => 'cloud_public_123',
            ]);

        $this->repositoryMock
            ->shouldReceive('storeEvidence')
            ->once();

        $result = $this->service->submit($citizen, $payload, $files);

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
    }

    // ========================================================================
    // SUBMIT: WITH DYNAMIC DATA (JSONB)
    // ========================================================================

    public function test_submit_separates_static_and_dynamic_data(): void
    {
        $citizen = $this->makeCitizen();
        $payload = array_merge($this->validPayload(), [
            'school_name' => 'SMA Negeri 1',
            'nisn'        => '1234567890',
        ]);
        $files = [];
        $submission = $this->makeSubmission();

        $this->repositoryMock
            ->shouldReceive('createSubmission')
            ->once()
            ->with(Mockery::on(function ($data) {
                $dynamicData = $data['submission_data'];
                return isset($dynamicData['school_name'])
                    && !isset($dynamicData['program_id']);
            }))
            ->andReturn($submission);

        $result = $this->service->submit($citizen, $payload, $files);

        $this->assertInstanceOf(AssistanceSubmission::class, $result);
    }

    // ========================================================================
    // SUBMIT: FAILSAFE
    // ========================================================================

    public function test_submit_cleans_up_cloudinary_on_database_error(): void
    {
        $citizen = $this->makeCitizen();
        $payload = $this->validPayload();
        $file = UploadedFile::fake()->image('ktp.jpg');
        $files = ['ktp' => $file];

        $this->storageMock
            ->shouldReceive('upload')
            ->once()
            ->andReturn([
                'url'       => 'https://cloudinary.com/ktp.jpg',
                'public_id' => 'cloud_to_delete',
            ]);

        $this->repositoryMock
            ->shouldReceive('createSubmission')
            ->once()
            ->andReturn($this->makeSubmission());

        $this->repositoryMock
            ->shouldReceive('storeEvidence')
            ->once()
            ->andThrow(new \Exception('Database Error'));

        // Failsafe: hapus dari Cloudinary
        $this->storageMock
            ->shouldReceive('delete')
            ->once()
            ->with('cloud_to_delete')
            ->andReturn(true);

        $this->expectException(\Exception::class);

        $this->service->submit($citizen, $payload, $files);
    }

    // ========================================================================
    // CANCEL SUBMISSION
    // ========================================================================

    public function test_cancel_submission_deletes_from_db_and_cloudinary(): void
    {
        $submission = $this->makeSubmission();

        $evidence1 = new \stdClass();
        $evidence1->cloud_public_id = 'cloud_pub_1';
        $evidence2 = new \stdClass();
        $evidence2->cloud_public_id = 'cloud_pub_2';

        $submission->setRelation('evidences', collect([$evidence1, $evidence2]));

        $this->repositoryMock
            ->shouldReceive('findByRegistrationNumber')
            ->once()
            ->with('SBN-ABC12345')
            ->andReturn($submission);

        $this->repositoryMock
            ->shouldReceive('deleteByRegistrationNumber')
            ->once()
            ->andReturn(true);

        $this->storageMock
            ->shouldReceive('delete')
            ->times(2)
            ->andReturn(true);

        $result = $this->service->cancelSubmission('SBN-ABC12345', 'uuid-citizen-123');

        $this->assertTrue($result);
    }

    // ========================================================================
    // GET CITIZEN HISTORY
    // ========================================================================

    public function test_get_citizen_history_calls_repository(): void
    {
        $this->repositoryMock
            ->shouldReceive('getHistoryByCitizenId')
            ->once()
            ->with('uuid-citizen-123')
            ->andReturn(collect([]));

        $result = $this->service->getCitizenHistory('uuid-citizen-123');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    // ========================================================================
    // GET BY ID
    // ========================================================================

    public function test_get_by_id_returns_submission(): void
    {
        $submission = $this->makeSubmission();

        // Mock: findById return submission tanpa load() ke DB
        $submissionMock = Mockery::mock($submission);
        $submissionMock->shouldReceive('load')
            ->once()
            ->with(['program', 'evidences'])
            ->andReturn($submissionMock);

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('uuid-sub-123')
            ->andReturn($submissionMock);

        $result = $this->service->getById('uuid-sub-123');

        $this->assertNotNull($result);
    }
}