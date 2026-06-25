<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Assistance;

use Tests\TestCase;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Services\Assistance\AssistanceSubmissionService;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Contracts\Storage\FileStorageInterface;
use App\Services\Admin\SmartCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;

final class GetCitizenHistoryTest extends TestCase
{
    use RefreshDatabase;

    private AssistanceSubmissionService $service;
    private AssistanceRepositoryInterface $repository;
    private FileStorageInterface $storage;
    private SmartCalculationService $smartService;
    private Citizen $citizen;
    private AssistanceProgram $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = Mockery::mock(FileStorageInterface::class);
        $this->smartService = Mockery::mock(SmartCalculationService::class);
        $this->repository = $this->createMock(AssistanceRepositoryInterface::class);
        $this->service = new AssistanceSubmissionService($this->repository, $this->storage, $this->smartService);

        $this->citizen = new Citizen();
        $this->citizen->nik = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        $this->citizen->family_card_number = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        $this->citizen->full_name = 'Test Citizen';
        $this->citizen->whatsapp_number = '6281234567890';
        $this->citizen->pin = bcrypt('123456');
        $this->citizen->save();

        $this->program = new AssistanceProgram();
        $this->program->name = 'BLT Test';
        $this->program->description = 'Program test';
        $this->program->status = 'active';
        $this->program->save();
    }

    private function createSubmission(string $status = 'pending'): AssistanceSubmission
    {
        $submission = new AssistanceSubmission();
        $submission->citizen_id = $this->citizen->id;
        $submission->program_id = $this->program->id;
        $submission->status = $status;
        $submission->registration_number = 'REG-' . uniqid();
        $submission->submission_data = json_encode(['pekerjaan' => 'Petani']);
        $submission->disbursement_method = 'village_cash';
        $submission->created_at = match ($status) {
            'pending'              => now()->subDays(10),
            'validated'            => now()->subDays(5),
            'completed'            => now()->subDays(3),
            'evaluation_pending'   => now()->subDay(),
            'rejected'             => now()->subDay(),
            default                => now(),
        };
        $submission->save();

        return $submission;
    }

    // ===== PILAR 1: SINGLE PROGRAM — SINGLE SUBMISSION =====

    public function test_history_single_submission_returns_one_item_with_timeline(): void
    {
        $sub = $this->createSubmission('pending');

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $item = $result->items()[0];
        $this->assertEquals($this->program->id, $item['program_id']);
        $this->assertEquals('pending', $item['current_status']);
        $this->assertEquals('Diajukan', $item['current_status_label']);
        $this->assertFalse($item['is_evaluation']);
        $this->assertCount(1, $item['timeline']);
    }

    // ===== PILAR 2: SINGLE PROGRAM — MULTIPLE SUBMISSIONS (GROUPED) =====

    public function test_history_groups_multiple_submissions_same_program(): void
    {
        $sub1 = $this->createSubmission('pending');
        $sub2 = AssistanceSubmission::create([
            'citizen_id'          => $this->citizen->id,
            'program_id'          => $this->program->id,
            'status'              => 'validated',
            'registration_number' => 'REG-VALID-' . uniqid(),
            'submission_data'     => json_encode([]),
            'disbursement_method' => 'village_cash',
            'created_at'          => now()->subDays(2),
        ]);

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $this->assertEquals(1, $result->total()); // Grouped jadi 1
        $item = $result->items()[0];
        $this->assertCount(2, $item['timeline']); // pending + validated
    }

    // ===== PILAR 3: EVALUATION PENDING =====

    public function test_history_detects_evaluation_pending(): void
    {
        $sub = $this->createSubmission('evaluation_pending');

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $item = $result->items()[0];
        $this->assertTrue($item['is_evaluation']);
        $this->assertEquals('Evaluasi 6 Bulan', $item['current_status_label']);
    }

    // ===== PILAR 4: EVALUATION APPROVED — TIMELINE EXTENDED =====

    public function test_history_evaluation_approved_adds_timeline_node(): void
    {
        $sub1 = $this->createSubmission('evaluation_pending');
        $sub2 = AssistanceSubmission::create([
            'citizen_id'          => $this->citizen->id,
            'program_id'          => $this->program->id,
            'status'              => 'validated',
            'registration_number' => 'REG-EVAL-' . uniqid(),
            'submission_data'     => json_encode([]),
            'disbursement_method' => 'village_cash',
            'created_at'          => now(),
        ]);

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $item = $result->items()[0];
        // Timeline: evaluation_pending + validated + evaluation_approved
        $lastTimeline = end($item['timeline']);
        $this->assertEquals('evaluation_approved', $lastTimeline['status']);
        $this->assertEquals('Dilanjutkan', $lastTimeline['label']);
    }

    // ===== PILAR 5: EVALUATION REVOKED — TIMELINE EXTENDED =====

    public function test_history_evaluation_revoked_adds_timeline_node(): void
    {
        $sub1 = $this->createSubmission('evaluation_pending');
        $sub2 = AssistanceSubmission::create([
            'citizen_id'          => $this->citizen->id,
            'program_id'          => $this->program->id,
            'status'              => 'rejected',
            'registration_number' => 'REG-REVOKED-' . uniqid(),
            'submission_data'     => json_encode([]),
            'disbursement_method' => 'village_cash',
            'created_at'          => now(),
        ]);

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $item = $result->items()[0];
        $lastTimeline = end($item['timeline']);
        $this->assertEquals('evaluation_revoked', $lastTimeline['status']);
        $this->assertEquals('Dihentikan', $lastTimeline['label']);
    }

    // ===== PILAR 6: MULTIPLE PROGRAMS — TIDAK DIGABUNG =====

    public function test_history_separates_different_programs(): void
    {
        $this->createSubmission('pending');

        $program2 = new AssistanceProgram();
        $program2->name = 'Beasiswa';
        $program2->description = 'Program beasiswa';
        $program2->status = 'active';
        $program2->save();

        AssistanceSubmission::create([
            'citizen_id'          => $this->citizen->id,
            'program_id'          => $program2->id,
            'status'              => 'pending',
            'registration_number' => 'REG-PROG2-' . uniqid(),
            'submission_data'     => json_encode([]),
            'disbursement_method' => 'village_cash',
        ]);

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $this->assertEquals(2, $result->total());
    }

    // ===== PILAR 7: EMPTY HISTORY =====

    public function test_history_returns_empty_when_no_submissions(): void
    {
        $result = $this->service->getCitizenHistory($this->citizen->id);

        $this->assertEquals(0, $result->total());
    }

    // ===== PILAR 8: TIMELINE ORDER =====

    public function test_timeline_is_ordered_by_date(): void
    {
        $sub1 = $this->createSubmission('pending');
        $sub2 = AssistanceSubmission::create([
            'citizen_id'          => $this->citizen->id,
            'program_id'          => $this->program->id,
            'status'              => 'completed',
            'registration_number' => 'REG-COMP-' . uniqid(),
            'submission_data'     => json_encode([]),
            'disbursement_method' => 'village_cash',
            'created_at'          => now(),
        ]);

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $item = $result->items()[0];
        $timeline = $item['timeline'];
        $this->assertEquals('pending', $timeline[0]['status']);
        $this->assertEquals('completed', $timeline[1]['status']);
    }

    // ===== PILAR 9: PAGINATION =====

    public function test_history_respects_pagination(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $program = new AssistanceProgram();
            $program->name = 'Program ' . $i;
            $program->description = 'Test';
            $program->status = 'active';
            $program->save();

            AssistanceSubmission::create([
                'citizen_id'          => $this->citizen->id,
                'program_id'          => $program->id,
                'status'              => 'pending',
                'registration_number' => 'REG-PG-' . uniqid(),
                'submission_data'     => json_encode([]),
                'disbursement_method' => 'village_cash',
            ]);
        }

        $result = $this->service->getCitizenHistory($this->citizen->id, 3);

        $this->assertCount(3, $result->items());
        $this->assertEquals(5, $result->total());
    }

    // ===== PILAR 10: TIMELINE LABEL & COLOR =====

    public function test_timeline_labels_are_correct(): void
    {
        $this->createSubmission('needs_revision');

        $result = $this->service->getCitizenHistory($this->citizen->id);

        $item = $result->items()[0];
        $timeline = $item['timeline'][0];
        $this->assertEquals('Perlu Revisi', $timeline['label']);
        $this->assertEquals('yellow', $timeline['color']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}