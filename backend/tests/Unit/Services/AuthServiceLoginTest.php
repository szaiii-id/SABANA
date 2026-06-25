<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Citizen;
use App\Services\AuthService;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthServiceLoginTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = app(CitizenRepositoryInterface::class);
        $this->service = new AuthService($repository);
    }

    private function createVerifiedCitizen(string $pin = '123456'): Citizen
    {
        return Citizen::factory()->create([
            'nik'         => '6301234567890123',
            'pin'         => Hash::make($pin),
            'is_verified' => true,
        ]);
    }

    // =============================================
    // LOGIN SUCCESS — 2 TEST
    // =============================================

    public function test_login_success_returns_token_and_citizen()
    {
        $this->createVerifiedCitizen('123456');

        $result = $this->service->login([
            'nik' => '6301234567890123',
            'pin' => '123456',
        ]);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('citizen', $result);
        $this->assertNotNull($result['token']);
    }

    public function test_login_success_updates_last_login_at()
    {
        $this->createVerifiedCitizen('123456');

        $this->service->login([
            'nik' => '6301234567890123',
            'pin' => '123456',
        ]);

        $this->assertNotNull(Citizen::first()->last_login_at);
    }

    // =============================================
    // LOGIN FAILED — 3 TEST
    // =============================================

    public function test_login_wrong_pin_throws_exception()
    {
        $this->expectException(ValidationException::class);
        $this->createVerifiedCitizen('999999');

        $this->service->login([
            'nik' => '6301234567890123',
            'pin' => '123456',
        ]);
    }

    public function test_login_nik_not_found_throws_exception()
    {
        $this->expectException(ValidationException::class);

        $this->service->login([
            'nik' => '6309999999999999',
            'pin' => '123456',
        ]);
    }

    public function test_login_unverified_citizen_throws_exception()
    {
        $this->expectException(ValidationException::class);

        Citizen::factory()->create([
            'nik'         => '6301234567890123',
            'pin'         => Hash::make('123456'),
            'is_verified' => false,
        ]);

        $this->service->login([
            'nik' => '6301234567890123',
            'pin' => '123456',
        ]);
    }

    // =============================================
    // RATE LIMIT — 1 TEST
    // =============================================

    public function test_login_rate_limited_after_5_failed_attempts()
    {
        $this->expectException(ValidationException::class);

        $this->createVerifiedCitizen('123456');
        $key = 'login:6301234567890123';

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 900);
        }

        $this->service->login([
            'nik' => '6301234567890123',
            'pin' => '123456',
        ]);
    }
}