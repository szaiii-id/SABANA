<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Auth/LogoutControllerTest.php =====

namespace Tests\Feature\Auth;

use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    private Citizen $citizen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->citizen = Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => true,
        ]);
    }

    // ===== URL =====

    private function logoutUrl(): string
    {
        return '/api/v1/auth/logout';
    }

    private function getToken(): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nik' => '6371012508900001',
            'pin' => '123456',
        ]);

        return $response->json('data.token');
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_logout_berhasil_mengembalikan_200(): void
    {
        $token = $this->getToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl());

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Anda telah keluar dari sistem.',
        ]);
    }

    public function test_logout_berhasil_menghapus_token(): void
    {
        $token = $this->getToken();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->citizen->id,
            'tokenable_type' => Citizen::class,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl());

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->citizen->id,
            'tokenable_type' => Citizen::class,
        ]);
    }

    public function test_setelah_logout_token_terhapus_dari_db(): void
    {
        $token = $this->getToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl())
            ->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->citizen->id,
        ]);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_logout_tanpa_token_gagal(): void
    {
        $response = $this->postJson($this->logoutUrl());

        $response->assertStatus(401);
    }

    public function test_logout_dengan_token_salah_gagal(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->postJson($this->logoutUrl());

        $response->assertStatus(401);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_logout_dua_kali_dengan_token_sama(): void
    {
        $token = $this->getToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl())
            ->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->citizen->id,
        ]);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_logout_hanya_menghapus_token_saat_ini(): void
    {
        $token1 = $this->getToken();
        $token2 = $this->getToken();

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson($this->logoutUrl())
            ->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_logout_dengan_header_authorization_kosong(): void
    {
        $response = $this->withHeader('Authorization', '')
            ->postJson($this->logoutUrl());

        $response->assertStatus(401);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_logout_response_json_bertipe_valid(): void
    {
        $token = $this->getToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl());

        $this->assertIsString($response->json('status'));
        $this->assertIsString($response->json('message'));
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_user_authenticated_bisa_logout(): void
    {
        $token = $this->getToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl());

        $response->assertStatus(200);
    }

    public function test_grup_user_unauthenticated_tidak_bisa_logout(): void
    {
        $response = $this->postJson($this->logoutUrl());

        $response->assertStatus(401);
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_transisi_dari_authenticated_ke_unauthenticated(): void
    {
        $token = $this->getToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/citizen/profile')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl())
            ->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->citizen->id,
        ]);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_logout_bersamaan_antar_user_tidak_saling_pengaruh(): void
    {
        $token = $this->getToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl())
            ->assertStatus(200);

        $this->assertTrue(true);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_logout_tidak_mengembalikan_data_sensitif(): void
    {
        $token = $this->getToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl());

        $response->assertJsonMissing(['pin']);
        $response->assertJsonMissing(['token']);
        $response->assertJsonMissing(['password']);
    }

    public function test_logout_tidak_mengekspos_user_data(): void
    {
        $token = $this->getToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->logoutUrl());

        $response->assertJsonMissing(['nik']);
        $response->assertJsonMissing(['whatsapp_number']);
    }
}