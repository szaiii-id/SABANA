<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AspirasiService;
use Illuminate\Support\Facades\Http;

final class AspirasiServiceTest extends TestCase
{
    /**
     * Pastikan payload dikirim dengan header yang benar ke Brevo.
     */
    public function test_it_sends_complete_payload_with_proper_headers_to_brevo(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);

        $payload = [
            'nama' => 'Akhmad Jainudin',
            'email' => 'akhmad@uniska.id',
            'subjek' => 'Infrastruktur',
            'pesan' => 'Jalanan depan kampus rusak parah.'
        ];

        (new AspirasiService())->prosesDanKirimAspirasi($payload);

        Http::assertSent(function ($request) use ($payload) {
            return $request->hasHeader('api-key') &&
                   $request['subject'] === 'Aspirasi Baru: ' . $payload['subjek'];
        });
    }

    /**
     * Pastikan aplikasi tidak crash dan melempar Exception saat API Brevo bermasalah.
     */
    public function test_it_handles_api_timeout_gracefully(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response([], 500)]);

        $this->expectException(\Exception::class);
        
        (new AspirasiService())->prosesDanKirimAspirasi([
            'nama' => 'T', 
            'email' => 't@t.com', 
            'subjek' => 'T', 
            'pesan' => '1234567890'
        ]);
    }
}