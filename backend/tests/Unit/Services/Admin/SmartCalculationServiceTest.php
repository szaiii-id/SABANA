<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Services\Admin\SmartCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SmartCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SmartCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SmartCalculationService();
    }

    // ===== HELPER =====

    private function createProgram(array $criteria = []): AssistanceProgram
    {
        return AssistanceProgram::query()->create([
            'name' => 'Program SMART Test ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
            'criteria' => $criteria ?: [],
        ]);
    }

    private function createSubmission(string $programId, array $submissionData = []): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $programId,
            'registration_number' => 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => $submissionData,
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    private function validCriteria(): array
    {
        return [
            'inputs' => [
                [
                    'key' => 'usia',
                    'label' => 'Usia',
                    'sifat' => 'benefit',
                    'weight' => 50,
                    'type' => 'number',
                    'ideal_value' => 60,
                ],
                [
                    'key' => 'penghasilan',
                    'label' => 'Penghasilan',
                    'sifat' => 'cost',
                    'weight' => 50,
                    'type' => 'currency',
                    'ideal_value' => 3000000,
                ],
            ],
        ];
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_calculate_returns_score_for_valid_data(): void
    {
        $program = $this->createProgram($this->validCriteria());
        $submission = $this->createSubmission($program->id, [
            'usia' => 30,
            'penghasilan' => 1500000,
        ]);

        $score = $this->service->calculate($submission);

        $this->assertNotNull($score);
        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(1, $score);
    }

    public function test_calculate_benefit_max_score(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                [
                    'key' => 'usia',
                    'label' => 'Usia',
                    'sifat' => 'benefit',
                    'weight' => 100,
                    'type' => 'number',
                    'ideal_value' => 60,
                ],
            ],
        ]);
        $submission = $this->createSubmission($program->id, ['usia' => 60]);

        $score = $this->service->calculate($submission);

        $this->assertEquals(1.0, $score);
    }

    public function test_calculate_cost_zero_score(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                [
                    'key' => 'penghasilan',
                    'label' => 'Penghasilan',
                    'sifat' => 'cost',
                    'weight' => 100,
                    'type' => 'currency',
                    'ideal_value' => 1000000,
                ],
            ],
        ]);
        $submission = $this->createSubmission($program->id, ['penghasilan' => 1000000]);

        $score = $this->service->calculate($submission);

        $this->assertEquals(0.0, $score);
    }

    // ===== SAD PATH (3 test) =====

    public function test_calculate_returns_null_for_missing_program(): void
    {
        $program = $this->createProgram($this->validCriteria());
        $submission = $this->createSubmission($program->id, ['usia' => 30]);
        
        // Hapus program setelah submission dibuat
        $program->delete();

        $score = $this->service->calculate($submission);

        $this->assertNull($score);
    }

    public function test_calculate_returns_null_for_empty_criteria(): void
    {
        $program = $this->createProgram([]);
        $submission = $this->createSubmission($program->id, ['usia' => 30]);

        $score = $this->service->calculate($submission);

        $this->assertNull($score);
    }

    public function test_calculate_returns_null_for_no_normalizable_inputs(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                [
                    'key' => 'catatan',
                    'label' => 'Catatan',
                    'sifat' => 'none',
                    'weight' => 50,
                    'type' => 'text',
                ],
            ],
        ]);
        $submission = $this->createSubmission($program->id, ['catatan' => 'test']);

        $score = $this->service->calculate($submission);

        $this->assertNull($score);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_calculate_with_zero_weight_skipped(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                [
                    'key' => 'usia',
                    'label' => 'Usia',
                    'sifat' => 'benefit',
                    'weight' => 0,
                    'type' => 'number',
                    'ideal_value' => 60,
                ],
            ],
        ]);
        $submission = $this->createSubmission($program->id, ['usia' => 60]);

        $score = $this->service->calculate($submission);

        $this->assertNull($score);
    }

    public function test_calculate_with_select_type(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                [
                    'key' => 'pendidikan',
                    'label' => 'Pendidikan',
                    'sifat' => 'benefit',
                    'weight' => 100,
                    'type' => 'select',
                    'options' => [
                        ['value' => 'sd', 'score' => 80],
                        ['value' => 'smp', 'score' => 60],
                        ['value' => 'sma', 'score' => 40],
                    ],
                ],
            ],
        ]);
        $submission = $this->createSubmission($program->id, ['pendidikan' => 'smp']);

        $score = $this->service->calculate($submission);

        $this->assertEquals(0.75, $score);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_calculate_with_missing_key_in_data(): void
    {
        $program = $this->createProgram($this->validCriteria());
        $submission = $this->createSubmission($program->id, []);

        $score = $this->service->calculate($submission);

        $this->assertNotNull($score);
        $this->assertGreaterThan(0, $score);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_calculate_with_json_string_submission_data(): void
    {
        $program = $this->createProgram($this->validCriteria());
        $submission = $this->createSubmission($program->id, []);
        $submission->update(['submission_data' => json_encode(['usia' => 45, 'penghasilan' => 2000000])]);

        $score = $this->service->calculate($submission->fresh());

        $this->assertNotNull($score);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_calculate_returns_float_or_null(): void
    {
        $program = $this->createProgram($this->validCriteria());
        $submission = $this->createSubmission($program->id, ['usia' => 25, 'penghasilan' => 2500000]);

        $score = $this->service->calculate($submission);

        $this->assertTrue(is_float($score) || is_null($score));
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_calculate_cost_more_than_ideal(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                [
                    'key' => 'pengeluaran',
                    'label' => 'Pengeluaran',
                    'sifat' => 'cost',
                    'weight' => 100,
                    'type' => 'currency',
                    'ideal_value' => 500000,
                ],
            ],
        ]);
        $submission = $this->createSubmission($program->id, ['pengeluaran' => 1000000]);

        $score = $this->service->calculate($submission);

        $this->assertEquals(0.0, $score);
    }

    public function test_calculate_benefit_more_than_ideal_clamped(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                [
                    'key' => 'usia',
                    'label' => 'Usia',
                    'sifat' => 'benefit',
                    'weight' => 100,
                    'type' => 'number',
                    'ideal_value' => 40,
                ],
            ],
        ]);
        $submission = $this->createSubmission($program->id, ['usia' => 80]);

        $score = $this->service->calculate($submission);

        $this->assertEquals(1.0, $score);
    }

    // ===== SECURITY (1 test) =====

    public function test_calculate_does_not_throw_on_invalid_data_type(): void
    {
        $program = $this->createProgram($this->validCriteria());
        $submission = $this->createSubmission($program->id, ['usia' => 'abc', 'penghasilan' => 'def']);

        $score = $this->service->calculate($submission);

        $this->assertNotNull($score);
    }
}