<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Citizen;
use App\Services\AuthService;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Jobs\SendWhatsAppJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class AuthServiceForgotResetTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;
    private string $testIp = '127.0.0.1';

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('otp-request:6301234567890123');
        RateLimiter::clear('otp-request-ip:127.0.0.1');
        RateLimiter::clear('otp-request:6309999999999999');

        $repository = app(CitizenRepositoryInterface::class);
        $this->service = new AuthService($repository);
    }

    private function createCitizen(): Citizen
    {
        return Citizen::factory()->create([
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
            'is_verified'     => true,
            'pin'             => Hash::make('oldpin'),
        ]);
    }

    // ===== REQUEST OTP =====

    /** @test */
    public function test_request_otp_success_dispatches_job(): void
    {
        Queue::fake();
        $this->createCitizen();

        $this->service->requestOtp([
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
        ], $this->testIp); // ✅ Passing IP

        Queue::assertPushed(SendWhatsAppJob::class);
    }

    /** @test */
    public function test_request_otp_not_found_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->requestOtp([
            'nik'             => '6309999999999999',
            'whatsapp_number' => '6281234567890',
        ], $this->testIp); // ✅ Passing IP
    }

    /** @test */
    public function test_request_otp_rate_limited_after_3_attempts(): void
    {
        $this->expectException(ValidationException::class);

        $this->createCitizen();
        $key = 'otp-request:6301234567890123';
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 1800);
        }

        $this->service->requestOtp([
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
        ], $this->testIp);
    }

    /** @test */
    public function test_request_otp_updates_temporary_pin(): void
    {
        Queue::fake();
        $this->createCitizen();

        $this->service->requestOtp([
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
        ], $this->testIp);

        $citizen = Citizen::first();
        $this->assertNotNull($citizen->temporary_pin);
        $this->assertNotNull($citizen->temporary_pin_expired_at);
    }

    /** @test */
    public function test_request_otp_unverified_citizen_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        Citizen::factory()->create([
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
            'is_verified'     => false,
        ]);

        $this->service->requestOtp([
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
        ], $this->testIp);
    }

    // ===== RESET PIN =====

    /** @test */
    public function test_reset_pin_success(): void
    {
        $otp = '654321';
        $citizen = $this->createCitizen();
        $citizen->update([
            'temporary_pin'            => Hash::make($otp),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $this->service->resetPin([
            'nik'                  => '6301234567890123',
            'whatsapp_number'      => '6281234567890',
            'otp'                  => $otp,
            'new_pin'              => '999999',
            'new_pin_confirmation' => '999999',
        ]);

        $citizen->refresh();
        $this->assertTrue(Hash::check('999999', $citizen->pin));
        $this->assertNull($citizen->temporary_pin);
    }

    /** @test */
    public function test_reset_pin_wrong_otp_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $citizen = $this->createCitizen();
        $citizen->update([
            'temporary_pin'            => Hash::make('111111'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $this->service->resetPin([
            'nik'                  => '6301234567890123',
            'whatsapp_number'      => '6281234567890',
            'otp'                  => '999999',
            'new_pin'              => '654321',
            'new_pin_confirmation' => '654321',
        ]);
    }

    /** @test */
    public function test_reset_pin_expired_otp_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $otp = '654321';
        $citizen = $this->createCitizen();
        $citizen->update([
            'temporary_pin'            => Hash::make($otp),
            'temporary_pin_expired_at' => now()->subMinutes(5),
        ]);

        $this->service->resetPin([
            'nik'                  => '6301234567890123',
            'whatsapp_number'      => '6281234567890',
            'otp'                  => $otp,
            'new_pin'              => '999999',
            'new_pin_confirmation' => '999999',
        ]);
    }

    /** @test */
    public function test_reset_pin_not_found_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->resetPin([
            'nik'                  => '6309999999999999',
            'whatsapp_number'      => '6281234567890',
            'otp'                  => '123456',
            'new_pin'              => '999999',
            'new_pin_confirmation' => '999999',
        ]);
    }

    /** @test */
    public function test_reset_pin_deletes_all_tokens(): void
    {
        $otp = '654321';
        $citizen = $this->createCitizen();
        $citizen->createToken('test');
        $citizen->update([
            'temporary_pin'            => Hash::make($otp),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $this->service->resetPin([
            'nik'                  => '6301234567890123',
            'whatsapp_number'      => '6281234567890',
            'otp'                  => $otp,
            'new_pin'              => '999999',
            'new_pin_confirmation' => '999999',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}