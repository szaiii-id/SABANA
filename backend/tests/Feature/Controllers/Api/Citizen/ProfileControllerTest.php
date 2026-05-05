<?php

namespace Tests\Feature\Controllers\Api\Citizen;

use App\Models\Citizen;
use App\Services\CitizenProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private CitizenProfileService|MockInterface $profileServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileServiceMock = Mockery::mock(CitizenProfileService::class);
        $this->app->instance(CitizenProfileService::class, $this->profileServiceMock);
    }

    public function test_update_profile_returns_200_and_calls_service()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();
        $payload = ['full_name' => 'Nama Baru', 'whatsapp_number' => '081234567890'];

        $this->profileServiceMock
            ->shouldReceive('updateProfile')
            ->once()
            ->with(Mockery::on(fn($c) => $c->id === $citizen->id), $payload)
            ->andReturn(true);

        $response = $this->actingAs($citizen, 'sanctum')
            ->putJson('/api/v1/citizen/profile', $payload); // URL DISESUAIKAN

        $response->assertStatus(200)->assertJson(['message' => 'Data diri berhasil diperbarui.']);
    }

    public function test_update_profile_returns_500_on_system_error()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();
        $payload = ['full_name' => 'Test', 'whatsapp_number' => '081122334455'];

        $this->profileServiceMock
            ->shouldReceive('updateProfile')
            ->once()
            ->andThrow(new \Exception('Database down'));

        $response = $this->actingAs($citizen, 'sanctum')
            ->putJson('/api/v1/citizen/profile', $payload); // URL DISESUAIKAN

        $response->assertStatus(500)->assertJson(['message' => 'Terjadi kesalahan pada sistem.']);
    }
}