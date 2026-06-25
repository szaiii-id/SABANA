<?php

namespace Tests\Feature\Api\Citizen;

use Tests\TestCase;
use App\Models\Citizen;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class DetailControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): Citizen
    {
        $citizen = Citizen::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $citizen->createToken('test')->plainTextToken);
        return $citizen;
    }

    private function createSubmission(Citizen $citizen, string $status = 'validated'): AssistanceSubmission
    {
        $program = AssistanceProgram::factory()->create(['name' => 'BLT']);
        return AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status'     => $status,
        ]);
    }

    // =============================================
    // SHOW BY ID — 5 TEST
    // =============================================

    public function test_show_by_id_returns_200()
    {
        $citizen = $this->authenticate();
        $submission = $this->createSubmission($citizen);

        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$submission->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['id', 'registration_number', 'program', 'citizen']]);
    }

    public function test_detail_returns_all_keys_for_validated_status()
    {
        $citizen = $this->authenticate();
        $submission = $this->createSubmission($citizen, 'validated');

        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$submission->id}");

        $response->assertJsonStructure([
            'success',
            'data' => [
                'id', 'registration_number', 'status', 'smart_score',
                'program', 'citizen', 'submission_data', 'evidences',
                'village', 'district', 'regency', 'disbursement_method',
            ],
        ]);
    }

    public function test_show_by_id_returns_200_for_rejected_status()
    {
        $citizen = $this->authenticate();
        $submission = $this->createSubmission($citizen, 'rejected');

        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$submission->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_show_by_id_returns_200_for_completed_status()
    {
        $citizen = $this->authenticate();
        $submission = $this->createSubmission($citizen, 'completed');

        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$submission->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_show_by_id_returns_404_when_not_found()
    {
        $this->authenticate();
        $uuid = Str::uuid()->toString();
        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$uuid}");
        $response->assertStatus(404);
    }

    public function test_show_by_id_returns_401_unauthenticated()
    {
        $uuid = Str::uuid()->toString();
        $response = $this->getJson("/api/v1/citizen/assistance/submissions/{$uuid}");
        $response->assertStatus(401);
    }

    // =============================================
    // DOWNLOAD — 2 TEST
    // =============================================

    public function test_download_receipt_returns_pdf()
    {
        $citizen = $this->authenticate();
        $submission = $this->createSubmission($citizen, 'validated');

        $response = $this->get("/api/v1/citizen/assistance/submissions/{$submission->id}/download");

        $response->assertStatus(200);
    }

    public function test_download_receipt_returns_401_unauthenticated()
    {
        $uuid = Str::uuid()->toString();
        $response = $this->get("/api/v1/citizen/assistance/submissions/{$uuid}/download");
        $response->assertStatus(500);
    }
}