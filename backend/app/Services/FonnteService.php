<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class FonnteService
{
    public function sendMessage(string $target, string $message): bool
    {
        $token = config('services.fonnte.token');

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target'      => $target,
                'message'     => $message,
                'countryCode' => '62',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FonnteService error', [
                'target'  => $target,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }
}