<?php 
namespace App\Services;

use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Exception;

class CitizenAuthService
{
    public function __construct(private CitizenRepositoryInterface $citizenRepository) {}

    public function updatePin(object $citizen, string $currentPin, string $newPin): bool
    {
        if (!Hash::check($currentPin, $citizen->pin)) {
            throw new Exception('PIN lama tidak sesuai.');
        }

        return $this->citizenRepository->updatePin($citizen, Hash::make($newPin));
    }
}