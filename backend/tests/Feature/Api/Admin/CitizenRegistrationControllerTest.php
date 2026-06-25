<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Admin/CitizenRegistrationControllerTest.php =====

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CitizenRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->admin = Admin::create([
            'name' => 'Admin Test',
            'nip' => '123456789012345678',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'admin-api');
    }

    // ===== URL HELPERS =====

    private function storeUrl(): string
    {
        return '/api/v1/sabana-center-63/citizen-registration/create';
    }

    private function showUrl(string $id): string
    {
        return "/api/v1/sabana-center-63/citizen-registration/citizens/{$id}";
    }

    private function updateUrl(string $id): string
    {
        return "/api/v1/sabana-center-63/citizen-registration/citizens/{$id}";
    }

    private function resendPinUrl(string $id): string
    {
        return "/api/v1/sabana-center-63/citizen-registration/create/resend-pin/{$id}";
    }

    private function indexUrl(): string
    {
        return '/api/v1/sabana-center-63/citizen-registration/citizens';
    }

    private function searchUrl(): string
    {
        return '/api/v1/sabana-center-63/citizen-registration/citizens/search';
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'full_name' => 'Ahmad Fauzi',
            'family_card_number' => '6371012508900002',
            'whatsapp_number' => '6281234567890',
            'with_pin' => true,
        ], $overrides);
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_store_dengan_pin_berhasil(): void
    {
        $response = $this->postJson($this->storeUrl(), $this->validData());

        $response->assertStatus(201);
        $response->assertJson(['status' => 'success']);
        $response->assertJsonPath('data.access_pin', fn($v) => strlen($v) === 6);
    }

    public function test_store_tanpa_pin_berhasil(): void
    {
        $response = $this->postJson($this->storeUrl(), $this->validData(['with_pin' => false]));

        $response->assertStatus(201);
        $response->assertJson(['status' => 'success']);
    }

    public function test_show_detail_warga(): void
    {
        $citizen = Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => true,
        ]);

        $response = $this->getJson($this->showUrl($citizen->id));

        $response->assertStatus(200);
        $response->assertJsonPath('data.nik', '6371012508900001');
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_store_gagal_tanpa_nik(): void
    {
        $response = $this->postJson($this->storeUrl(), $this->validData(['nik' => '']));

        $response->assertStatus(422);
    }

    public function test_store_gagal_nik_bukan_kalsel(): void
    {
        $response = $this->postJson($this->storeUrl(), $this->validData(['nik' => '3301012508900001']));

        $response->assertStatus(422);
    }

    public function test_show_warga_tidak_ditemukan(): void
    {
        $response = $this->getJson($this->showUrl('00000000-0000-0000-0000-000000000000'));

        $response->assertStatus(404);
    }

    public function test_update_warga_tidak_ditemukan(): void
    {
        $response = $this->putJson(
            $this->updateUrl('00000000-0000-0000-0000-000000000000'),
            ['full_name' => 'Nama Baru']
        );

        $response->assertStatus(404);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_store_nama_tepat_3_karakter(): void
    {
        $response = $this->postJson($this->storeUrl(), $this->validData(['full_name' => 'Ali']));

        $response->assertStatus(201);
    }

    public function test_store_whatsapp_tepat_10_digit(): void
    {
        $response = $this->postJson($this->storeUrl(), $this->validData(['whatsapp_number' => '0812345678']));

        $response->assertStatus(201);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_store_sanitasi_nik_dengan_spasi(): void
    {
        $response = $this->postJson($this->storeUrl(), $this->validData([
            'nik' => '6371 0125 0890 0001',
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('citizens', ['nik' => '6371012508900001']);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_store_gagal_semua_field_kosong(): void
    {
        $response = $this->postJson($this->storeUrl(), []);

        $response->assertStatus(422);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_index_return_pagination(): void
    {
        $response = $this->getJson($this->indexUrl());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'total',
            'last_page',
            'per_page',
        ]);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_store_dengan_pin_dan_tanpa_pin(): void
    {
        $a = $this->postJson($this->storeUrl(), $this->validData(['with_pin' => true, 'nik' => '6300000000000001']));
        $b = $this->postJson($this->storeUrl(), $this->validData(['with_pin' => false, 'nik' => '6300000000000002']));

        $a->assertStatus(201);
        $b->assertStatus(201);
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_update_mengubah_nama_warga(): void
    {
        $citizen = Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Nama Lama',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => true,
        ]);

        $response = $this->putJson($this->updateUrl($citizen->id), ['full_name' => 'Nama Baru']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('citizens', ['id' => $citizen->id, 'full_name' => 'Nama Baru']);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_double_store_nik_sama_ditolak(): void
    {
        $this->postJson($this->storeUrl(), $this->validData());

        $response = $this->postJson($this->storeUrl(), $this->validData(['whatsapp_number' => '6289999999999']));

        $response->assertStatus(422);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_access_tanpa_auth_ditolak(): void
    {
        // Reset auth
        $this->app['auth']->guard('admin-api')->forgetUser();

        $response = $this->postJson($this->storeUrl(), $this->validData());

        $response->assertStatus(401);
    }

    public function test_pin_tidak_tampil_di_index(): void
    {
        $response = $this->getJson($this->indexUrl());

        $response->assertJsonMissing(['pin']);
    }
}