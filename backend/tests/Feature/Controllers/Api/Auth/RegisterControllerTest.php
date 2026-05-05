<?php

namespace Tests\Feature\Controllers\Api\Auth;

use Tests\TestCase;
use App\Models\Citizen;
use App\Services\CitizenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;

final class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    private CitizenService|MockInterface $citizenServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->citizenServiceMock = Mockery::mock(CitizenService::class);
        $this->app->instance(CitizenService::class, $this->citizenServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // SUCCESS
    // ========================================================================

    public function test_register_returns_201_on_success(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Akhmad Warga',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $fakeCitizen = new Citizen([
            'id'              => 'uuid-123',
            'nik'             => '6301234567890123',
            'full_name'       => 'Akhmad Warga',
            'whatsapp_number' => '081234567890',
        ]);

        $this->citizenServiceMock
            ->shouldReceive('registerCitizen')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['nik'] === '6301234567890123'
                    && $data['full_name'] === 'Akhmad Warga'
                    && $data['whatsapp_number'] === '081234567890';
            }))
            ->andReturn($fakeCitizen);

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Kode verifikasi telah dikirim melalui WhatsApp.',
            ]);
    }

    // ========================================================================
    // VALIDATION
    // ========================================================================

    public function test_register_returns_422_when_nik_not_kalteng(): void
    {
        $payload = [
            'nik'                => '3301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik'])
            ->assertJsonFragment([
                'NIK tidak valid. Pendaftaran SABANA khusus untuk KTP Kalimantan Selatan.',
            ]);
    }

    public function test_register_returns_422_when_pin_confirmation_mismatch(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '654321',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pin']);
    }

    public function test_register_returns_422_when_name_contains_numbers(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test123',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name']);
    }

    public function test_register_returns_422_when_whatsapp_too_short(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test',
            'whatsapp_number'    => '12345',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['whatsapp_number']);
    }

    public function test_register_returns_422_when_family_card_not_16_digits(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '123',
            'full_name'          => 'Test',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['family_card_number']);
    }

    public function test_register_returns_422_when_pin_not_6_digits(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test',
            'whatsapp_number'    => '081234567890',
            'pin'                => '12',
            'pin_confirmation'   => '12',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pin']);
    }

    // ========================================================================
    // SANITIZATION
    // ========================================================================

    public function test_register_sanitizes_nik_by_removing_non_digits(): void
    {
        $payload = [
            'nik'                => '6301-2345-6789-0123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test User',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $fakeCitizen = new Citizen(['nik' => '6301234567890123']);

        $this->citizenServiceMock
            ->shouldReceive('registerCitizen')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['nik'] === '6301234567890123';
            }))
            ->andReturn($fakeCitizen);

        $this->postJson('/api/v1/auth/register', $payload)->assertStatus(201);
    }

    public function test_register_sanitizes_full_name_by_stripping_tags(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => '<b>John Doe</b>',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $fakeCitizen = new Citizen(['full_name' => 'John Doe']);

        $this->citizenServiceMock
            ->shouldReceive('registerCitizen')
            ->once()
            ->with(\Mockery::on(function ($data) {
                return $data['full_name'] === 'John Doe';
            }))
            ->andReturn($fakeCitizen);

        $this->postJson('/api/v1/auth/register', $payload)->assertStatus(201);
    }

    // ========================================================================
    // DUPLICATE NIK
    // ========================================================================

    public function test_register_returns_422_when_nik_already_verified(): void
    {
        Citizen::factory()->create([
            'nik'         => '6301234567890123',
            'is_verified' => true,
        ]);

        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'New User',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nik'])
            ->assertJsonFragment([
                'NIK ini sudah terdaftar di sistem kami.',
            ]);
    }

    // ========================================================================
    // ERROR HANDLING
    // ========================================================================

    public function test_register_returns_422_when_service_throws_exception(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $this->citizenServiceMock
            ->shouldReceive('registerCitizen')
            ->once()
            ->andThrow(new \Exception('NIK ini sudah terdaftar dan aktif.'));

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status'  => 'error',
                'message' => 'NIK ini sudah terdaftar dan aktif.',
            ]);
    }
    public function test_register_responds_under_300ms(): void
    {
        $payload = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Test User',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];

        $this->citizenServiceMock
            ->shouldReceive('registerCitizen')
            ->once()
            ->andReturn(new Citizen(['nik' => '6301234567890123']));

        $start = microtime(true);
        $this->postJson('/api/v1/auth/register', $payload);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(300, $duration, "Register too slow: {$duration}ms");
    }
}