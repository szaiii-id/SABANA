<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Citizen;

use Tests\TestCase;
use App\Models\Citizen;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;

#[Group('feature')]
#[Group('controller')]
final class AssistanceControllerSubmitTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/citizen/assistance/submit';

    private function authenticate(): Citizen
    {
        $citizen = Citizen::factory()->create();
        $this->withHeader('Authorization', 'Bearer ' . $citizen->createToken('test')->plainTextToken);
        RateLimiter::clear('submit-assistance:' . $citizen->id);

        return $citizen;
    }

    // ===== HAPPY PATH =====

    public function test_store_returns_201_with_valid_data(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create(['status' => 'active']);

        $response = $this->postJson($this->endpoint, [
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['registration_number', 'status']]);
    }

    // ===== SAD PATH =====

    public function test_store_returns_422_when_program_id_missing(): void
    {
        $this->authenticate();

        $response = $this->postJson($this->endpoint, [
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['program_id']);
    }

    public function test_store_returns_422_when_regency_invalid(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        $response = $this->postJson($this->endpoint, [
            'program_id'          => $program->id,
            'regency_id'          => '63',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['regency_id']);
    }

    public function test_store_returns_422_when_district_invalid(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        $response = $this->postJson($this->endpoint, [
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['district_id']);
    }

    public function test_store_returns_422_when_village_invalid(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        $response = $this->postJson($this->endpoint, [
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301',
            'disbursement_method' => 'village_cash',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['village_id']);
    }

    public function test_store_returns_422_when_disbursement_method_invalid(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        $response = $this->postJson($this->endpoint, [
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'invalid_method',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['disbursement_method']);
    }

    // ===== BOUNDARY =====

    public function test_store_returns_422_when_duplicate_submission(): void
    {
        $citizen = $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status'     => AssistanceSubmission::STATUS_PENDING,
        ]);

        $response = $this->postJson($this->endpoint, [
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ]);

        $response->assertStatus(422);
    }

    // ===== EDGE CASE =====

    public function test_store_requires_bank_account_for_bpd_transfer(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create();

        $response = $this->postJson($this->endpoint, [
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'bpd_transfer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account_number']);
    }

    // ===== SECURITY =====

    public function test_store_returns_401_unauthenticated(): void
    {
        $response = $this->postJson($this->endpoint, []);
        $response->assertStatus(401);
    }
}