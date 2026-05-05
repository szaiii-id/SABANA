<?php

namespace Tests\Feature\Controllers\Api\Citizen;

use App\Models\Citizen;
use App\Services\CitizenAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    private CitizenAuthService|MockInterface $authServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authServiceMock = Mockery::mock(CitizenAuthService::class);
        $this->app->instance(CitizenAuthService::class, $this->authServiceMock);
    }

    public function test_update_pin_returns_200_and_success_message()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();
        $payload = [
            'current_pin' => '123456',
            'new_pin' => '654321',
            'new_pin_confirmation' => '654321'
        ];

        $this->authServiceMock
            ->shouldReceive('updatePin')
            ->once()
            ->with(Mockery::on(fn($c) => $c->id === $citizen->id), '123456', '654321')
            ->andReturn(true);

        $response = $this->actingAs($citizen, 'sanctum')
            ->putJson('/api/v1/citizen/security/pin', $payload); // URL DISESUAIKAN

        $response->assertStatus(200)
                 ->assertJson(['message' => 'PIN berhasil diperbarui.']);
    }

    public function test_update_pin_returns_400_when_service_throws_exception()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();
        $payload = ['current_pin' => '111111', 'new_pin' => '222222', 'new_pin_confirmation' => '222222'];

        $this->authServiceMock
            ->shouldReceive('updatePin')
            ->once()
            ->andThrow(new \Exception('PIN saat ini tidak valid.'));

        $response = $this->actingAs($citizen, 'sanctum')
            ->putJson('/api/v1/citizen/security/pin', $payload); // URL DISESUAIKAN

        $response->assertStatus(400)
                 ->assertJson(['message' => 'PIN saat ini tidak valid.']);
    }

    public function test_update_pin_returns_422_on_validation_failure()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();
        $payload = ['current_pin' => '123', 'new_pin' => '654321', 'new_pin_confirmation' => '000000'];

        $response = $this->actingAs($citizen, 'sanctum')
            ->putJson('/api/v1/citizen/security/pin', $payload); // URL DISESUAIKAN

        $this->authServiceMock->shouldNotReceive('updatePin');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['current_pin', 'new_pin']);
    }
}