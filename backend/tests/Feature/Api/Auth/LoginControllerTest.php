<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Auth/LoginControllerTest.php =====

namespace Tests\Feature\Auth;

use App\Models\Citizen;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        RateLimiter::clear('login:6371012508900001');
        RateLimiter::clear('login-ip:127.0.0.1');
    }

    // ===== [DATA HELPERS] =====

    private function validLoginData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'pin' => '123456',
        ], $overrides);
    }

    private function seedVerifiedCitizen(array $overrides = []): Citizen
    {
        return Citizen::create(array_merge([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => true,
        ], $overrides));
    }

    private function seedUnverifiedCitizen(): Citizen
    {
        return Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => false,
        ]);
    }

    // ===== URL =====

    private function loginUrl(): string
    {
        return '/api/v1/auth/login';
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_login_berhasil_mengembalikan_token(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData());

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Login berhasil.',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'citizen' => [
                    'nik',
                    'full_name',
                    'whatsapp_number',
                    'is_verified',
                ],
                'token',
                'require_pin_change',
            ],
        ]);

        $this->assertNotNull($response->json('data.token'));
        $this->assertIsString($response->json('data.token'));
    }

    public function test_login_berhasil_update_last_login(): void
    {
        $this->seedVerifiedCitizen();

        $this->postJson($this->loginUrl(), $this->validLoginData());

        $this->assertDatabaseHas('citizens', [
            'nik' => '6371012508900001',
        ]);
        $citizen = Citizen::where('nik', '6371012508900001')->first();
        $this->assertNotNull($citizen->last_login_at);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_login_gagal_nik_salah(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData([
            'nik' => '0000000000000000',
        ]));

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
        $response->assertJsonFragment(['NIK atau PIN yang Anda masukkan salah.']);
    }

    public function test_login_gagal_pin_salah(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData([
            'pin' => '999999',
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['NIK atau PIN yang Anda masukkan salah.']);
    }

    public function test_login_gagal_akun_belum_terverifikasi(): void
    {
        $this->seedUnverifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData());

        $response->assertStatus(422);
        $response->assertJsonFragment(['Akun belum aktif. Silakan verifikasi nomor WhatsApp Anda.']);
    }

    public function test_login_gagal_nik_kosong(): void
    {
        $response = $this->postJson($this->loginUrl(), $this->validLoginData(['nik' => '']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_login_gagal_pin_kosong(): void
    {
        $response = $this->postJson($this->loginUrl(), $this->validLoginData(['pin' => '']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pin']);
    }

    public function test_login_gagal_nik_kurang_16_digit(): void
    {
        $response = $this->postJson($this->loginUrl(), $this->validLoginData(['nik' => '637101']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_login_gagal_pin_kurang_6_digit(): void
    {
        $response = $this->postJson($this->loginUrl(), $this->validLoginData(['pin' => '123']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pin']);
    }

    public function test_login_gagal_pin_mengandung_huruf(): void
    {
        $response = $this->postJson($this->loginUrl(), $this->validLoginData(['pin' => 'ABC123']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pin']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_login_nik_tepat_16_digit(): void
    {
        $this->seedVerifiedCitizen(['nik' => '6399999999999999']);

        $response = $this->postJson($this->loginUrl(), $this->validLoginData([
            'nik' => '6399999999999999',
        ]));

        $response->assertStatus(200);
    }

    public function test_login_pin_tepat_6_digit(): void
    {
        $this->seedVerifiedCitizen(['pin' => Hash::make('000000')]);

        $response = $this->postJson($this->loginUrl(), $this->validLoginData(['pin' => '000000']));

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_login_sanitasi_nik_menghapus_spasi(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData([
            'nik' => '6371 0125 0890 0001',
        ]));

        $response->assertStatus(200);
    }

    public function test_login_sanitasi_pin_menghapus_spasi(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData([
            'pin' => '12 34 56',
        ]));

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_login_gagal_semua_field_kosong(): void
    {
        $response = $this->postJson($this->loginUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik', 'pin']);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_login_response_token_bertipe_string(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData());

        $this->assertIsString($response->json('data.token'));
        $this->assertIsBool($response->json('data.require_pin_change'));
        $this->assertIsBool($response->json('data.citizen.is_verified'));
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_pin_valid_login_sukses(): void
    {
        $pins = ['000000', '123456', '999999'];

        foreach ($pins as $pin) {
            $nik = '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
            Citizen::create([
                'nik' => $nik,
                'family_card_number' => $nik,
                'full_name' => 'Test',
                'whatsapp_number' => '628' . str_pad((string) random_int(1, 9999999), 9, '0', STR_PAD_LEFT),
                'pin' => Hash::make($pin),
                'is_verified' => true,
            ]);

            RateLimiter::clear('login:' . $nik);
            RateLimiter::clear('login-ip:127.0.0.1');

            $response = $this->postJson($this->loginUrl(), ['nik' => $nik, 'pin' => $pin]);
            $response->assertStatus(200);
        }
    }

    public function test_grup_pin_salah_login_ditolak(): void
    {
        $this->seedVerifiedCitizen();

        $wrongPins = ['000001', '111111', '999998'];

        foreach ($wrongPins as $pin) {
            RateLimiter::clear('login:6371012508900001');
            RateLimiter::clear('login-ip:127.0.0.1');

            $response = $this->postJson($this->loginUrl(), $this->validLoginData(['pin' => $pin]));
            $response->assertStatus(422);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_login_mengubah_last_login_dari_null_ke_terisi(): void
    {
        $citizen = $this->seedVerifiedCitizen();
        $this->assertNull($citizen->last_login_at);

        $this->postJson($this->loginUrl(), $this->validLoginData());

        $this->assertNotNull($citizen->fresh()->last_login_at);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_rate_limit_mencegah_brute_force(): void
    {
        $this->seedVerifiedCitizen();

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson($this->loginUrl(), $this->validLoginData(['pin' => '999999']));
            $response->assertStatus(422);
        }

        // Attempt ke-6 harus kena rate limit
        $response = $this->postJson($this->loginUrl(), $this->validLoginData(['pin' => '999999']));
        $response->assertStatus(422);
        $response->assertJsonFragment(['Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.']);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_login_tidak_mengembalikan_pin(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData());

        $response->assertJsonMissing(['pin']);
        $this->assertArrayNotHasKey('pin', $response->json('data.citizen'));
    }

    public function test_login_tidak_mengembalikan_temporary_pin(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData());

        $response->assertJsonMissing(['temporary_pin']);
    }

    public function test_login_akun_belum_verifikasi_tidak_bocorkan_data(): void
    {
        $this->seedUnverifiedCitizen();

        $response = $this->postJson($this->loginUrl(), $this->validLoginData());

        $response->assertStatus(422);
        $response->assertJsonMissing(['token']);
        $response->assertJsonMissing(['citizen']);
    }
}