<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

final class AspirasiApiTest extends TestCase
{
    /**
     * Test validasi input yang ketat pada endpoint kontak.
     */
    public function test_it_validates_strict_input_requirements(): void
    {
        $response = $this->postJson('/api/v1/kontak', [
            'nama' => 'Jainudin',
            'email' => 'bukan-email',
            'subjek' => 'Test',
            'pesan' => 'Pesan valid 10 karakter'
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test konsistensi struktur JSON yang dikirim ke frontend.
     */
    public function test_it_maintains_consistent_json_structure_for_frontend(): void
    {
        // Mocking API external (Brevo) agar test berjalan cepat tanpa internet
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);

        $response = $this->postJson('/api/v1/kontak', [
            'nama' => 'Jainudin',
            'email' => 'akhmad@uniska.id',
            'subjek' => 'Layanan',
            'pesan' => 'Mohon tingkatkan layanan publik'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message']);
    }
}