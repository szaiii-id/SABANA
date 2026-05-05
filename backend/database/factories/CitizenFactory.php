<?php

namespace Database\Factories;

use App\Models\Citizen;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CitizenFactory extends Factory
{
    protected $model = Citizen::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'nik' => $this->faker->numerify('6301############'), // 16 digit NIK
            'family_card_number' => $this->faker->numerify('6301############'),
            'full_name' => $this->faker->name(),
            'whatsapp_number' => $this->faker->phoneNumber(),
            'pin' => bcrypt('123456'), // PIN Default
            'is_verified' => true,
        ];
    }
}