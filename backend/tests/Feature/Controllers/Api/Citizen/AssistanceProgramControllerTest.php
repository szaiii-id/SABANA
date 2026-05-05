<?php

namespace Tests\Feature\Controllers\Api\Citizen;

use App\Models\AssistanceProgram;
use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistanceProgramControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_returns_active_programs()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();

        AssistanceProgram::factory()->count(2)->create(['is_active' => true]);
        AssistanceProgram::factory()->create(['is_active' => false, 'name' => 'Program Tutup']);

        $response = $this->actingAs($citizen, 'sanctum')
            ->getJson('/api/v1/citizen/assistance-categories'); // URL DISESUAIKAN

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(2, 'data')
                 ->assertJsonMissing(['name' => 'Program Tutup']); 
    }
}