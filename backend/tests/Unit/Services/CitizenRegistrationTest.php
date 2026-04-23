<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CitizenService;
use App\Services\FonnteService;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Contracts\SearchEngineInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mockery;

class CitizenRegistrationTest extends TestCase
{
    protected $citizenRepo;
    protected $elastic;
    protected $fonnte;
    protected $citizenService;
    protected $validData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->citizenRepo = Mockery::mock(CitizenRepositoryInterface::class);
        $this->fonnte = Mockery::mock(FonnteService::class);
        $this->elastic = Mockery::mock(SearchEngineInterface::class);

        $this->citizenService = new CitizenService(
            $this->citizenRepo,
            $this->elastic,
            $this->fonnte
        );

        // KITA BUAT DATA STANDAR AGAR TIDAK ERROR "Undefined array key"
        $this->validData = [
            'nik' => '6301012345678901',
            'family_card_number' => '6301012345678902',
            'full_name' => 'Akhmad Jainudin',
            'whatsapp_number' => '08123456789',
            'pin' => '123456'
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close(); // Tutup Mockery dulu
        parent::tearDown(); // Baru panggil tearDown bawaan Laravel
    }

    /**
     * [SUCCESS] Registrasi warga baru
     */
    public function test_register_citizen_successfully(): void
    {
        $citizen = new Citizen($this->validData);
        $citizen->created_at = now(); 

        $this->citizenRepo->shouldReceive('findByNik')->with($this->validData['nik'])->andReturn(null);
        $this->citizenRepo->shouldReceive('create')->once()->andReturn($citizen);
        
        $this->fonnte->shouldReceive('sendMessage')->once()->andReturn(true);
        $this->elastic->shouldReceive('index')->once()->andReturn(true);

        $result = $this->citizenService->registerCitizen($this->validData);

        $this->assertEquals($this->validData['nik'], $result->nik);
    }

    /**
     * [EDGE CASE] Update data warga yang belum verifikasi
     */
    public function test_register_updates_existing_unverified_citizen(): void
    {
        // FIX 2: Lengkapi data existing agar Fonnte tidak menerima nomor WA null
        $existingCitizen = new Citizen([
            'id' => 1, 
            'nik' => '6301012345678901',
            'full_name' => 'Jainudin Updated',
            'whatsapp_number' => '08123456789', // Ini yang tadi kurang
            'is_verified' => false
        ]);
        $existingCitizen->created_at = now();

        $this->citizenRepo->shouldReceive('findByNik')->with($this->validData['nik'])->andReturn($existingCitizen);
        $this->citizenRepo->shouldReceive('update')->once()->andReturn(true);
        $this->citizenRepo->shouldReceive('findByNik')->andReturn($existingCitizen); 
        
        $this->fonnte->shouldReceive('sendMessage')->once();
        $this->elastic->shouldReceive('index')->once();

        $result = $this->citizenService->registerCitizen($this->validData);

        $this->assertEquals($this->validData['nik'], $result->nik);
    }

    /**
     * [NEGATIVE] Gagal registrasi karena sudah aktif
     */
    public function test_register_throws_exception_if_citizen_already_verified(): void
    {
        $verifiedCitizen = new Citizen(['nik' => '6301012345678901', 'is_verified' => true]);
        
        $this->citizenRepo->shouldReceive('findByNik')->andReturn($verifiedCitizen);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("NIK ini sudah terdaftar dan aktif.");

        // FIX 3: Gunakan $this->validData agar "pin" terbaca oleh Hash::make
        $this->citizenService->registerCitizen($this->validData);
    }

    /**
     * [EDGE CASE] Database Rollback saat error
     */
    public function test_register_rolls_back_on_database_error(): void
    {
        $this->citizenRepo->shouldReceive('findByNik')->andThrow(new \Exception("Database Connection Failed"));

        Log::shouldReceive('error')->once();

        $this->expectException(\Exception::class);
        
        // FIX 4: Gunakan $this->validData
        $this->citizenService->registerCitizen($this->validData);
    }
}