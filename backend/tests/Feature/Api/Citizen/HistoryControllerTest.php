<?php

namespace Tests\Feature\Api\Citizen;

use Tests\TestCase;
use App\Models\Citizen;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/citizen/assistance/submissions';

    private function authenticate(): Citizen
    {
        $citizen = Citizen::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $citizen->createToken('test')->plainTextToken);
        return $citizen;
    }

    // =============================================
    // SUCCESS
    // =============================================

    public function test_history_returns_200_with_data()
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->count(2)->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_history_returns_200_with_meta_pagination()
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->count(5)->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
        ]);

        $response = $this->getJson($this->endpoint . '?per_page=2');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_history_returns_empty_for_new_citizen()
    {
        $this->authenticate();

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(0, 'data');
    }

    // =============================================
    // AUTH
    // =============================================

    public function test_history_returns_401_unauthenticated()
    {
        $response = $this->getJson($this->endpoint);
        $response->assertStatus(401);
    }

    // =============================================
    // BOUNDARY
    // =============================================

    public function test_history_per_page_minimum_1()
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->count(3)->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
        ]);

        $response = $this->getJson($this->endpoint . '?per_page=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =============================================
    // STATUS FILTERING
    // =============================================

    public function test_history_returns_needs_revision_status()
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status'     => 'needs_revision',
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.status', 'needs_revision')
            ->assertJsonPath('data.0.is_evaluation', false);
    }

    public function test_history_returns_evaluation_pending_status()
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status'     => 'evaluation_pending',
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.status', 'evaluation_pending')
            ->assertJsonPath('data.0.is_evaluation', true);
    }

    public function test_history_returns_validated_status()
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status'     => 'validated',
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.status', 'validated');
    }

    public function test_history_returns_rejected_status()
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status'     => 'rejected',
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.status', 'rejected');
    }
}