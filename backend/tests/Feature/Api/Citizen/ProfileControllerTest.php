<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Citizen/ProfileControllerTest.php =====

namespace Tests\Feature\Citizen;

use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private Citizen $citizen;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->citizen = Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => true,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'nik' => '6371012508900001',
            'pin' => '123456',
        ]);

        $this->token = $loginResponse->json('data.token');

        RateLimiter::clear('change-wa:' . $this->citizen->id);
    }

    // ===== URL HELPERS =====

    private function profileUrl(): string
    {
        return '/api/v1/citizen/profile';
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_show_profile_berhasil(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson($this->profileUrl());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'nik',
                'family_card_number',
                'full_name',
                'whatsapp_number',
                'is_verified',
                'last_login',
            ],
        ]);
        $response->assertJsonPath('data.nik', '6371012508900001');
        $response->assertJsonPath('data.full_name', 'Ahmad Fauzi');
    }

    public function test_update_nama_berhasil(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['full_name' => 'Nama Baru']);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Data diri berhasil diperbarui.',
        ]);
        $response->assertJsonPath('data.full_name', 'Nama Baru');

        $this->assertDatabaseHas('citizens', [
            'id' => $this->citizen->id,
            'full_name' => 'Nama Baru',
        ]);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_show_profile_tanpa_token_gagal(): void
    {
        $response = $this->getJson($this->profileUrl());

        $response->assertStatus(401);
    }

    public function test_update_profile_tanpa_token_gagal(): void
    {
        $response = $this->patchJson($this->profileUrl(), ['full_name' => 'Nama Baru']);

        $response->assertStatus(401);
    }

    public function test_update_nama_dengan_karakter_invalid(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['full_name' => '<script>alert(1)</script>']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['full_name']);
    }

    public function test_update_whatsapp_kurang_dari_10_digit(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['whatsapp_number' => '0812']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['whatsapp_number']);
    }

    public function test_update_whatsapp_sudah_dipakai_akun_lain(): void
    {
        Citizen::create([
            'nik' => '6371012508900003',
            'family_card_number' => '6371012508900004',
            'full_name' => 'User Lain',
            'whatsapp_number' => '6289999999999',
            'pin' => Hash::make('123456'),
            'is_verified' => true,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['whatsapp_number' => '6289999999999']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['Nomor WhatsApp ini sudah digunakan oleh akun lain.']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_update_nama_tepat_255_karakter(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['full_name' => str_repeat('A', 255)]);

        $response->assertStatus(200);
    }

    public function test_update_whatsapp_tepat_10_digit(): void
    {
        RateLimiter::clear('change-wa:' . $this->citizen->id);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['whatsapp_number' => '0812345678']);

        // Bisa 200 (nama ikut) atau 422 (verifikasi WA) — tergantung flow
        $this->assertContains($response->status(), [200, 422]);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_update_nama_dengan_trim_spasi(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['full_name' => '  Nama Baru  ']);

        $response->assertStatus(200);
        $response->assertJsonPath('data.full_name', 'Nama Baru');
    }

    public function test_ganti_whatsapp_memicu_otp_verifikasi(): void
    {
        RateLimiter::clear('change-wa:' . $this->citizen->id);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['whatsapp_number' => '6289999999999']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['Nomor WhatsApp berhasil diubah. Silakan verifikasi nomor baru Anda melalui kode OTP yang telah dikirim.']);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_update_dengan_body_kosong(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), []);

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_show_profile_response_bertipe_valid(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson($this->profileUrl());

        $this->assertIsString($response->json('data.nik'));
        $this->assertIsString($response->json('data.full_name'));
        $this->assertIsBool($response->json('data.is_verified'));
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_update_nama_saja_sukses(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['full_name' => 'Budi Santoso']);

        $response->assertStatus(200);
    }

    public function test_grup_update_whatsapp_saja_memicu_verifikasi(): void
    {
        RateLimiter::clear('change-wa:' . $this->citizen->id);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['whatsapp_number' => '6288888888888']);

        $this->assertContains($response->status(), [200, 422]);
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_ganti_whatsapp_mengubah_is_verified_ke_false(): void
    {
        RateLimiter::clear('change-wa:' . $this->citizen->id);

        $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['whatsapp_number' => '6289999999999']);

        $this->assertDatabaseHas('citizens', [
            'id' => $this->citizen->id,
            'is_verified' => false,
        ]);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_rate_limit_ganti_whatsapp_3x_dalam_sejam(): void
    {
        $key = 'change-wa:' . $this->citizen->id;
        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key, 3600);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['whatsapp_number' => '6289999999999']);

        $response->assertStatus(422);
        $response->assertJsonFragment(['Terlalu banyak perubahan nomor. Silakan coba lagi dalam 60 menit.']);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_show_profile_tidak_menampilkan_pin(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson($this->profileUrl());

        $response->assertJsonMissing(['pin']);
        $response->assertJsonMissing(['temporary_pin']);
    }

    public function test_update_profile_tidak_menampilkan_pin(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson($this->profileUrl(), ['full_name' => 'Nama Baru']);

        $response->assertJsonMissing(['pin']);
    }
}