<?php

namespace Tests\Unit\Http\Resources;

use Tests\TestCase;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Http\Resources\SubmissionResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubmissionResourceTest extends TestCase
{
    use RefreshDatabase;

    private function createSubmission(string $status = 'pending'): AssistanceSubmission
    {
        $citizen = Citizen::factory()->create();
        $program = AssistanceProgram::factory()->create(['name' => 'BLT Dana Desa']);

        return AssistanceSubmission::factory()->create([
            'citizen_id'          => $citizen->id,
            'program_id'          => $program->id,
            'registration_number' => 'SBN-ABC12345',
            'status'              => $status,
        ]);
    }

    // =============================================
    // RESOURCE KEYS
    // =============================================

    public function test_resource_has_all_required_keys()
    {
        $submission = $this->createSubmission();
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('registration_number', $response);
        $this->assertArrayHasKey('program', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('is_evaluation', $response);
        $this->assertArrayHasKey('revision_items', $response);
        $this->assertArrayHasKey('admin_note', $response);
        $this->assertArrayHasKey('submitted_at', $response);
        $this->assertArrayHasKey('location', $response);
    }

    public function test_resource_returns_program_data()
    {
        $submission = $this->createSubmission();
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertArrayHasKey('id', $response['program']);
        $this->assertArrayHasKey('name', $response['program']);
    }

    // =============================================
    // STATUS
    // =============================================

    public function test_resource_returns_pending_status()
    {
        $submission = $this->createSubmission('pending');
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertEquals('pending', $response['status']);
        $this->assertFalse($response['is_evaluation']);
    }

    public function test_resource_returns_evaluation_pending_status()
    {
        $submission = $this->createSubmission('evaluation_pending');
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertEquals('evaluation_pending', $response['status']);
        $this->assertTrue($response['is_evaluation']);
    }

    public function test_resource_returns_validated_status()
    {
        $submission = $this->createSubmission('validated');
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertEquals('validated', $response['status']);
    }

    public function test_resource_returns_rejected_status()
    {
        $submission = $this->createSubmission('rejected');
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertEquals('rejected', $response['status']);
    }

    public function test_resource_returns_needs_revision_status()
    {
        $submission = $this->createSubmission('needs_revision');
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertEquals('needs_revision', $response['status']);
    }

    public function test_resource_returns_completed_status()
    {
        $submission = $this->createSubmission('completed');
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertEquals('completed', $response['status']);
    }

    // =============================================
    // LOCATION
    // =============================================

    public function test_resource_has_location_keys()
    {
        $submission = $this->createSubmission();
        $resource = new SubmissionResource($submission);
        $response = $resource->toArray(request());

        $this->assertArrayHasKey('location', $response);
        $this->assertArrayHasKey('village', $response['location']);
        $this->assertArrayHasKey('district', $response['location']);
        $this->assertArrayHasKey('regency', $response['location']);
        $this->assertArrayHasKey('province', $response['location']);
    }
}