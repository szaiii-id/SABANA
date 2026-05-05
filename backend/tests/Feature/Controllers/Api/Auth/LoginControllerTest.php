<?php

namespace Tests\Feature\Controllers\Api\Auth;

use Tests\TestCase;
use App\Models\Citizen;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;

final class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    private AuthService|MockInterface $authServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $this->authServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // SUCCESS
    // ========================================================================

    public function test_login_returns_200_with_token_on_success(): void
    {
        $credentials = ['nik' => '6301234567890123', 'pin' => '123456'];

        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->with($credentials)
            ->andReturn([
                'user'  => new Citizen(['nik' => '6301234567890123', 'full_name' => 'John Doe']),
                'token' => 'test-token-abc',
            ]);

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Login successful',
            ])
            ->assertJsonPath('data.token', 'test-token-abc');
    }

    // ========================================================================
    // VALIDATION
    // ========================================================================

    public function test_login_returns_422_when_nik_is_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', ['pin' => '123456']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_login_returns_422_when_pin_is_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', ['nik' => '6301234567890123']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pin']);
    }

    public function test_login_returns_422_when_nik_not_16_digits(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nik' => '123',
            'pin' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_login_returns_422_when_pin_not_6_digits(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nik' => '6301234567890123',
            'pin' => '12',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pin']);
    }

    // ========================================================================
    // ERROR HANDLING
    // ========================================================================

    public function test_login_returns_422_when_credentials_invalid(): void
    {
        $credentials = ['nik' => '6301234567890123', 'pin' => '654321'];

        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->with($credentials)
            ->andThrow(ValidationException::withMessages([
                'nik' => ['NIK atau PIN yang Anda masukkan salah.'],
            ]));

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);
    }

    public function test_login_returns_422_when_account_unverified(): void
    {
        $credentials = ['nik' => '6301234567890123', 'pin' => '123456'];

        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->with($credentials)
            ->andThrow(ValidationException::withMessages([
                'is_verified'     => ['Akun belum aktif. Silakan verifikasi nomor WhatsApp Anda.'],
                'whatsapp_number' => '081234567890',
            ]));

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_verified', 'whatsapp_number']);
    }

    public function test_login_returns_500_on_unexpected_error(): void
    {
        $credentials = ['nik' => '6301234567890123', 'pin' => '123456'];

        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->andThrow(new \Exception('Database connection lost'));

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(500);
    }

    public function test_login_responds_under_200ms(): void
    {
        $credentials = ['nik' => '6301234567890123', 'pin' => '123456'];

        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->andReturn([
                'user'  => new Citizen(['nik' => '6301234567890123']),
                'token' => 'test-token',
            ]);

        $start = microtime(true);
        $this->postJson('/api/v1/auth/login', $credentials);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $duration, "Login too slow: {$duration}ms");
    }
}