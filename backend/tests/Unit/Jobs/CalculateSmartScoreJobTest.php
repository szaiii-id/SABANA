<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\CalculateSmartScoreJob;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Services\Admin\SmartCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

final class CalculateSmartScoreJobTest extends TestCase
{
    use RefreshDatabase;

    private function createCitizen(): Citizen
    {
        $citizen = new Citizen();
        $citizen->nik = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        $citizen->family_card_number = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        $citizen->full_name = 'Test Citizen';
        $citizen->whatsapp_number = '6281234567890';
        $citizen->pin = bcrypt('123456');
        $citizen->save();
        return $citizen;
    }

    private function createProgram(array $criteria): AssistanceProgram
    {
        $program = new AssistanceProgram();
        $program->name = 'BLT Test';
        $program->description = 'Program test';
        $program->status = 'active';
        $program->criteria = $criteria;
        $program->save();
        return $program;
    }

    private function createSubmission(AssistanceProgram $program, string $status = 'pending'): AssistanceSubmission
    {
        $citizen = $this->createCitizen();

        $submission = new AssistanceSubmission();
        $submission->citizen_id = $citizen->id;
        $submission->program_id = $program->id;
        $submission->status = $status;
        $submission->registration_number = 'REG-' . uniqid();
        $submission->regency_id = '6301';
        $submission->district_id = '630101';
        $submission->village_id = '6301010001';
        $submission->submission_data = json_encode(['penghasilan' => 1000000, 'tanggungan' => 3]);
        $submission->disbursement_method = 'village_cash';
        $submission->save();
        return $submission;
    }

    private function validCriteria(): array
    {
        return [
            'ahp_weights' => ['penghasilan' => 1.0],
            'inputs'      => [['key' => 'penghasilan', 'label' => 'Penghasilan', 'type' => 'currency', 'sifat' => 'cost']],
        ];
    }

    // ===== [1] HAPPY PATH =====
    public function test_job_calculates_and_updates_score(): void
    {
        $program = $this->createProgram($this->validCriteria());
        $submission = $this->createSubmission($program);

        $job = new CalculateSmartScoreJob($submission->id);
        $job->handle(app(SmartCalculationService::class));

        $this->assertNotNull($submission->fresh()->saw_score);
    }

    // ===== [2] SAD PATH =====
    public function test_job_handles_not_found(): void
    {
        Log::spy();

        $job = new CalculateSmartScoreJob('00000000-0000-0000-0000-000000000000');
        $job->handle(app(SmartCalculationService::class));

        Log::shouldHaveReceived('warning')->once();
    }

    // ===== [3] EDGE CASE =====
    public function test_job_skips_without_criteria(): void
    {
        $program = $this->createProgram([]);
        $submission = $this->createSubmission($program);

        $job = new CalculateSmartScoreJob($submission->id);
        $job->handle(app(SmartCalculationService::class));

        $this->assertNull($submission->fresh()->smart_score);
    }
}