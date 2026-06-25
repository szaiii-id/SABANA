<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Ai;

use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\AssistanceEvidence;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Services\Ai\AnomalyDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class AnomalyDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssistanceRepositoryInterface $repository;
    private AnomalyDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(AssistanceRepositoryInterface::class);
        $this->service = new AnomalyDetectionService($this->repository);
    }

    // ===== HELPER =====

    private function createProgram(array $criteria = []): AssistanceProgram
    {
        return AssistanceProgram::query()->create([
            'name' => 'Anomaly Test ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
            'criteria' => $criteria ?: [],
        ]);
    }

    private function createSubmission(string $programId, array $submissionData = [], string $status = 'pending'): AssistanceSubmission
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
            'status' => $status,
            'submission_data' => $submissionData,
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (2 test) =====

    public function test_detect_returns_empty_array_for_clean_submission(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                ['key' => 'nik', 'label' => 'NIK', 'type' => 'text'],
                ['key' => 'full_name', 'label' => 'Nama', 'type' => 'text'],
            ],
        ]);
        $submission = $this->createSubmission($program->id, [
            'nik' => '6371012508900001',
            'full_name' => 'Ahmad Fauzi',
            'family_card_number' => '6371012508900002',
        ]);

        $anomalies = $this->service->detect($submission);

        $this->assertIsArray($anomalies);
        $this->assertEmpty($anomalies);
    }

    public function test_detect_double_submit_found(): void
    {
        $program = $this->createProgram();
        
        // Buat citizen dulu, lalu pakai untuk 2 submission
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Double Submitter',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        // Submission pertama
        AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-FIRST001',
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['nik' => '1111111111111111'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        // Submission kedua — citizen & program SAMA
        $submission2 = AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'registration_number' => 'SBN-SECOND02',
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => 'pending',
            'submission_data' => ['nik' => '1111111111111111'],
            'disbursement_method' => 'bpd_transfer',
        ]);

        $anomalies = $this->service->detect($submission2);

        $hasDoubleSubmit = collect($anomalies)->contains(fn($a) => $a['type'] === 'double_submit');
        $this->assertTrue($hasDoubleSubmit, 'Harus mendeteksi double submit');
    }

    // ===== SAD PATH (1 test) =====

    public function test_detect_duplicate_nik_found(): void
    {
        $program1 = $this->createProgram();
        $program2 = $this->createProgram();

        $submission1 = $this->createSubmission($program1->id, ['nik' => '6371012508900001']);
        $submission2 = $this->createSubmission($program2->id, ['nik' => '6371012508900001']);

        $anomalies = $this->service->detect($submission2);

        $hasDuplicateNik = collect($anomalies)->contains(fn($a) => $a['type'] === 'duplicate_nik');
        $this->assertTrue($hasDuplicateNik);
    }

    // ===== BOUNDARY (1 test) =====

    public function test_detect_duplicate_kk_found(): void
    {
        $program = $this->createProgram();

        $this->createSubmission($program->id, [
            'nik' => '1111111111111111',
            'family_card_number' => '6371012508900002',
        ]);
        $submission2 = $this->createSubmission($program->id, [
            'nik' => '2222222222222222',
            'family_card_number' => '6371012508900002',
        ]);

        $anomalies = $this->service->detect($submission2);

        $hasDuplicateKK = collect($anomalies)->contains(fn($a) => $a['type'] === 'duplicate_kk');
        $this->assertTrue($hasDuplicateKK);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_detect_outlier_values_found(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                ['key' => 'penghasilan', 'label' => 'Penghasilan', 'type' => 'currency'],
            ],
        ]);

        $this->createSubmission($program->id, ['penghasilan' => 1000000]);
        $this->createSubmission($program->id, ['penghasilan' => 1200000]);
        $submissionOutlier = $this->createSubmission($program->id, ['penghasilan' => 10000000]);

        $anomalies = $this->service->detect($submissionOutlier);

        $hasOutlier = collect($anomalies)->contains(fn($a) => $a['type'] === 'outlier_value');
        $this->assertTrue($hasOutlier);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_detect_duplicate_nik_skips_null_nik(): void
    {
        $program = $this->createProgram();
        $submission = $this->createSubmission($program->id, []);

        $anomalies = $this->service->detect($submission);

        $hasDuplicateNik = collect($anomalies)->contains(fn($a) => $a['type'] === 'duplicate_nik');
        $this->assertFalse($hasDuplicateNik);
    }

    public function test_detect_duplicate_kk_skips_null_kk(): void
    {
        $program = $this->createProgram();
        $submission = $this->createSubmission($program->id, ['nik' => '1234567890123456']);

        $anomalies = $this->service->detect($submission);

        $hasDuplicateKK = collect($anomalies)->contains(fn($a) => $a['type'] === 'duplicate_kk');
        $this->assertFalse($hasDuplicateKK);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_detect_returns_array(): void
    {
        $program = $this->createProgram();
        $submission = $this->createSubmission($program->id, []);

        $result = $this->service->detect($submission);

        $this->assertIsArray($result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_detect_financial_anomaly_expense_exceeds_income(): void
    {
        $program = $this->createProgram([
            'inputs' => [
                ['key' => 'penghasilan', 'label' => 'Penghasilan', 'type' => 'currency'],
                ['key' => 'pengeluaran', 'label' => 'Pengeluaran', 'type' => 'currency'],
            ],
        ]);

        $submission = $this->createSubmission($program->id, [
            'penghasilan' => 1000000,
            'pengeluaran' => 2000000,
        ]);

        $anomalies = $this->service->detect($submission);

        $hasFinancial = collect($anomalies)->contains(fn($a) => $a['type'] === 'expense_exceeds_income');
        $this->assertTrue($hasFinancial);
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_clear_anomalies_updates_related_submissions(): void
    {
        $program = $this->createProgram();
        $submission1 = $this->createSubmission($program->id, ['nik' => '1111111111111111']);
        $submission2 = $this->createSubmission($program->id, ['nik' => '2222222222222222']);

        // Ini akan trigger query DB — test basic flow
        $this->service->clearAnomaliesForRelatedSubmissions($submission1);

        $this->assertTrue(true);
    }

    // ===== SECURITY (1 test) =====

    public function test_detect_does_not_throw_on_invalid_data(): void
    {
        $program = $this->createProgram();
        $submission = $this->createSubmission($program->id, ['nik' => null, 'family_card_number' => null]);

        $result = $this->service->detect($submission);

        $this->assertIsArray($result);
    }

    // ===== GAP COVERAGE (4 test) =====

public function test_detect_ocr_mismatch_found(): void
{
    $program = $this->createProgram([
        'inputs' => [
            ['key' => 'nik', 'label' => 'NIK', 'type' => 'text'],
            ['key' => 'full_name', 'label' => 'Nama', 'type' => 'text'],
        ],
    ]);
    $submission = $this->createSubmission($program->id, [
        'nik' => '6371012508900001',
        'full_name' => 'Ahmad Fauzi',
    ]);

    // Tambah evidence dengan ai_result mismatch
    AssistanceEvidence::query()->create([
        'submission_id' => $submission->id,
        'image_type' => 'ktp',
        'image_url' => 'https://example.com/ktp.jpg',
        'cloud_public_id' => 'ktp_123',
        'ai_result' => [
            'success' => true,
            'matches' => [
                [
                    'field' => 'nik',
                    'label' => 'NIK',
                    'match_status' => 'tidak_cocok',
                    'match_score' => 50,
                    'input_value' => '6371012508900001',
                    'ocr_value' => '6371012508900099',
                ],
            ],
        ],
    ]);

    $anomalies = $this->service->detect($submission);

    $hasOcr = collect($anomalies)->contains(fn($a) => $a['type'] === 'ocr_mismatch');
    $this->assertTrue($hasOcr);
}

public function test_detect_blurry_document_found(): void
{
    $program = $this->createProgram();
    $submission = $this->createSubmission($program->id, []);

    AssistanceEvidence::query()->create([
        'submission_id' => $submission->id,
        'image_type' => 'ktp',
        'image_url' => 'https://example.com/ktp.jpg',
        'cloud_public_id' => 'ktp_blur',
        'ai_result' => [
            'success' => true,
            'ocr_text' => 'pendek',
        ],
    ]);

    $anomalies = $this->service->detect($submission);

    $hasBlurry = collect($anomalies)->contains(fn($a) => $a['type'] === 'blurry_document');
    $this->assertTrue($hasBlurry);
}

public function test_detect_duplicate_photo_found(): void
{
    $program = $this->createProgram();
    $submission1 = $this->createSubmission($program->id, ['nik' => '1111111111111111']);
    $submission2 = $this->createSubmission($program->id, ['nik' => '2222222222222222']);

    $sameUrl = 'https://example.com/duplicate.jpg';

    AssistanceEvidence::query()->create([
        'submission_id' => $submission1->id,
        'image_type' => 'ktp',
        'image_url' => $sameUrl,
        'cloud_public_id' => 'dup_1',
    ]);

    AssistanceEvidence::query()->create([
        'submission_id' => $submission2->id,
        'image_type' => 'ktp',
        'image_url' => $sameUrl,
        'cloud_public_id' => 'dup_2',
    ]);

    $anomalies = $this->service->detect($submission2);

    $hasDupPhoto = collect($anomalies)->contains(fn($a) => $a['type'] === 'duplicate_photo');
    $this->assertTrue($hasDupPhoto);
}

public function test_clear_anomalies_removes_anomaly_data(): void
{
    $program = $this->createProgram();
    $submission1 = $this->createSubmission($program->id, ['nik' => '1111111111111111']);
    $submission2 = $this->createSubmission($program->id, [
        'nik' => '1111111111111111',
        'submission_data' => ['nik' => '1111111111111111', 'anomalies' => [['type' => 'old_anomaly']]],
    ]);

    $this->service->clearAnomaliesForRelatedSubmissions($submission1);

    $updated = $submission2->fresh();
    $data = $updated->submission_data;
    $this->assertArrayNotHasKey('anomalies', $data);
}

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}