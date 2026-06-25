<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Auth/ForgotPinControllerTest.php =====

namespace Tests\Feature\Auth;

use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class ForgotPinControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        RateLimiter::clear('otp-request:6371012508900001');
        RateLimiter::clear('otp-request-ip:127.0.0.1');
    }

    // ===== [DATA HELPERS] =====

    private function validSendOtpData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
        ], $overrides);
    }

    private function validResetPinData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
            'otp' => '654321',
            'new_pin' => '111111',
            'new_pin_confirmation' => '111111',
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

    // ===== URL HELPERS =====

    private function sendOtpUrl(): string
    {
        return '/api/v1/auth/forgot-pin';
    }

    private function resetPinUrl(): string
    {
        return '/api/v1/auth/reset-pin';
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_send_otp_berhasil(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData());

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'PIN sementara telah dikirim ke nomor WhatsApp Anda.',
        ]);
    }

    public function test_reset_pin_berhasil(): void
    {
        $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData([
            'otp' => '654321',
            'new_pin' => '999999',
            'new_pin_confirmation' => '999999',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'PIN berhasil diubah. Silakan login menggunakan PIN baru Anda.',
        ]);

        // Verifikasi PIN baru tersimpan
        $citizen = Citizen::where('nik', '6371012508900001')->first();
        $this->assertTrue(Hash::check('999999', $citizen->pin));
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_send_otp_gagal_nik_tidak_ditemukan(): void
    {
        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData());

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
    }

    public function test_send_otp_gagal_akun_belum_terverifikasi(): void
    {
        Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'is_verified' => false,
        ]);

        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData());

        $response->assertStatus(422);
        $response->assertJsonFragment(['Akun belum terverifikasi. Silakan verifikasi terlebih dahulu.']);
    }

    public function test_send_otp_gagal_nik_kosong(): void
    {
        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData(['nik' => '']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_send_otp_gagal_nik_kurang_16_digit(): void
    {
        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData(['nik' => '637101']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_send_otp_gagal_whatsapp_kosong(): void
    {
        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData(['whatsapp_number' => '']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['whatsapp_number']);
    }

    public function test_reset_pin_gagal_otp_salah(): void
    {
        $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData(['otp' => '000000']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['Kode OTP salah atau tidak valid.']);
    }

    public function test_reset_pin_gagal_otp_expired(): void
    {
        $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->subHour(),
        ]);

        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData(['otp' => '654321']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['Kode OTP telah kedaluwarsa. Silakan minta kode baru.']);
    }

    public function test_reset_pin_gagal_pin_baru_tidak_cocok(): void
    {
        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData([
            'new_pin_confirmation' => '999999',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_pin']);
    }

    public function test_reset_pin_gagal_nik_tidak_ditemukan(): void
    {
        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData());

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_send_otp_nik_tepat_16_digit(): void
    {
        $this->seedVerifiedCitizen(['nik' => '6399999999999999']);

        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData([
            'nik' => '6399999999999999',
        ]));

        $response->assertStatus(200);
    }

    public function test_reset_pin_otp_tepat_6_digit(): void
    {
        $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('000000'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData([
            'otp' => '000000',
            'new_pin' => '999999',
            'new_pin_confirmation' => '999999',
        ]));

        $response->assertStatus(200);
    }

    public function test_reset_pin_pin_baru_tepat_6_digit(): void
    {
        $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData([
            'otp' => '654321',
            'new_pin' => '000000',
            'new_pin_confirmation' => '000000',
        ]));

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_send_otp_sanitasi_nik_menghapus_spasi(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData([
            'nik' => '6371 0125 0890 0001',
        ]));

        $response->assertStatus(200);
    }

    public function test_reset_pin_sanitasi_otp_menghapus_spasi(): void
    {
        $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('123456'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData([
            'otp' => '12 34 56',
            'new_pin' => '999999',
            'new_pin_confirmation' => '999999',
        ]));

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_send_otp_gagal_semua_field_kosong(): void
    {
        $response = $this->postJson($this->sendOtpUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik', 'whatsapp_number']);
    }

    public function test_reset_pin_gagal_semua_field_kosong(): void
    {
        $response = $this->postJson($this->resetPinUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik', 'whatsapp_number', 'otp', 'new_pin']);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_send_otp_response_bertipe_valid(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData());

        $this->assertIsString($response->json('status'));
        $this->assertIsString($response->json('message'));
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_whatsapp_valid_send_otp_sukses(): void
    {
        $whatsapps = ['0812345678', '6281234567890', '628123456789012'];

        foreach ($whatsapps as $wa) {
            $nik = '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
            Citizen::create([
                'nik' => $nik,
                'family_card_number' => $nik,
                'full_name' => 'Test',
                'whatsapp_number' => $wa,
                'pin' => Hash::make('123456'),
                'is_verified' => true,
            ]);

            RateLimiter::clear('otp-request:' . $nik);
            RateLimiter::clear('otp-request-ip:127.0.0.1');

            $response = $this->postJson($this->sendOtpUrl(), [
                'nik' => $nik,
                'whatsapp_number' => $wa,
            ]);
            $response->assertStatus(200);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_reset_pin_mengubah_pin_lama_ke_baru(): void
    {
        $this->seedVerifiedCitizen([
            'pin' => Hash::make('123456'),
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $this->postJson($this->resetPinUrl(), $this->validResetPinData([
            'otp' => '654321',
            'new_pin' => '999999',
            'new_pin_confirmation' => '999999',
        ]));

        $citizen = Citizen::where('nik', '6371012508900001')->first();
        $this->assertFalse(Hash::check('123456', $citizen->pin));
        $this->assertTrue(Hash::check('999999', $citizen->pin));
        $this->assertNull($citizen->temporary_pin);
        $this->assertNull($citizen->temporary_pin_expired_at);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_rate_limit_send_otp_mencegah_spam(): void
    {
        $this->seedVerifiedCitizen();

        // Hit rate limit 3x (maksimal)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData());
        }

        // Attempt ke-4 harus kena rate limit
        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData());
        $response->assertStatus(422);
        $response->assertJsonFragment(['Terlalu banyak permintaan OTP dari perangkat ini. Silakan coba lagi dalam 30 menit.']);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_send_otp_tidak_mengembalikan_otp(): void
    {
        $this->seedVerifiedCitizen();

        $response = $this->postJson($this->sendOtpUrl(), $this->validSendOtpData());

        $response->assertJsonMissing(['otp']);
        $response->assertJsonMissing(['temporary_pin']);
    }

    public function test_reset_pin_tidak_mengembalikan_pin(): void
    {
        $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson($this->resetPinUrl(), $this->validResetPinData(['otp' => '654321']));

        $response->assertJsonMissing(['pin']);
        $response->assertJsonMissing(['new_pin']);
    }

    public function test_reset_pin_revoke_semua_token(): void
    {
        $citizen = $this->seedVerifiedCitizen([
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        // Buat token dulu
        $citizen->createToken('test');

        $this->postJson($this->resetPinUrl(), $this->validResetPinData(['otp' => '654321']));

        // Token harus terhapus
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $citizen->id,
            'tokenable_type' => Citizen::class,
        ]);
    }
}