<?php

declare(strict_types=1);

// ===== FILE: tests/Feature/Citizen/ReportControllerTest.php =====

namespace Tests\Feature\Citizen;

use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ReportControllerTest extends TestCase
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
    }

    // ===== URL & HEADERS =====

    private function whatsappUrl(): string
    {
        return '/api/v1/citizen/report/whatsapp';
    }

    private function emailUrl(): string
    {
        return '/api/v1/citizen/report/email';
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    private function validWhatsappData(): array
    {
        return ['pesan' => 'Ini laporan warga melalui WhatsApp.'];
    }

    private function validEmailData(): array
    {
        return [
            'nama' => 'Ahmad Fauzi',
            'email' => 'test@example.com',
            'subjek' => 'Laporan Bantuan',
            'pesan' => 'Ini laporan warga melalui email.',
        ];
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_send_whatsapp_berhasil(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), $this->validWhatsappData());

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Laporan berhasil diteruskan ke WhatsApp Admin.',
        ]);
    }

    public function test_send_email_berhasil(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), $this->validEmailData());

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Laporan berhasil dikirim ke Email Instansi.',
        ]);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_send_whatsapp_gagal_tanpa_token(): void
    {
        $response = $this->postJson($this->whatsappUrl(), $this->validWhatsappData());

        $response->assertStatus(401);
    }

    public function test_send_email_gagal_tanpa_token(): void
    {
        $response = $this->postJson($this->emailUrl(), $this->validEmailData());

        $response->assertStatus(401);
    }

    public function test_send_whatsapp_gagal_pesan_kosong(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), ['pesan' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pesan']);
    }

    public function test_send_whatsapp_gagal_pesan_kurang_10_karakter(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), ['pesan' => 'Pendek']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pesan']);
    }

    public function test_send_email_gagal_email_tidak_valid(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), array_merge($this->validEmailData(), [
                'email' => 'bukan-email',
            ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_send_email_gagal_subjek_kurang_5_karakter(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), array_merge($this->validEmailData(), [
                'subjek' => 'OK',
            ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subjek']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_send_whatsapp_pesan_tepat_10_karakter(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), ['pesan' => str_repeat('A', 10)]);

        $response->assertStatus(200);
    }

    public function test_send_whatsapp_pesan_tepat_1000_karakter(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), ['pesan' => str_repeat('A', 1000)]);

        $response->assertStatus(200);
    }

    public function test_send_email_pesan_tepat_2000_karakter(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), array_merge($this->validEmailData(), [
                'pesan' => str_repeat('A', 2000),
            ]));

        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_send_whatsapp_sanitasi_tag_html(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), ['pesan' => '<script>alert(1)</script> Laporan penting']);

        // strip_tags → "alert(1) Laporan penting" — 21 karakter, lolos
        $response->assertStatus(200);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_send_whatsapp_gagal_semua_field_kosong(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pesan']);
    }

    public function test_send_email_gagal_semua_field_kosong(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nama', 'email', 'subjek', 'pesan']);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_send_whatsapp_response_bertipe_valid(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), $this->validWhatsappData());

        $this->assertIsString($response->json('status'));
        $this->assertIsString($response->json('message'));
    }

    public function test_send_email_response_bertipe_valid(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), $this->validEmailData());

        $this->assertIsString($response->json('status'));
        $this->assertIsString($response->json('message'));
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_pesan_whatsapp_valid_diterima(): void
    {
        $pesanList = [
            str_repeat('A', 10),
            str_repeat('B', 500),
            str_repeat('C', 1000),
        ];

        foreach ($pesanList as $pesan) {
            $response = $this->withHeaders($this->authHeader())
                ->postJson($this->whatsappUrl(), ['pesan' => $pesan]);
            $response->assertStatus(200);
        }
    }

    public function test_grup_email_valid_diterima(): void
    {
        $emails = ['test@example.com', 'admin@sabana.go.id'];

        foreach ($emails as $email) {
            $response = $this->withHeaders($this->authHeader())
                ->postJson($this->emailUrl(), array_merge($this->validEmailData(), [
                    'email' => $email,
                ]));
            $response->assertStatus(200);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_kirim_whatsapp_lalu_email_tidak_saling_pengaruh(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), $this->validWhatsappData())
            ->assertStatus(200);

        $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), $this->validEmailData())
            ->assertStatus(200);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_kirim_whatsapp_dua_kali_cepat(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), $this->validWhatsappData())
            ->assertStatus(200);

        $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), ['pesan' => 'Laporan kedua warga.'])
            ->assertStatus(200);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_send_whatsapp_tidak_mengembalikan_data_sensitif(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->whatsappUrl(), $this->validWhatsappData());

        $response->assertJsonMissing(['pin']);
        $response->assertJsonMissing(['nik']);
    }

    public function test_send_email_tidak_mengembalikan_data_sensitif(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson($this->emailUrl(), $this->validEmailData());

        $response->assertJsonMissing(['pin']);
        $response->assertJsonMissing(['nik']);
    }
}