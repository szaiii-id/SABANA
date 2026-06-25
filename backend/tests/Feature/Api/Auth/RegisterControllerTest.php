<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Auth/RegisterControllerTest.php =====

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;


    // ===== [DATA HELPER] =====

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => '123456',
            'pin_confirmation' => '123456',
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // ===== URL HELPERS =====

    private function registerUrl(): string
    {
        return '/api/v1/auth/register';
    }

    private function prefillUrl(string $nik = ''): string
    {
        $query = $nik ? '?nik=' . $nik : '';
        return '/api/v1/auth/prefill-registration' . $query;
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_register_berhasil_mengembalikan_201(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData());

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Kode verifikasi telah dikirim melalui WhatsApp.',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'nik',
                'family_card_number',
                'full_name',
                'whatsapp_number',
                'is_verified',
                'last_login',
            ],
        ]);
        $this->assertDatabaseHas('citizens', [
            'nik' => '6371012508900001',
            'is_verified' => false,
        ]);
    }

    public function test_prefill_berhasil_untuk_citizen_belum_terverifikasi(): void
    {
        $this->postJson($this->registerUrl(), $this->validData());

        $response = $this->getJson($this->prefillUrl('6371012508900001'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'nik' => '6371012508900001',
                'full_name' => 'Ahmad Fauzi',
            ],
        ]);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_register_gagal_nik_kurang_dari_16_digit(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'nik' => '637101',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_register_gagal_nik_tidak_berawalan_63(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'nik' => '3371012508900001',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_register_gagal_nik_mengandung_huruf(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'nik' => '63710125089ABCDE',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_register_gagal_pin_tidak_cocok(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'pin_confirmation' => '999999',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pin']);
    }

    public function test_register_gagal_nama_kosong(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'full_name' => '',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['full_name']);
    }

    public function test_register_gagal_nama_terlalu_pendek(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'full_name' => 'AB',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['full_name']);
    }

    public function test_register_gagal_whatsapp_kurang_dari_10_digit(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'whatsapp_number' => '0812',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['whatsapp_number']);
    }

    public function test_register_gagal_pin_kurang_dari_6_digit(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'pin' => '123',
            'pin_confirmation' => '123',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pin']);
    }

    public function test_prefill_gagal_nik_tidak_ditemukan(): void
    {
        $response = $this->getJson($this->prefillUrl('0000000000000000'));

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_register_nik_tepat_16_digit_berawalan_63(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'nik' => '6399999999999999',
        ]));

        $response->assertStatus(201);
    }

    public function test_register_whatsapp_tepat_10_digit(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'whatsapp_number' => '0812345678',
        ]));

        $response->assertStatus(201);
    }

    public function test_register_whatsapp_tepat_15_digit(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'whatsapp_number' => '628123456789012',
        ]));

        $response->assertStatus(201);
    }

    public function test_register_nama_tepat_3_karakter(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'full_name' => 'Ali',
        ]));

        $response->assertStatus(201);
    }

    public function test_register_nama_tepat_255_karakter(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'full_name' => str_repeat('A', 255),
        ]));

        $response->assertStatus(201);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_sanitasi_nik_menghapus_spasi(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'nik' => '6371 0125 0890 0001',
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('citizens', ['nik' => '6371012508900001']);
    }

    public function test_sanitasi_whatsapp_menghapus_tanda_plus(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'whatsapp_number' => '+62 812-3456-7890',
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('citizens', ['whatsapp_number' => '6281234567890']);
    }

    public function test_sanitasi_nama_menghapus_tag_html(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'full_name' => '<b>Ahmad</b> Fauzi',
        ]));

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('Ahmad Fauzi', $data['full_name']);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_register_gagal_semua_field_kosong(): void
    {
        $response = $this->postJson($this->registerUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'nik',
            'family_card_number',
            'full_name',
            'whatsapp_number',
            'pin',
        ]);
    }

    public function test_prefill_gagal_tanpa_parameter_nik(): void
    {
        $response = $this->getJson($this->prefillUrl());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_register_response_json_bertipe_valid(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData());

        $data = $response->json('data');
        $this->assertIsBool($data['is_verified']);
        $this->assertIsString($data['nik']);
        $this->assertIsString($data['full_name']);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_nik_kalsel_valid_diterima(): void
    {
        $nikKalsel = ['6301012508900001', '6312999999999999', '6399000000000000'];

        foreach ($nikKalsel as $nik) {
            $response = $this->postJson($this->registerUrl(), $this->validData(['nik' => $nik]));
            $response->assertStatus(201);
        }
    }

    public function test_grup_nik_non_kalsel_ditolak(): void
    {
        $nikNonKalsel = ['3301012508900001', '5201012508900001', '1201012508900001'];

        foreach ($nikNonKalsel as $nik) {
            $response = $this->postJson($this->registerUrl(), $this->validData(['nik' => $nik]));
            $response->assertStatus(422);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_register_membuat_citizen_dalam_state_unverified(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData());

        $this->assertFalse($response->json('data.is_verified'));
        $this->assertDatabaseHas('citizens', [
            'nik' => '6371012508900001',
            'is_verified' => false,
        ]);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_double_register_dengan_nik_sama_ditangani(): void
    {
        $response1 = $this->postJson($this->registerUrl(), $this->validData());
        $response1->assertStatus(201);

        $response2 = $this->postJson($this->registerUrl(), $this->validData([
            'whatsapp_number' => '6289999999999',
        ]));

        $response2->assertStatus(422);
        $response2->assertJson([
            'status' => 'error',
            'message' => 'Kode verifikasi masih berlaku. Cek WhatsApp Anda atau tunggu 10 menit untuk kirim ulang.',
        ]);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_pin_tidak_dikembalikan_dalam_response(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData());

        $response->assertJsonMissing(['pin']);
        $this->assertArrayNotHasKey('pin', $response->json('data'));
    }

    public function test_xss_via_nama_disantitasi(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'full_name' => '<img src=x onerror=alert(1)> Ahmad Fauzi',
        ]));

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertStringContainsString('Ahmad Fauzi', $data['full_name']);
    }

    public function test_nik_mengandung_karakter_khusus_disantitasi(): void
    {
        $response = $this->postJson($this->registerUrl(), $this->validData([
            'nik' => "6301012508900001'; DROP TABLE citizens; --",
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('citizens', ['nik' => '6301012508900001']);
    }
}