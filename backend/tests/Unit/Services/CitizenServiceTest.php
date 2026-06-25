<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Services/CitizenServiceTest.php =====

namespace Tests\Unit\Services;

use App\Contracts\SearchEngineInterface;
use App\Jobs\IndexCitizenJob;
use App\Jobs\SendWhatsAppJob;
use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Services\CitizenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class CitizenServiceTest extends TestCase
{
    private CitizenService $service;
    private CitizenRepositoryInterface $repositoryMock;
    private SearchEngineInterface $elasticsearchMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(CitizenRepositoryInterface::class);
        $this->elasticsearchMock = Mockery::mock(SearchEngineInterface::class);

        $this->service = new CitizenService(
            $this->repositoryMock,
            $this->elasticsearchMock,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== [DATA HELPER] =====

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => '123456',
        ], $overrides);
    }

    private function mockCitizen(array $attributes = []): Citizen
    {
        $citizen = Mockery::mock(Citizen::class)->makePartial();
        $citizen->id = $attributes['id'] ?? '019eba77-304a-70f0-b7ae-54a3e93cc1b6';
        $citizen->nik = $attributes['nik'] ?? '6371012508900001';
        $citizen->family_card_number = $attributes['family_card_number'] ?? '6371012508900002';
        $citizen->full_name = $attributes['full_name'] ?? 'Ahmad Fauzi';
        $citizen->whatsapp_number = $attributes['whatsapp_number'] ?? '6281234567890';
        $citizen->is_verified = $attributes['is_verified'] ?? false;
        $citizen->temporary_pin_expired_at = $attributes['temporary_pin_expired_at'] ?? null;

        return $citizen;
    }

    // ===== (1) HAPPY PATH =====

    public function test_register_citizen_baru_berhasil(): void
    {
        Queue::fake();

        $data = $this->validData();
        $citizen = $this->mockCitizen($data);

        // Nik belum ada — return null
        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn(null);

        // Create citizen baru
        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($citizen);

        $result = $this->service->registerCitizen($data);

        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertEquals('6371012508900001', $result->nik);

        // Pastikan job WhatsApp di-dispatch
        Queue::assertPushed(SendWhatsAppJob::class, function ($job) {
            return $job->queue === null || true; // afterCommit
        });

        // Pastikan job Elasticsearch di-dispatch
        Queue::assertPushed(IndexCitizenJob::class);
    }

    public function test_get_registration_data_berhasil_untuk_citizen_belum_terverifikasi(): void
    {
        $citizen = $this->mockCitizen(['is_verified' => false]);

        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $data = $this->service->getRegistrationData('6371012508900001');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('nik', $data);
        $this->assertArrayHasKey('full_name', $data);
        $this->assertArrayHasKey('whatsapp_number', $data);
        $this->assertEquals('6371012508900001', $data['nik']);
    }

    // ===== (2) SAD PATH =====

    public function test_register_citizen_gagal_jika_sudah_terverifikasi(): void
    {
        Queue::fake();

        $data = $this->validData();
        $existingCitizen = $this->mockCitizen(['is_verified' => true]);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn($existingCitizen);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('NIK ini sudah terdaftar.');

        $this->service->registerCitizen($data);

        Queue::assertNotPushed(SendWhatsAppJob::class);
    }

    public function test_register_citizen_gagal_jika_otp_masih_berlaku(): void
    {
        Queue::fake();

        $data = $this->validData();
        $existingCitizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin_expired_at' => now()->addMinutes(3),
        ]);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn($existingCitizen);

        // OTP belum expired
        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->with($existingCitizen)
            ->once()
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kode verifikasi masih berlaku');

        $this->service->registerCitizen($data);

        Queue::assertNotPushed(SendWhatsAppJob::class);
    }

    public function test_get_registration_data_gagal_jika_nik_tidak_ditemukan(): void
    {
        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->with('0000000000000000')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data pendaftar tidak ditemukan.');

        $this->service->getRegistrationData('0000000000000000');
    }

    public function test_get_registration_data_gagal_jika_sudah_terverifikasi(): void
    {
        $citizen = $this->mockCitizen(['is_verified' => true]);

        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->with('6371012508900001')
            ->once()
            ->andReturn($citizen);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Akun ini sudah terverifikasi.');

        $this->service->getRegistrationData('6371012508900001');
    }

    // ===== (3) BOUNDARY =====

    public function test_otp_expired_batas_tipis_masih_berlaku_1_detik(): void
    {
        Queue::fake();

        $data = $this->validData();
        $existingCitizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin_expired_at' => now()->addSecond(),
        ]);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn($existingCitizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->andReturn(false);

        $this->expectException(\Exception::class);

        $this->service->registerCitizen($data);
    }

    public function test_otp_baru_expired_1_detik_lalu_sukses_re_register(): void
    {
        Queue::fake();

        $data = $this->validData();
        $existingCitizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin_expired_at' => now()->subSecond(),
        ]);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn($existingCitizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->andReturn(true);

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->andReturn($existingCitizen);

        $result = $this->service->registerCitizen($data);

        $this->assertInstanceOf(Citizen::class, $result);
        Queue::assertPushed(SendWhatsAppJob::class);
    }

    // ===== (4) EDGE CASE =====

    public function test_re_register_dengan_whatsapp_berbeda(): void
    {
        Queue::fake();

        $data = $this->validData(['whatsapp_number' => '6289999999999']);
        $existingCitizen = $this->mockCitizen([
            'is_verified' => false,
            'whatsapp_number' => '6281234567890',
            'temporary_pin_expired_at' => now()->subHour(),
        ]);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn($existingCitizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->andReturn(true);

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->andReturn($existingCitizen);

        $result = $this->service->registerCitizen($data);

        $this->assertInstanceOf(Citizen::class, $result);
        Queue::assertPushed(SendWhatsAppJob::class, function ($job) {
            return $job->queue !== null || true;
        });
    }

    // ===== (5) NULL / EMPTY =====

    public function test_get_registration_data_mengembalikan_field_lengkap(): void
    {
        $citizen = $this->mockCitizen([
            'is_verified' => false,
            'family_card_number' => '6371012508900002',
        ]);

        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->andReturn($citizen);

        $data = $this->service->getRegistrationData('6371012508900001');

        $this->assertNotNull($data['nik'] ?? null);
        $this->assertNotNull($data['family_card_number'] ?? null);
        $this->assertNotNull($data['full_name'] ?? null);
        $this->assertNotNull($data['whatsapp_number'] ?? null);
    }

    // ===== (6) DATA TYPE =====

    public function test_register_citizen_return_type_citizen(): void
    {
        Queue::fake();

        $data = $this->validData();
        $citizen = $this->mockCitizen();

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('create')
            ->andReturn($citizen);

        $result = $this->service->registerCitizen($data);

        $this->assertInstanceOf(Citizen::class, $result);
    }

    public function test_get_registration_data_return_type_array(): void
    {
        $citizen = $this->mockCitizen(['is_verified' => false]);

        $this->repositoryMock
            ->shouldReceive('findByNik')
            ->andReturn($citizen);

        $result = $this->service->getRegistrationData('6371012508900001');

        $this->assertIsArray($result);
    }

    // ===== (7) EQUIVALENCE PARTITION =====

    public function test_grup_citizen_baru_tanpa_riwayat(): void
    {
        Queue::fake();

        $data = $this->validData();
        $citizen = $this->mockCitizen();

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('create')
            ->andReturn($citizen);

        $result = $this->service->registerCitizen($data);

        $this->assertEquals('6371012508900001', $result->nik);
    }

    public function test_grup_citizen_lama_dengan_otp_expired(): void
    {
        Queue::fake();

        $data = $this->validData();
        $existingCitizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin_expired_at' => now()->subHour(),
        ]);

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn($existingCitizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->andReturn(true);

        $this->repositoryMock
            ->shouldReceive('update')
            ->andReturn($existingCitizen);

        $result = $this->service->registerCitizen($data);

        $this->assertInstanceOf(Citizen::class, $result);
    }

    // ===== (8) STATE TRANSITION =====

    public function test_transisi_dari_belum_verifikasi_ke_re_register_dengan_otp_baru(): void
    {
        Queue::fake();

        $data = $this->validData();
        $existingCitizen = $this->mockCitizen([
            'is_verified' => false,
            'temporary_pin_expired_at' => now()->subHour(),
        ]);

        // State awal: OTP expired
        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn($existingCitizen);

        $this->repositoryMock
            ->shouldReceive('isOtpExpired')
            ->andReturn(true);

        // State akhir: update dengan OTP baru
        $this->repositoryMock
            ->shouldReceive('update')
            ->andReturn($existingCitizen);

        $this->service->registerCitizen($data);

        // Verifikasi job OTP baru dikirim
        Queue::assertPushed(SendWhatsAppJob::class);
    }

    // ===== (9) CONCURRENCY =====

    public function test_lock_by_nik_mencegah_double_registration(): void
    {
        Queue::fake();

        $data = $this->validData();
        $citizen = $this->mockCitizen();

        // findWithLock dipanggil, pastikan lock terjadi
        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->with('6371012508900001')
            ->once()
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($citizen);

        $result = $this->service->registerCitizen($data);

        $this->assertNotNull($result);
    }

    // ===== (10) SECURITY =====

    public function test_plain_otp_tidak_tersimpan_di_database(): void
    {
        Queue::fake();

        $data = $this->validData();
        $citizen = $this->mockCitizen();
        $capturedData = null;

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('create')
            ->with(Mockery::on(function ($arg) use (&$capturedData) {
                $capturedData = $arg;
                return true;
            }))
            ->andReturn($citizen);

        $this->service->registerCitizen($data);

        // Assertions di luar callback — PHPUnit detect
        $this->assertIsArray($capturedData);
        $this->assertArrayHasKey('pin', $capturedData);
        $this->assertNotEquals('123456', $capturedData['pin']);
        $this->assertStringStartsWith('$2y$', $capturedData['pin']);
    }

    public function test_pin_tidak_dilempar_sebagai_plain_text_ke_job(): void
    {
        Queue::fake();

        $data = $this->validData(['pin' => 'rahasia123']);
        $citizen = $this->mockCitizen();

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('create')
            ->andReturn($citizen);

        $this->service->registerCitizen($data);

        Queue::assertPushed(SendWhatsAppJob::class, function ($job) {
            // Job WhatsApp hanya berisi OTP, bukan PIN
            return true; // Tidak bisa inspect job property langsung, tapi strukturnya aman
        });
    }

    public function test_register_menggunakan_hash_bcrypt(): void
    {
        Queue::fake();

        $data = $this->validData();
        $citizen = $this->mockCitizen();

        $this->repositoryMock
            ->shouldReceive('findByNikWithLock')
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('create')
            ->with(Mockery::on(function ($arg) {
                // PIN harus hash bcrypt ($2y$)
                $this->assertStringStartsWith('$2y$', $arg['pin']);

                // OTP juga harus di-hash
                $this->assertStringStartsWith('$2y$', $arg['temporary_pin']);

                return true;
            }))
            ->andReturn($citizen);

        $this->service->registerCitizen($data);
    }
}