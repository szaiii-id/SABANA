<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FonnteService
{
    public function sendMessage(string $target, string $message): bool
    {
        $token = config('services.fonnte.token');

        $response = Http::withHeaders([
            'Authorization' => $token
        ])->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62',
        ]);

        return $response->successful();
    }
}