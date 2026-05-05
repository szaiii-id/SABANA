<?php

namespace Tests\Integration\Repositories;

use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Repositories\AssistanceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistanceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AssistanceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AssistanceRepository();
    }

    public function test_find_active_submission_only_returns_pending_or_validated_status()
    {
        $citizen = Citizen::factory()->create();

        AssistanceSubmission::factory()->create(['citizen_id' => $citizen->id, 'status' => 'rejected']);
        
        $activeSubmission = AssistanceSubmission::factory()->create(['citizen_id' => $citizen->id, 'status' => 'validated']);

        $result = $this->repository->findActiveSubmission($citizen->id);

        $this->assertNotNull($result);
        $this->assertEquals($activeSubmission->id, $result->id);
        $this->assertEquals('validated', $result->status);
    }

    public function test_delete_by_registration_number_force_deletes_record()
    {
        $citizen = Citizen::factory()->create();
        $submission = AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'registration_number' => 'REG-DELETE-001'
        ]);

        $isDeleted = $this->repository->deleteByRegistrationNumber('REG-DELETE-001', $citizen->id);

        $this->assertTrue($isDeleted);
        $this->assertDatabaseMissing('assistance_submissions', ['id' => $submission->id]);
    }
}