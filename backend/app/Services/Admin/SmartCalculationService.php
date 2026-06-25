<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use Illuminate\Support\Facades\Log;

final readonly class SmartCalculationService
{
    /*
    |--------------------------------------------------------------------------
    | MAIN CALCULATION
    |--------------------------------------------------------------------------
    */

    public function calculate(AssistanceSubmission $submission): ?float
    {
        $criteria = $this->getCriteria($submission->program_id);

        if ($criteria === null) {
            Log::warning('SMART: Program not found or criteria empty', [
                'submission_id' => $submission->id,
                'program_id'    => $submission->program_id,
            ]);
            return null;
        }

        $inputs = collect($criteria['inputs'] ?? [])->filter(
            fn (array $input): bool => $this->isNormalizable($input)
        );

        if ($inputs->isEmpty()) {
            Log::info('SMART: No normalizable inputs configured', ['submission_id' => $submission->id]);
            return null;
        }

        $submissionData = $this->resolveSubmissionData($submission);
        $totalScore = 0.0;
        $processedCount = 0;

        foreach ($inputs as $input) {
            $sifat  = $input['sifat'] ?? 'benefit';
            $weight = (float) ($input['weight'] ?? 0) / 100;
            $type   = $input['type'] ?? 'number';

            if ($type === 'select') {
                $maxScore = collect($input['options'] ?? [])->max('score') ?: 100;
                $ideal    = (float) $maxScore;
            } else {
                $ideal = (float) ($input['ideal_value'] ?? 0);
            }

            if ($weight <= 0 || $ideal <= 0) {
                continue;
            }

            $value      = $this->getValue($submissionData, $input);
            $normalized = $this->normalizeWithIdeal($value, $ideal, $sifat, $type);

            $totalScore += $normalized * $weight;
            $processedCount++;
        }

        if ($processedCount === 0) {
            Log::warning('SMART: All inputs skipped due to invalid weight/ideal', [
                'submission_id' => $submission->id,
            ]);
            return null;
        }

        return round($totalScore, 4);
    }

    /*
    |--------------------------------------------------------------------------
    | DATA RESOLVER — MENANGANI JSON STRING & ARRAY
    |--------------------------------------------------------------------------
    */

    private function resolveSubmissionData(AssistanceSubmission $submission): array
    {
        $data = $submission->submission_data;

        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function getCriteria(string $programId): ?array
    {
        $program = AssistanceProgram::find($programId);

        if (!$program) {
            Log::warning('SMART: Program not found in database', [
                'program_id' => $programId,
            ]);
            return null;
        }

        if (!$program->criteria) {
            Log::warning('SMART: Program found but criteria is empty', [
                'program_id'   => $programId,
                'program_name' => $program->name,
            ]);
            return null;
        }

        $criteria = $program->criteria;
        return is_array($criteria) ? $criteria : json_decode($criteria, true);
    }

    private function isNormalizable(array $input): bool
    {
        $sifat = $input['sifat'] ?? 'none';
        if (!in_array($sifat, ['benefit', 'cost'], true)) {
            return false;
        }

        if (($input['weight'] ?? 0) <= 0) {
            return false;
        }

        $type = $input['type'] ?? '';
        if (!in_array($type, ['number', 'decimal', 'currency', 'select'], true)) {
            return false;
        }

        if ($type === 'select' && !empty($input['options'])) {
            return true;
        }

        return ($input['ideal_value'] ?? 0) > 0;
    }

    private function normalizeWithIdeal(float $value, float $ideal, string $sifat, string $type = 'number'): float
    {
        if ($ideal <= 0) {
            return 0.0;
        }

        if ($type === 'select') {
            return min($value / $ideal, 1.0);
        }

        if ($sifat === 'benefit') {
            return min($value / $ideal, 1.0);
        }

        return max(($ideal - $value) / $ideal, 0.0);
    }

    /*
    |--------------------------------------------------------------------------
    | VALUE EXTRACTION
    |--------------------------------------------------------------------------
    */

    private function getValue(array $data, array $input): float
    {
        $key = $input['key'];
        $raw = $data[$key] ?? 0;

        if (!array_key_exists($key, $data)) {
            Log::warning('SMART: Input key not found in submission_data', [
                'key'   => $key,
                'label' => $input['label'] ?? $key,
            ]);
        }

        if (($input['type'] ?? '') === 'select' && !empty($input['options'])) {
            $option = collect($input['options'])->firstWhere('value', $raw);
            return floatval($option['score'] ?? 0);
        }

        return floatval($raw);
    }
}