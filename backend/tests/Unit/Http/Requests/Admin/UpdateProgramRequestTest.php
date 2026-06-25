<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\AssistanceProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

final class UpdateProgramRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/sabana-center-63/programs';

    private function authenticate(): void
    {
        $admin = new Admin();
        $admin->id = Str::uuid()->toString();
        $admin->nip = '199001012020011001';
        $admin->name = 'Super Admin';
        $admin->password = bcrypt('password');
        $admin->role = 'super_admin';
        $admin->is_active = true;
        $admin->save();

        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('test')->plainTextToken);
    }

    // ===== NAME =====

    public function test_name_min_3(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", ['name' => 'AB']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_valid_passes(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", ['name' => 'BLT Updated']);

        $response->assertStatus(200);
    }

    // ===== END DATE =====

    public function test_end_date_must_be_after_start(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
            'start_date' => '2026-06-01',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", [
            'end_date' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_end_date_after_start_passes(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", [
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
        ]);

        $response->assertStatus(200);
    }

    public function test_end_date_uses_existing_start_date_from_db(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
            'start_date' => '2026-06-01',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", [
            'end_date' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    // ===== STATUS =====

    public function test_status_draft_passes(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'active',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", ['status' => 'draft']);

        $response->assertStatus(200);
    }

    public function test_status_completed_passes(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", ['status' => 'completed']);

        $response->assertStatus(200);
    }

    public function test_status_invalid_fails(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", ['status' => 'invalid']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ===== QUOTA =====

    public function test_quota_total_min_1(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", ['quota_total' => 0]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quota_total']);
    }

    // ===== BENEFIT =====

    public function test_benefit_amount_min_0(): void
    {
        $this->authenticate();
        $program = AssistanceProgram::factory()->create([
            'name' => 'Test', 'description' => 'Description here', 'status' => 'draft',
        ]);

        $response = $this->putJson("{$this->endpoint}/{$program->id}", ['benefit_amount' => -500]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['benefit_amount']);
    }
}