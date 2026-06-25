<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Auth/AccountVerificationControllerTest.php =====

namespace Tests\Feature\Auth;

use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class AccountVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        
        \Illuminate\Support\Facades\RateLimiter::clear('resend-otp:6371012508900001');
        \Illuminate\Support\Facades\RateLimiter::clear('verify-otp:6371012508900001');
        \Illuminate\Support\Facades\RateLimiter::clear('verify-otp-ip:127.0.0.1');
    }

    // ===== [DATA HELPERS] =====

    private function validVerifyData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
            'otp' => '654321',
        ], $overrides);
    }

    private function validResendData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'whatsapp_number' => '6281234567890',
        ], $overrides);
    }

    private function seedUnverifiedCitizen(array $overrides = []): Citizen
    {
        return Citizen::create(array_merge([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => Hash::make('123456'),
            'temporary_pin' => Hash::make('654321'),
            'temporary_pin_expired_at' => now()->addMinutes(10),
            'is_verified' => false,
        ], $overrides));
    }

    // ===== URL HELPERS =====

    private function verifyUrl(): string
    {
        return '/api/v1/auth/verify-registration';
    }

    private function resendUrl(): string
    {
        return '/api/v1/auth/resend-registration-otp';
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_verify_otp_berhasil(): void
    {
        $this->seedUnverifiedCitizen();

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '654321']));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Akun berhasil diverifikasi. Silakan login menggunakan NIK dan PIN Anda.',
        ]);

        $this->assertDatabaseHas('citizens', [
            'nik' => '6371012508900001',
            'is_verified' => true,
            'temporary_pin' => null,
            'temporary_pin_expired_at' => null,
        ]);
    }

   public function test_resend_otp_berhasil(): void
{
    $citizen = $this->seedUnverifiedCitizen([
        'temporary_pin_expired_at' => now()->subMinutes(30),
    ]);

    $response = $this->postJson($this->resendUrl(), $this->validResendData());
    $response->dump();
    $response->assertStatus(200);
}

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_verify_otp_gagal_kode_salah(): void
    {
        $this->seedUnverifiedCitizen();

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '000000']));

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
    }

    public function test_verify_otp_gagal_nik_tidak_ditemukan(): void
    {
        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData());

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
    }

    public function test_verify_otp_gagal_otp_expired(): void
    {
        $this->seedUnverifiedCitizen([
            'temporary_pin_expired_at' => now()->subMinutes(30),
        ]);

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '654321']));

        $response->assertStatus(422);
        $response->assertJsonFragment(['Kode OTP telah kedaluwarsa. Silakan minta kode baru.']);
    }

    public function test_verify_otp_gagal_nik_kurang_16_digit(): void
    {
        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['nik' => '637101']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_verify_otp_gagal_otp_kurang_6_digit(): void
    {
        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '123']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['otp']);
    }

    public function test_verify_otp_gagal_whatsapp_kurang_10_digit(): void
    {
        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['whatsapp_number' => '0812']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['whatsapp_number']);
    }

    public function test_resend_otp_gagal_nik_tidak_ditemukan(): void
    {
        $response = $this->postJson($this->resendUrl(), $this->validResendData());

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
    }

    public function test_resend_otp_gagal_nik_kurang_16_digit(): void
    {
        $response = $this->postJson($this->resendUrl(), $this->validResendData(['nik' => '637101']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_resend_otp_gagal_whatsapp_kurang_10_digit(): void
    {
        $response = $this->postJson($this->resendUrl(), $this->validResendData(['whatsapp_number' => '0812']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['whatsapp_number']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_verify_otp_tepat_6_digit(): void
    {
        $this->seedUnverifiedCitizen(['temporary_pin' => Hash::make('999999')]);

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '999999']));

        $response->assertStatus(200);
    }

    public function test_resend_otp_whatsapp_tepat_10_digit(): void
    {
        $this->seedUnverifiedCitizen([
            'whatsapp_number' => '0812345678',
            'temporary_pin_expired_at' => now()->subMinutes(30),
        ]);

        $response = $this->postJson($this->resendUrl(), $this->validResendData(['whatsapp_number' => '0812345678']));

        $response->assertStatus(200);
    }

    public function test_resend_otp_whatsapp_tepat_15_digit(): void
    {
        $this->seedUnverifiedCitizen([
            'whatsapp_number' => '628123456789012',
            'temporary_pin_expired_at' => now()->subMinutes(30),
        ]);

        $response = $this->postJson($this->resendUrl(), $this->validResendData(['whatsapp_number' => '628123456789012']));

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_verify_otp_sanitasi_nik_menghapus_spasi(): void
    {
        $this->seedUnverifiedCitizen();

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData([
            'nik' => '6371 0125 0890 0001',
            'otp' => '654321',
        ]));

        $response->assertStatus(200);
    }

    public function test_resend_otp_sanitasi_whatsapp_menghapus_plus(): void
    {
        $this->seedUnverifiedCitizen([
            'temporary_pin_expired_at' => now()->subMinutes(30),
        ]);

        $response = $this->postJson($this->resendUrl(), $this->validResendData([
            'whatsapp_number' => '+62 812-3456-7890',
        ]));

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_verify_otp_gagal_semua_field_kosong(): void
    {
        $response = $this->postJson($this->verifyUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik', 'whatsapp_number', 'otp']);
    }

    public function test_resend_otp_gagal_semua_field_kosong(): void
    {
        $response = $this->postJson($this->resendUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik', 'whatsapp_number']);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_verify_otp_response_json_bertipe_valid(): void
    {
        $this->seedUnverifiedCitizen();

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '654321']));

        $response->assertStatus(200);
        $this->assertIsString($response->json('status'));
        $this->assertIsString($response->json('message'));
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_otp_valid_diterima(): void
    {
        $otpList = ['000000', '999999', '123456', '654321'];

        foreach ($otpList as $otp) {
            $citizen = Citizen::create([
                'nik' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
                'family_card_number' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
                'full_name' => 'Test User',
                'whatsapp_number' => '628' . str_pad((string) random_int(1, 9999999), 9, '0', STR_PAD_LEFT),
                'pin' => Hash::make('123456'),
                'temporary_pin' => Hash::make($otp),
                'temporary_pin_expired_at' => now()->addMinutes(10),
                'is_verified' => false,
            ]);

            $response = $this->postJson($this->verifyUrl(), [
                'nik' => $citizen->nik,
                'whatsapp_number' => $citizen->whatsapp_number,
                'otp' => $otp,
            ]);

            $response->assertStatus(200);
        }
    }

    public function test_grup_otp_salah_ditolak(): void
    {
        $this->seedUnverifiedCitizen();
        $wrongOtps = ['000001', '999998', '111111'];

        foreach ($wrongOtps as $otp) {
            $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => $otp]));
            $response->assertStatus(422);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_verify_mengubah_state_dari_unverified_ke_verified(): void
    {
        $citizen = $this->seedUnverifiedCitizen();
        $this->assertFalse($citizen->is_verified);

        $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '654321']));

        $this->assertDatabaseHas('citizens', [
            'id' => $citizen->id,
            'is_verified' => true,
        ]);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_double_verify_tidak_menyebabkan_error(): void
    {
        $this->seedUnverifiedCitizen();

        $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '654321']));

        // Verify kedua → harusnya ditolak (sudah terverifikasi)
        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '654321']));

        $response->assertStatus(422);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_verify_otp_tidak_mengembalikan_data_sensitif(): void
    {
        $this->seedUnverifiedCitizen();

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '654321']));

        $response->assertJsonMissing(['pin']);
        $response->assertJsonMissing(['temporary_pin']);
        $response->assertJsonMissing(['token']);
    }

    public function test_resend_otp_tidak_mengembalikan_otp_dalam_response(): void
    {
        $this->seedUnverifiedCitizen(['temporary_pin_expired_at' => now()->subHour()]);

        $response = $this->postJson($this->resendUrl(), $this->validResendData());

        $response->assertJsonMissing(['otp']);
        $response->assertJsonMissing(['temporary_pin']);
    }

    public function test_sanitasi_otp_menghapus_karakter_non_digit(): void
    {
        $this->seedUnverifiedCitizen(['temporary_pin' => Hash::make('123456')]);

        $response = $this->postJson($this->verifyUrl(), $this->validVerifyData(['otp' => '12 34 56']));

        // Harusnya sukses — spasi dihapus jadi 123456
        $response->assertStatus(200);
    }
}