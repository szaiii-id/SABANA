<?php

namespace App\Services;

use App\Repositories\Contracts\CitizenRepositoryInterface;

class CitizenProfileService
{
    public function __construct(private CitizenRepositoryInterface $citizenRepository) {}

    public function updateProfile(object $citizen, array $data)
    {
        return $this->citizenRepository->update($citizen->id, $data);
    }
}