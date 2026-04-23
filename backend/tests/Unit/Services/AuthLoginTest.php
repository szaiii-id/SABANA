<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthService;
use App\Services\FonnteService;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Mockery;

class AuthLoginTest extends TestCase
{
    protected $citizenRepo;
    protected $fonnte;
    protected $authService;
    protected $verifiedCitizen;
    protected $unverifiedCitizen;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->citizenRepo = Mockery::mock(CitizenRepositoryInterface::class);
        $this->fonnte = Mockery::mock(FonnteService::class);
        
        $this->authService = new AuthService(
            $this->citizenRepo,
            $this->fonnte
        );

        // DATA: Warga yang SUDAH verifikasi
        $this->verifiedCitizen = Mockery::mock(Citizen::class)->makePartial();
        $this->verifiedCitizen->id = 1;
        $this->verifiedCitizen->nik = '6301012345678901';
        $this->verifiedCitizen->pin = Hash::make('123456');
        $this->verifiedCitizen->is_verified = true;

        // DATA: Warga yang BELUM verifikasi
        $this->unverifiedCitizen = Mockery::mock(Citizen::class)->makePartial();
        $this->unverifiedCitizen->id = 2;
        $this->unverifiedCitizen->nik = '6301012345678902';
        $this->unverifiedCitizen->pin = Hash::make('123456');
        $this->unverifiedCitizen->is_verified = false;
        $this->unverifiedCitizen->whatsapp_number = '08123456789';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * [SUCCESS] Berhasil Login, update last_login_at, dan menerbitkan Token Sanctum
     */
    public function test_login_successfully(): void
    {
        $credentials = ['nik' => '6301012345678901', 'pin' => '123456'];

        $this->citizenRepo->shouldReceive('findByNik')->with($credentials['nik'])->andReturn($this->verifiedCitizen);
        
        // Memastikan sistem mencatat waktu login terakhir
        $this->citizenRepo->shouldReceive('update')->once()->with(1, \Mockery::type('array'))->andReturn(true);

        // Mocking pembuatan Token Sanctum
        $tokenMock = (object) ['plainTextToken' => 'sabana-super-secret-token'];
        $this->verifiedCitizen->shouldReceive('createToken')->once()->with('Sabana App Token')->andReturn($tokenMock);

        $result = $this->authService->login($credentials);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('sabana-super-secret-token', $result['token']);
    }

    /**
     * [NEGATIVE] Gagal Login karena NIK tidak ditemukan
     */
    public function test_login_fails_if_nik_not_found(): void
    {
        $credentials = ['nik' => '9999999999999999', 'pin' => '123456'];

        $this->citizenRepo->shouldReceive('findByNik')->with($credentials['nik'])->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau PIN yang Anda masukkan salah.');

        $this->authService->login($credentials);
    }

    /**
     * [NEGATIVE] Gagal Login karena PIN salah
     */
    public function test_login_fails_if_pin_is_wrong(): void
    {
        $credentials = ['nik' => '6301012345678901', 'pin' => '654321']; // PIN Salah

        $this->citizenRepo->shouldReceive('findByNik')->with($credentials['nik'])->andReturn($this->verifiedCitizen);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NIK atau PIN yang Anda masukkan salah.');

        $this->authService->login($credentials);
    }

    /**
     * [EDGE CASE] Gagal Login karena akun belum di-verifikasi (OTP belum diisi)
     */
    public function test_login_fails_if_account_is_unverified(): void
    {
        $credentials = ['nik' => '6301012345678902', 'pin' => '123456'];

        $this->citizenRepo->shouldReceive('findByNik')->with($credentials['nik'])->andReturn($this->unverifiedCitizen);

        try {
            $this->authService->login($credentials);
            $this->fail('Seharusnya melempar ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('is_verified', $errors);
            $this->assertEquals('Akun belum aktif. Silakan verifikasi nomor WhatsApp Anda.', $errors['is_verified'][0]);
            
            // Memastikan frontend mendapat nomor WA untuk diarahkan ke layar OTP
            $this->assertEquals('08123456789', $errors['whatsapp_number'][0]);
        }
    }
}