<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AspirasiService
{
    // --- FUNGSI AWAL MAS (JANGAN DIUBAH AGAR ASPIRASI TIDAK ERROR) ---
    public function prosesDanKirimAspirasi(array $payload)
    {
        $apiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS');

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => ['name' => 'SABANA Admin', 'email' => $senderEmail],
            'to' => [['email' => $senderEmail, 'name' => 'Aspirasi Sabana']],
            'subject' => 'Aspirasi Baru: ' . $payload['subjek'],
            'htmlContent' => view('emails.aspirasi', ['data' => $payload])->render(),
        ]);

        if ($response->failed()) {
            Log::error('Brevo API Error: ' . $response->body());
            throw new \Exception('Gagal mengirim email via API');
        }

        return true;
    }

    // --- FUNGSI BARU KHUSUS UNTUK LAPOR WARGA (AUTO-REPLY) ---
    public function kirimBalasanOtomatis(array $payload, string $targetEmail, string $targetName)
    {
        $apiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS');

        return Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => ['name' => 'SABANA Admin', 'email' => $senderEmail],
            'to' => [['email' => $targetEmail, 'name' => $targetName]],
            'subject' => 'Konfirmasi Laporan: ' . $payload['subjek'],
            'htmlContent' => "Halo {$targetName}, laporan Anda telah kami terima. Terima kasih.",
        ]);
    }
}