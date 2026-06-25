<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AssistanceSubmission;
use App\Models\AssistanceEvidence;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class AnomalyDetectionService
{
    public function __construct(
        private readonly AssistanceRepositoryInterface $repository
    ) {}

    /*
    |--------------------------------------------------------------------------
    | MAIN DETECTION
    |--------------------------------------------------------------------------
    */

    public function detect(AssistanceSubmission $submission): array
    {
        $anomalies = [];

        $doubleSubmit = $this->detectDoubleSubmit($submission);
        if ($doubleSubmit) $anomalies[] = $doubleSubmit;

        $duplicateNIK = $this->detectDuplicateNIK($submission);
        if ($duplicateNIK) $anomalies[] = $duplicateNIK;

        $duplicateKK = $this->detectDuplicateKK($submission);
        if ($duplicateKK) $anomalies[] = $duplicateKK;

        $ocrMismatch = $this->detectOcrMismatch($submission);
        if ($ocrMismatch) $anomalies = array_merge($anomalies, $ocrMismatch);

        $blurryDocument = $this->detectBlurryDocument($submission);
        if ($blurryDocument) $anomalies[] = $blurryDocument;

        $duplicatePhoto = $this->detectDuplicatePhoto($submission);
        if ($duplicatePhoto) $anomalies[] = $duplicatePhoto;

        $outlierValues = $this->detectOutlierValues($submission);
        if ($outlierValues) $anomalies = array_merge($anomalies, $outlierValues);

        $financialAnomalies = $this->detectFinancialAnomalies($submission);
        if ($financialAnomalies) $anomalies = array_merge($anomalies, $financialAnomalies);

        return $anomalies;
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR ANOMALIES
    |--------------------------------------------------------------------------
    */

    public function clearAnomaliesForRelatedSubmissions(AssistanceSubmission $deletedSubmission): void
    {
        $relatedSubmissions = AssistanceSubmission::where('citizen_id', $deletedSubmission->citizen_id)
            ->where('program_id', $deletedSubmission->program_id)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->whereNull('deleted_at')
            ->where('id', '!=', $deletedSubmission->id)
            ->get();

        foreach ($relatedSubmissions as $submission) {
            $anomalies = $this->detect($submission);
            $data = $submission->submission_data ?? [];

            if (empty($anomalies)) {
                unset($data['anomalies']);
            } else {
                $data['anomalies'] = $anomalies;
            }

            $submission->update(['submission_data' => $data]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 1. DOUBLE SUBMIT
    |--------------------------------------------------------------------------
    */

    private function detectDoubleSubmit(AssistanceSubmission $submission): ?array
    {
        $exists = AssistanceSubmission::where('citizen_id', $submission->citizen_id)
            ->where('program_id', $submission->program_id)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->whereNull('deleted_at')
            ->where('id', '!=', $submission->id)
            ->exists();

        return $exists ? [
            'type'     => 'double_submit',
            'severity' => 'high',
            'message'  => 'Warga sudah memiliki pengajuan aktif di program ini.',
        ] : null;
    }

    /*
    |--------------------------------------------------------------------------
    | 2. DUPLICATE NIK
    |--------------------------------------------------------------------------
    */

    private function detectDuplicateNIK(AssistanceSubmission $submission): ?array
    {
        $nik = $submission->submission_data['nik'] ?? null;
        if (!$nik) return null;

        $exists = AssistanceSubmission::where('citizen_id', '!=', $submission->citizen_id)
            ->where('submission_data->nik', $nik)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->whereNull('deleted_at')
            ->exists();

        return $exists ? [
            'type'     => 'duplicate_nik',
            'severity' => 'high',
            'message'  => 'NIK ini sudah digunakan oleh warga lain di pengajuan berbeda.',
        ] : null;
    }

    /*
    |--------------------------------------------------------------------------
    | 3. DUPLICATE KK
    |--------------------------------------------------------------------------
    */

    private function detectDuplicateKK(AssistanceSubmission $submission): ?array
    {
        $kkNumber = $submission->submission_data['family_card_number'] ?? null;
        if (!$kkNumber) return null;

        $exists = AssistanceSubmission::where('citizen_id', '!=', $submission->citizen_id)
            ->where('submission_data->family_card_number', $kkNumber)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->whereNull('deleted_at')
            ->exists();

        return $exists ? [
            'type'     => 'duplicate_kk',
            'severity' => 'high',
            'message'  => 'KK ini sudah digunakan oleh warga lain dengan NIK berbeda.',
        ] : null;
    }

    /*
    |--------------------------------------------------------------------------
    | 4. OCR MISMATCH — PER DOKUMEN
    |--------------------------------------------------------------------------
    */

    private function detectOcrMismatch(AssistanceSubmission $submission): array
    {
        $anomalies = [];
        $criteria = $submission->program->criteria['inputs'] ?? [];
        $criteriaKeys = array_column($criteria, 'key');

        $identityFields = ['nik', 'full_name', 'family_card_number'];
        $ktpFields = ['nik', 'full_name'];
        $kkFields  = ['nik', 'full_name', 'family_card_number'];

        foreach ($submission->evidences as $evidence) {
            $aiResult = $evidence->ai_result;
            if (!$aiResult || !($aiResult['success'] ?? false)) continue;

            $validKeys = match ($evidence->image_type) {
                'ktp' => $ktpFields,
                'kk'  => $kkFields,
                default => $criteriaKeys,
            };

            foreach ($aiResult['matches'] ?? [] as $match) {
                $fieldKey = $match['field'] ?? $match['key'] ?? '';
                if (!in_array($fieldKey, $validKeys)) continue;
                if (($match['match_status'] ?? '') === 'cocok' && ($match['match_score'] ?? 0) >= 90) continue;

                $anomalies[] = [
                    'type'     => 'ocr_mismatch',
                    'severity' => 'medium',
                    'message'  => "Data {$match['label']} di dokumen {$evidence->image_type} tidak sesuai dengan input warga.",
                    'extra'    => [
                        'image_type'  => $evidence->image_type,
                        'field'       => $match['label'],
                        'input_value' => $match['input_value'] ?? null,
                        'ocr_value'   => $match['ocr_value'] ?? null,
                        'match_score' => $match['match_score'] ?? 0,
                    ],
                ];
            }
        }

        return array_values(array_unique($anomalies, SORT_REGULAR));
    }

    /*
    |--------------------------------------------------------------------------
    | 5. BLURRY DOCUMENT
    |--------------------------------------------------------------------------
    */

    private function detectBlurryDocument(AssistanceSubmission $submission): ?array
    {
        foreach ($submission->evidences as $evidence) {
            $aiResult = $evidence->ai_result;
            if (!$aiResult || !($aiResult['success'] ?? false)) continue;

            if (strlen($aiResult['ocr_text'] ?? '') < 10) {
                return [
                    'type'     => 'blurry_document',
                    'severity' => 'low',
                    'message'  => "Dokumen {$evidence->image_type} tidak terbaca dengan jelas oleh AI.",
                ];
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | 6. DUPLICATE PHOTO
    |--------------------------------------------------------------------------
    */

    private function detectDuplicatePhoto(AssistanceSubmission $submission): ?array
    {
        $currentUrls = $submission->evidences->pluck('image_url')->toArray();
        if (empty($currentUrls)) return null;

        $exists = AssistanceEvidence::whereNot('submission_id', $submission->id)
            ->whereHas('submission', fn ($q) => $q->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)->whereNull('deleted_at'))
            ->whereIn('image_url', $currentUrls)
            ->exists();

        return $exists ? [
            'type'     => 'duplicate_photo',
            'severity' => 'high',
            'message'  => 'Foto yang sama ditemukan di pengajuan lain.',
        ] : null;
    }

    /*
    |--------------------------------------------------------------------------
    | 7. OUTLIER NUMERIK (DYNAMIC)
    |--------------------------------------------------------------------------
    */

    private function detectOutlierValues(AssistanceSubmission $submission): array
    {
        $anomalies = [];
        $data = $submission->submission_data ?? [];
        $criteria = $submission->program->criteria['inputs'] ?? [];

        foreach ($criteria as $input) {
            if (!in_array($input['type'] ?? '', ['number', 'decimal', 'currency'])) continue;

            $key = $input['key'];
            $value = $this->parseNumber($data[$key] ?? null);
            if (!$value || $value <= 0) continue;

            $avg = AssistanceSubmission::where('program_id', $submission->program_id)
                ->where('id', '!=', $submission->id)
                ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
                ->whereNull('deleted_at')
                ->whereNotNull("submission_data->{$key}")
                ->avg(DB::raw("CAST(submission_data->>'{$key}' AS DECIMAL)"));

            $avg = (float) $avg; // ← TAMBAHKAN INI

            if ($avg && $avg > 0 && $value > $avg * 3) {
                $anomalies[] = [
                    'type'     => 'outlier_value',
                    'severity' => 'medium',
                    'message'  => "Nilai {$input['label']} (" . number_format($value) . ") jauh di atas rata-rata (" . number_format($avg) . ").",
                    'extra'    => ['field' => $key, 'value' => $value, 'average' => round($avg)],
                ];
            }
        }

        return $anomalies;
    }
    
    /*
    |--------------------------------------------------------------------------
    | 8. FINANCIAL ANOMALY (CONDITIONAL)
    |--------------------------------------------------------------------------
    */

    private function detectFinancialAnomalies(AssistanceSubmission $submission): array
    {
        $anomalies = [];
        $data = $submission->submission_data ?? [];
        $criteria = $submission->program->criteria['inputs'] ?? [];

        $currencyFields = array_values(array_filter($criteria, fn ($i) => ($i['type'] ?? '') === 'currency'));
        if (count($currencyFields) < 2) return [];

        $values = [];
        foreach ($currencyFields as $field) {
            $val = $this->parseNumber($data[$field['key']] ?? null);
            if ($val !== null) {
                $values[] = ['key' => $field['key'], 'label' => $field['label'] ?? $field['key'], 'value' => $val];
            }
        }

        if (count($values) >= 2 && $values[1]['value'] > $values[0]['value']) {
            $anomalies[] = [
                'type'     => 'expense_exceeds_income',
                'severity' => 'medium',
                'message'  => "{$values[1]['label']} (Rp " . number_format($values[1]['value']) . ") melebihi {$values[0]['label']} (Rp " . number_format($values[0]['value']) . ").",
            ];
        }

        return $anomalies;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function parseNumber(mixed $value): ?float
    {
        if (is_numeric($value)) return floatval($value);
        return null;
    }
}