<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

final class FonnteServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set token default untuk semua test agar tidak null
        Config::set('services.fonnte.token', 'test-token-secret');
    }

    /**
     * [HAPPY PATH]
     * Memastikan pesan terkirim dengan benar saat API merespons sukses (200 OK).
     */
    public function test_it_sends_whatsapp_message_successfully(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response(['status' => true], 200)
        ]);

        $service = new FonnteService();
        $result = $service->sendMessage('08123456789', 'Halo Mas Akhmad!');

        $this->assertTrue($result);

        // Memastikan payload yang dikirim ke Fonnte sesuai spesifikasi dokumentasi mereka
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'test-token-secret') &&
                   $request['target'] === '08123456789' &&
                   $request['message'] === 'Halo Mas Akhmad!' &&
                   $request['countryCode'] === '62';
        });
    }

    /**
     * [NEGATIVE PATH]
     * Memastikan return false jika nomor target salah atau API merespons gagal (422).
     */
    public function test_it_returns_false_when_target_number_is_invalid(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response([
                'status' => false,
                'reason' => 'invalid target'
            ], 422)
        ]);

        $service = new FonnteService();
        $result = $service->sendMessage('invalid-number', 'Test');

        $this->assertFalse($result);
    }

    /**
     * [SECURITY/AUTH CASE]
     * Memastikan sistem menangani kondisi Token Unauthorized (401).
     */
    public function test_it_returns_false_when_authentication_fails(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response([
                'status' => false,
                'reason' => 'unauthorized'
            ], 401)
        ]);

        $service = new FonnteService();
        $result = $service->sendMessage('08123456789', 'Cek Token');

        $this->assertFalse($result);
    }

    /**
     * [EDGE CASE - NETWORK]
     * Memastikan aplikasi tidak crash saat terjadi Request Timeout.
     */
    public function test_it_handles_network_timeout_gracefully(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response([], 408)
        ]);

        $service = new FonnteService();
        $result = $service->sendMessage('08123456789', 'Test Delay');

        $this->assertFalse($result);
    }

    /**
     * [EDGE CASE - SERVER ERROR]
     * Memastikan penanganan jika Server Fonnte sedang down (500 atau 503).
     */
    public function test_it_returns_false_when_fonnte_server_is_down(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response(['error' => 'Internal Server Error'], 500)
        ]);

        $service = new FonnteService();
        $result = $service->sendMessage('08123456789', 'Test Server Down');

        $this->assertFalse($result);
    }

    /**
     * [INPUT INTEGRITY]
     * Memastikan pesan kosong tetap diproses (atau sesuai kebijakan bisnis Mas).
     */
    public function test_it_can_handle_empty_message_string(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response(['status' => true], 200)
        ]);

        $service = new FonnteService();
        $result = $service->sendMessage('08123456789', '');

        $this->assertTrue($result);
    }
}