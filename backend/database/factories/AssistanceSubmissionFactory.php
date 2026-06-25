<?php

namespace Database\Factories;

use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\AssistanceProgram;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssistanceSubmissionFactory extends Factory
{
    protected $model = AssistanceSubmission::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'citizen_id' => Citizen::factory(), // Otomatis buat warga jika tidak diisi
            'program_id' => AssistanceProgram::factory(), // Otomatis buat program jika tidak diisi
            'registration_number' => 'REG-' . date('Y') . '-' . $this->faker->unique()->randomNumber(5, true),
            'regency_id' => '6301',
            'district_id' => '630101',
            'village_id' => '6301012001',
            'status' => 'pending',
            'submission_data' => ['pertanyaan_1' => 'jawaban_1'],
            'smart_score' => 0.0,
            'disbursement_method' => 'village_cash',
            'needs_data_update' => false,
        ];
    }
}