<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Admin/CitizenRegistrationServiceTest.php =====

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Citizen;
use App\Services\Admin\CitizenRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class CitizenRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CitizenRegistrationService $service;
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->admin = Admin::create([
            'id' => '019eba77-0000-7000-0000-000000000001',
            'name' => 'Admin Test',
            'nip' => '123456789012345678',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->service = new CitizenRegistrationService(
            new \App\Repositories\CitizenRegistrationRepository(null)
        );
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'full_name' => 'Ahmad Fauzi',
            'family_card_number' => '6371012508900002',
            'whatsapp_number' => '6281234567890',
        ], $overrides);
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_register_with_pin_berhasil(): void
    {
        $result = $this->service->registerWithPin($this->validData(), $this->admin->id);

        $this->assertArrayHasKey('citizen', $result);
        $this->assertArrayHasKey('access_pin', $result);
        $this->assertEquals(6, strlen($result['access_pin']));
        $this->assertDatabaseHas('citizens', ['nik' => '6371012508900001', 'is_verified' => true]);

        Queue::assertPushed(\App\Jobs\SendWhatsAppJob::class);
        Queue::assertPushed(\App\Jobs\IndexCitizenJob::class);
    }

    public function test_register_without_pin_berhasil(): void
    {
        $result = $this->service->registerWithoutPin($this->validData(), $this->admin->id);

        $this->assertArrayHasKey('citizen', $result);
        $this->assertArrayHasKey('access_pin', $result);
        $this->assertDatabaseHas('citizens', ['nik' => '6371012508900001', 'is_verified' => true]);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_register_gagal_nik_sudah_terverifikasi(): void
    {
        $this->service->registerWithPin($this->validData(), $this->admin->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('NIK ini sudah terdaftar dan aktif.');

        $this->service->registerWithPin($this->validData(['whatsapp_number' => '6289999999999']), $this->admin->id);
    }

    public function test_register_gagal_whatsapp_kosong(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nomor WhatsApp wajib diisi');

        $this->service->registerWithPin($this->validData(['whatsapp_number' => '']), $this->admin->id);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_access_pin_selalu_6_digit(): void
    {
        $result = $this->service->registerWithPin($this->validData(), $this->admin->id);

        $this->assertEquals(6, strlen($result['access_pin']));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $result['access_pin']);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_nik_belum_terverifikasi_di_update(): void
    {
        Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Lama',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => false,
        ]);

        $result = $this->service->registerWithPin($this->validData(), $this->admin->id);

        $this->assertTrue($result['citizen']->is_verified);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_register_without_pin_tanpa_whatsapp(): void
    {
        $result = $this->service->registerWithoutPin(
            $this->validData(['whatsapp_number' => '']),
            $this->admin->id
        );

        $this->assertEmpty($result['citizen']->whatsapp_number);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_register_return_array_dengan_citizen(): void
    {
        $result = $this->service->registerWithPin($this->validData(), $this->admin->id);

        $this->assertIsArray($result);
        $this->assertInstanceOf(Citizen::class, $result['citizen']);
        $this->assertIsString($result['access_pin']);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_register_pin_dan_tanpa_pin(): void
    {
        $a = $this->service->registerWithPin($this->validData(['nik' => '6300000000000001']), $this->admin->id);
        $b = $this->service->registerWithoutPin($this->validData(['nik' => '6300000000000002']), $this->admin->id);

        $this->assertArrayHasKey('access_pin', $a);
        $this->assertArrayHasKey('access_pin', $b);
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_update_citizen_mengubah_nama(): void
    {
        $citizen = $this->service->registerWithPin($this->validData(), $this->admin->id)['citizen'];

        $updated = $this->service->updateCitizen($citizen->id, ['full_name' => 'Nama Baru'], $this->admin->id);

        $this->assertEquals('Nama Baru', $updated->full_name);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_double_register_ditolak(): void
    {
        $this->service->registerWithPin($this->validData(), $this->admin->id);

        $this->expectException(\Exception::class);
        $this->service->registerWithPin($this->validData(['whatsapp_number' => '6289999999999']), $this->admin->id);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_pin_disimpan_dalam_bentuk_hash(): void
    {
        $result = $this->service->registerWithPin($this->validData(), $this->admin->id);
        $citizen = Citizen::find($result['citizen']->id);

        $this->assertNotEquals($result['access_pin'], $citizen->pin);
        $this->assertTrue(Hash::check($result['access_pin'], $citizen->pin));
    }

    public function test_reset_pin_mengembalikan_pin_baru(): void
    {
        $citizen = $this->service->registerWithPin($this->validData(), $this->admin->id)['citizen'];

        $result = $this->service->resetPinAndGetCardData($citizen->id, $this->admin->id);

        $this->assertArrayHasKey('access_pin', $result);
        $this->assertEquals(6, strlen($result['access_pin']));
    }
}