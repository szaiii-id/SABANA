<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AssistanceEvidence;
use App\Services\Ai\AiAnalysisService;
use App\Services\Ai\AnomalyDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class AnalyzeEvidenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 10;

    public function __construct(
        private readonly string $evidenceId,
        private readonly array $fields
    ) {}

    public function handle(AiAnalysisService $aiService, AnomalyDetectionService $anomalyService): void
    {
        $evidence = AssistanceEvidence::find($this->evidenceId);

        if (!$evidence) {
            Log::warning('AI Job: Evidence tidak ditemukan', ['id' => $this->evidenceId]);
            return;
        }

        $submission = $evidence->submission;
        if (!$submission) {
            Log::warning('AI Job: Submission tidak ditemukan', ['evidence_id' => $this->evidenceId]);
            return;
        }

        if (!$submission->program) {
            Log::warning('AI Job: Program tidak ditemukan', [
                'evidence_id'   => $this->evidenceId,
                'submission_id' => $submission->id,
            ]);
            return;
        }

        $program   = $submission->program;
        $aiConfig  = $program->ai_config;
        $citizen   = $submission->citizen;
        $inputData = $submission->submission_data ?? [];

        // ✅ Dynamic fields dari criteria program
        $criteriaInputs = $program->criteria['inputs'] ?? [];
        $criteriaFields = array_map(function (array $input) use ($inputData): array {
            return [
                'label' => $input['label'] ?? $input['key'],
                'key'   => $input['key'],
                'value' => (string) ($inputData[$input['key']] ?? ''),
            ];
        }, $criteriaInputs);

        // ✅ Field identitas HANYA untuk KTP & KK
        $identityFields = [];
        if (in_array($evidence->image_type, ['ktp', 'kk'])) {
            $identityFields = [
                ['label' => 'NIK',          'key' => 'nik',                'value' => $citizen?->nik ?? ''],
                ['label' => 'Nama Lengkap', 'key' => 'full_name',          'value' => $citizen?->full_name ?? ''],
                ['label' => 'Nomor KK',     'key' => 'family_card_number', 'value' => $citizen?->family_card_number ?? ''],
            ];
        }

        $allFields = array_merge($this->fields, $criteriaFields, $identityFields);

        if ($aiConfig[$evidence->image_type]['ocr'] ?? false) {
            $result = $aiService->analyzeEvidence($evidence->image_url, $allFields);
            $evidence->update(['ai_result' => $result]);

            Log::info('AI Job: OCR+NLP selesai', [
                'evidence_id' => $this->evidenceId,
                'success'     => $result['success'],
            ]);
        }

        $anomalies = $anomalyService->detect($submission);
        $data = $submission->submission_data ?? [];

        if (!empty($anomalies)) {
            $data['anomalies'] = $anomalies;
            Log::warning('Anomali terdeteksi', [
                'submission_id' => $submission->id,
                'count'         => count($anomalies),
            ]);
        } else {
            unset($data['anomalies']);
            Log::info('Tidak ada anomali', ['submission_id' => $submission->id]);
        }

        $submission->update(['submission_data' => $data]);
    }
}