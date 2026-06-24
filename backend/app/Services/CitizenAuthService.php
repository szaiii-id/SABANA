<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class CitizenAuthService
{
    public function __construct(
        private readonly CitizenRepositoryInterface $citizenRepository,
    ) {}

    public function updatePin(Citizen $citizen, string $currentPin, string $newPin): bool
    {
        $key = 'change-pin:' . $citizen->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $minutes = ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages([
                'current_pin' => ["Terlalu banyak percobaan. Silakan coba lagi dalam {$minutes} menit."],
            ]);
        }

        if (!Hash::check($currentPin, $citizen->pin)) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages([
                'current_pin' => ['PIN lama tidak sesuai.'],
            ]);
        }

        RateLimiter::clear($key);

        return $this->citizenRepository->updatePin($citizen, Hash::make($newPin));
    }
}