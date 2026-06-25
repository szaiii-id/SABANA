<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Services/CitizenAuthServiceTest.php =====

namespace Tests\Unit\Services;

use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Services\CitizenAuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class CitizenAuthServiceTest extends TestCase
{
    private CitizenAuthService $service;
    private CitizenRepositoryInterface $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(CitizenRepositoryInterface::class);
        $this->service = new CitizenAuthService($this->repositoryMock);

        RateLimiter::clear('change-pin:019eba77-304a-70f0-b7ae-54a3e93cc1b6');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== [DATA HELPERS] =====

    private function mockCitizen(array $attributes = []): Citizen
    {
        $id = $attributes['id'] ?? '019eba77-304a-70f0-b7ae-54a3e93cc1b6';

        $citizen = Mockery::mock(Citizen::class)->makePartial();
        $citizen->shouldAllowMockingProtectedMethods();
        $citizen->shouldReceive('getKey')->andReturn($id);

        $citizen->id = $id;
        $citizen->pin = $attributes['pin'] ?? Hash::make('123456');

        return $citizen;
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_update_pin_berhasil(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $this->repositoryMock
            ->shouldReceive('updatePin')
            ->with($citizen, Mockery::on(fn($hashed) => Hash::check('999999', $hashed)))
            ->once()
            ->andReturn(true);

        $result = $this->service->updatePin($citizen, '123456', '999999');

        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_update_pin_gagal_pin_lama_salah(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('PIN lama tidak sesuai.');

        $this->service->updatePin($citizen, '000000', '999999');
    }

    public function test_update_pin_gagal_rate_limit(): void
    {
        $citizen = $this->mockCitizen();
        $key = 'change-pin:' . $citizen->id;

        RateLimiter::hit($key, 900);
        RateLimiter::hit($key, 900);
        RateLimiter::hit($key, 900);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak percobaan');

        $this->service->updatePin($citizen, '123456', '999999');
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_update_pin_3x_percobaan_masih_diizinkan(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);
        $key = 'change-pin:' . $citizen->id;

        RateLimiter::clear($key);

        // 2x gagal — masih boleh
        RateLimiter::hit($key, 900);
        RateLimiter::hit($key, 900);

        // Attempt ke-3 masih boleh (rate limit 3, baru ke-4 kena)
        RateLimiter::hit($key, 900);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Terlalu banyak percobaan');

        $this->service->updatePin($citizen, '123456', '999999');
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_update_pin_pin_baru_sama_dengan_pin_lama(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $this->repositoryMock
            ->shouldReceive('updatePin')
            ->with($citizen, Mockery::on(fn($hashed) => Hash::check('123456', $hashed)))
            ->once()
            ->andReturn(true);

        $result = $this->service->updatePin($citizen, '123456', '123456');

        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_update_pin_dengan_pin_kosong_gagal(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('PIN lama tidak sesuai.');

        $this->service->updatePin($citizen, '', '999999');
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_update_pin_return_boolean(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $this->repositoryMock
            ->shouldReceive('updatePin')
            ->andReturn(true);

        $result = $this->service->updatePin($citizen, '123456', '999999');

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_pin_valid_diterima(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $this->repositoryMock
            ->shouldReceive('updatePin')
            ->andReturn(true);

        // Berbagai format pin valid
        $result = $this->service->updatePin($citizen, '123456', '000000');
        $this->assertTrue($result);
    }

    public function test_grup_pin_salah_ditolak(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $this->expectException(ValidationException::class);

        $this->service->updatePin($citizen, '111111', '999999');
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_update_pin_sukses_reset_rate_limiter(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);
        $key = 'change-pin:' . $citizen->id;

        RateLimiter::clear($key);  // ← TAMBAH INI

        RateLimiter::hit($key, 900);
        RateLimiter::hit($key, 900);

        $this->repositoryMock
            ->shouldReceive('updatePin')
            ->andReturn(true);

        $this->service->updatePin($citizen, '123456', '999999');

        $this->assertFalse(RateLimiter::tooManyAttempts($key, 3));
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_rate_limiter_mencegah_brute_force(): void
    {
        $citizen = $this->mockCitizen();
        $key = 'change-pin:' . $citizen->id;

        RateLimiter::hit($key, 900);
        RateLimiter::hit($key, 900);
        RateLimiter::hit($key, 900);

        $this->expectException(ValidationException::class);

        $this->service->updatePin($citizen, '123456', '999999');
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_pin_baru_disimpan_dalam_bentuk_hash(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        $capturedPin = null;
        $this->repositoryMock
            ->shouldReceive('updatePin')
            ->with($citizen, Mockery::on(function ($hashed) use (&$capturedPin) {
                $capturedPin = $hashed;
                return true;
            }))
            ->andReturn(true);

        $this->service->updatePin($citizen, '123456', '999999');

        $this->assertNotEquals('999999', $capturedPin);
        $this->assertTrue(Hash::check('999999', $capturedPin));
    }

    public function test_pin_lama_tidak_terekspos_di_error(): void
    {
        $citizen = $this->mockCitizen(['pin' => Hash::make('123456')]);

        RateLimiter::clear('change-pin:' . $citizen->id);

        try {
            $this->service->updatePin($citizen, '000000', '999999');
        } catch (ValidationException $e) {
            $this->assertStringNotContainsString('123456', $e->getMessage());
        }
    }
}