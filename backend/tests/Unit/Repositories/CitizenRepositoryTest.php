<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Repositories/CitizenRepositoryTest.php =====

namespace Tests\Unit\Repositories;

use App\Models\Citizen;
use App\Repositories\CitizenRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CitizenRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CitizenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CitizenRepository();
    }

    // ===== [DATA HELPER] =====

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => false,
        ], $overrides);
    }

    private function createCitizen(array $overrides = []): Citizen
    {
        return Citizen::create($this->validData($overrides));
    }

    // ===== (1) HAPPY PATH =====

    public function test_create_mengembalikan_instance_citizen(): void
    {
        $citizen = $this->repository->create($this->validData());

        $this->assertInstanceOf(Citizen::class, $citizen);
        $this->assertNotNull($citizen->id);
        $this->assertEquals('6371012508900001', $citizen->nik);
    }

    public function test_find_by_nik_mengembalikan_citizen_yang_ada(): void
    {
        $this->createCitizen(['nik' => '6371012508900001']);

        $citizen = $this->repository->findByNik('6371012508900001');

        $this->assertNotNull($citizen);
        $this->assertEquals('6371012508900001', $citizen->nik);
    }

    public function test_update_mengembalikan_citizen_dengan_data_baru(): void
    {
        $citizen = $this->createCitizen();
        $updated = $this->repository->update($citizen->id, ['full_name' => 'Nama Baru']);

        $this->assertEquals('Nama Baru', $updated->full_name);
        $this->assertEquals('Nama Baru', $citizen->fresh()->full_name);
    }

    // ===== (2) SAD PATH =====

    public function test_find_by_nik_mengembalikan_null_jika_tidak_ditemukan(): void
    {
        $citizen = $this->repository->findByNik('9999999999999999');

        $this->assertNull($citizen);
    }

    public function test_find_by_nik_and_whatsapp_gagal_jika_nik_salah(): void
    {
        $this->createCitizen([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
        ]);

        $citizen = $this->repository->findByNikAndWhatsapp('9999999999999999', '6281234567890');

        $this->assertNull($citizen);
    }

    public function test_find_by_nik_and_whatsapp_gagal_jika_whatsapp_salah(): void
    {
        $this->createCitizen([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
        ]);

        $citizen = $this->repository->findByNikAndWhatsapp('6371012508900001', '6289999999999');

        $this->assertNull($citizen);
    }

    public function test_update_gagal_jika_id_tidak_ditemukan(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->update('019eba77-0000-0000-0000-000000000000', ['full_name' => 'Hantu']);
    }

    // ===== (3) BOUNDARY =====

    public function test_find_by_nik_dengan_nik_16_karakter(): void
    {
        $this->createCitizen(['nik' => '1234567890123456']);

        $citizen = $this->repository->findByNik('1234567890123456');

        $this->assertNotNull($citizen);
    }

    public function test_update_dengan_array_kosong_tidak_mengubah_data(): void
    {
        $citizen = $this->createCitizen();
        $original = $citizen->full_name;

        $this->repository->update($citizen->id, []);
        $citizen->refresh();

        $this->assertEquals($original, $citizen->full_name);
    }

    // ===== (4) EDGE CASE =====

    public function test_find_by_nik_and_whatsapp_dengan_nomor_sama_beda_nik(): void
    {
        // WhatsApp tidak unique — satu nomor bisa dipakai beberapa NIK
        $citizen1 = $this->createCitizen([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
        ]);
        $this->createCitizen([
            'nik' => '6371012508900003',
            'whatsapp_number' => '6281234567890',
        ]);

        // findByNikAndWhatsapp harusnya return spesifik NIK
        $result = $this->repository->findByNikAndWhatsapp('6371012508900001', '6281234567890');

        $this->assertNotNull($result);
        $this->assertEquals($citizen1->id, $result->id);
    }

    public function test_find_by_nik_with_lock_mengembalikan_citizen(): void
    {
        $this->createCitizen(['nik' => '6371012508900001']);

        DB::transaction(function () {
            $citizen = $this->repository->findByNikWithLock('6371012508900001');
            $this->assertNotNull($citizen);
            $this->assertEquals('6371012508900001', $citizen->nik);
        });
    }

    // ===== (5) NULL / EMPTY =====

    public function test_is_otp_expired_jika_temporary_pin_expired_at_null(): void
    {
        $citizen = $this->createCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => null,
        ]);

        $this->assertTrue($this->repository->isOtpExpired($citizen));
    }

    public function test_is_otp_expired_jika_temporary_pin_tidak_ada(): void
    {
        $citizen = $this->createCitizen([
            'temporary_pin' => null,
            'temporary_pin_expired_at' => null,
        ]);

        $this->assertTrue($this->repository->isOtpExpired($citizen));
    }
    
    // ===== (6) DATA TYPE =====

    public function test_create_mengembalikan_citizen_dengan_id_bertipe_string_uuid(): void
    {
        $citizen = $this->repository->create($this->validData());

        $this->assertIsString($citizen->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $citizen->id
        );
    }

    public function test_find_by_nik_mengembalikan_null_tidak_mengembalikan_collection(): void
    {
        $result = $this->repository->findByNik('0000000000000000');

        $this->assertNull($result);
        $this->assertNotInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    // ===== (7) EQUIVALENCE PARTITION =====

    public function test_grup_is_otp_expired_mengembalikan_true(): void
    {
        $citizen = $this->createCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->subHour(),
        ]);

        $this->assertTrue($this->repository->isOtpExpired($citizen));
    }

    public function test_grup_is_otp_expired_mengembalikan_false(): void
    {
        $citizen = $this->createCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(5),
        ]);

        $this->assertFalse($this->repository->isOtpExpired($citizen));
    }

    // ===== (8) STATE TRANSITION =====

    public function test_transisi_pin_setelah_update_pin(): void
    {
        $citizen = $this->createCitizen(['pin' => Hash::make('pin_lama')]);
        $pinLama = $citizen->pin;

        $this->repository->updatePin($citizen, Hash::make('pin_baru'));
        $citizen->refresh();

        $this->assertNotEquals($pinLama, $citizen->pin);
        $this->assertTrue(Hash::check('pin_baru', $citizen->pin));
    }

    public function test_transisi_is_verified_melalui_update(): void
    {
        $citizen = $this->createCitizen(['is_verified' => false]);
        $this->assertFalse($citizen->is_verified);

        $this->repository->update($citizen->id, ['is_verified' => true]);
        $citizen->refresh();

        $this->assertTrue($citizen->is_verified);
    }

    public function test_transisi_last_login_melalui_update(): void
    {
        $citizen = $this->createCitizen();
        $this->assertNull($citizen->last_login_at);

        $this->repository->update($citizen->id, ['last_login_at' => now()]);
        $citizen->refresh();

        $this->assertNotNull($citizen->last_login_at);
    }

    // ===== (9) CONCURRENCY =====

    public function test_find_by_nik_with_lock_di_dalam_transaction_mencegah_stale_data(): void
    {
        $this->createCitizen(['nik' => '6371012508900001', 'full_name' => 'Asli']);

        DB::transaction(function () {
            $citizen = $this->repository->findByNikWithLock('6371012508900001');
            $this->assertNotNull($citizen);

            // Simulasi update di dalam lock
            $citizen->update(['full_name' => 'Diubah Dalam Lock']);
            $this->assertEquals('Diubah Dalam Lock', $citizen->full_name);
        });

        $this->assertEquals('Diubah Dalam Lock', Citizen::where('nik', '6371012508900001')->first()->full_name);
    }

    public function test_update_pin_menghapus_semua_token(): void
    {
        $citizen = $this->createCitizen();
        $citizen->createToken('test-token');

        $this->assertCount(1, $citizen->tokens);

        $this->repository->updatePin($citizen, Hash::make('pin_baru'));
        $citizen->refresh();

        $this->assertCount(0, $citizen->tokens);
    }

    // ===== (10) SECURITY =====

    public function test_pin_tersimpan_dalam_bentuk_hash_tidak_plain_text(): void
    {
        $citizen = $this->repository->create($this->validData(['pin' => Hash::make('rahasia123')]));

        $this->assertNotEquals('rahasia123', $citizen->pin);
        $this->assertTrue(Hash::check('rahasia123', $citizen->pin));
        $this->assertStringStartsWith('$2y$', $citizen->pin);
    }

    public function test_update_pin_menghasilkan_hash_baru_tidak_sama_dengan_input(): void
    {
        $citizen = $this->createCitizen();
        $hashed = Hash::make('pin_baru_123456');

        $this->repository->updatePin($citizen, $hashed);
        $citizen->refresh();

        $this->assertNotEquals('pin_baru_123456', $citizen->pin);
        $this->assertTrue(Hash::check('pin_baru_123456', $citizen->pin));
    }

    public function test_update_tidak_bisa_mengisi_field_yang_tidak_ada_di_fillable(): void
    {
        $citizen = $this->createCitizen();

        // 'id' tidak di fillable, harusnya tidak berubah
        $idLama = $citizen->id;
        $this->repository->update($citizen->id, [
            'id' => '019eba77-9999-9999-9999-999999999999',
            'full_name' => 'Test',
        ]);
        $citizen->refresh();

        $this->assertEquals($idLama, $citizen->id);
        $this->assertEquals('Test', $citizen->full_name);
    }
}