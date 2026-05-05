<?php

namespace Database\Factories;

use App\Models\AssistanceProgram;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssistanceProgramFactory extends Factory
{
    protected $model = AssistanceProgram::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'criteria' => ['Warga Banua', 'Memiliki KTP'], // Data JSON
            'is_active' => true, // Secara default aktif
        ];
    }
}