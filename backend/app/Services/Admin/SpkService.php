<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class SpkService
{
    /**
     * Ambil daftar program aktif untuk halaman SPK.
     */
    public function getActivePrograms(?string $search = null): Collection 
    {
        $query = AssistanceProgram::query()
            ->withCount('submissions')
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->whereNull('deleted_at');

        if ($search) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        return $query->get(['id', 'name', 'description', 'quota_total', 'criteria', 'status', 'banner_url']);
    }

    /**
     * Ambil data lengkap SPK untuk satu program.
     */
    public function getSpkData(string $programId, array $filters = []): array
    {
        $program = AssistanceProgram::withTrashed()->find($programId);

        if (!$program) {
            throw new \RuntimeException('Program tidak ditemukan.');
        }

        $criteriaInputs = $program->criteria['inputs'] ?? [];
        $smartInputs = $this->filterSmartInputs($criteriaInputs);

        if (empty($smartInputs)) {
            throw new \RuntimeException('Program tidak memiliki kriteria SMART.');
        }

        $query = AssistanceSubmission::query()
            ->with(['citizen', 'program'])
            ->where('program_id', $programId)
            ->whereNull('deleted_at');

        if (!empty($filters['village_id'])) {
            $query->where('village_id', $filters['village_id']);
        } elseif (!empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        } elseif (!empty($filters['regency_id'])) {
            $query->where('regency_id', $filters['regency_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $submissions = $query->get();

        $decisionMatrix = $this->buildDecisionMatrix($submissions, $smartInputs);
        $normalizedMatrix = $this->buildNormalizedMatrix($decisionMatrix, $smartInputs);
        $weightedMatrix = $this->buildWeightedMatrix($normalizedMatrix, $smartInputs);
        $ranking = $this->buildRanking($weightedMatrix);
        $summary = $this->buildSummary($weightedMatrix, $program->quota_total);

        return [
            'program' => [
                'id'          => $program->id,
                'name'        => $program->name,
                'quota_total' => $program->quota_total,
            ],
            'criteria'          => $smartInputs,
            'decision_matrix'   => $decisionMatrix,
            'normalized_matrix' => $normalizedMatrix,
            'weighted_matrix'   => $weightedMatrix,
            'ranking'           => $ranking,
            'summary'           => $summary,
            'total_alternatives' => $submissions->count(),
        ];
    }

    private function buildDecisionMatrix(Collection $submissions, array $criteria): array
    {
        return $submissions->map(function (AssistanceSubmission $sub) use ($criteria): array {
            $data = $sub->submission_data ?? [];
            $row = [
                'id'     => $sub->id,
                'nik'    => $sub->citizen->nik ?? '-',
                'name'   => $sub->citizen->full_name ?? '-',
                'status' => $sub->status,
            ];

            foreach ($criteria as $c) {
                $key = $c['key'];
                $raw = $data[$key] ?? 0;

                if (($c['type'] ?? '') === 'select' && !empty($c['options'])) {
                    $option = collect($c['options'])->firstWhere('value', $raw);
                    $row[$key] = [
                        'raw'     => $raw,
                        'value'   => (float) ($option['score'] ?? 0),
                        'display' => (string) $raw,
                    ];
                } else {
                    $row[$key] = [
                        'raw'     => $raw,
                        'value'   => (float) $raw,
                        'display' => $c['type'] === 'currency'
                            ? 'Rp ' . number_format((float) $raw, 0, ',', '.')
                            : (string) $raw,
                    ];
                }
            }

            return $row;
        })->toArray();
    }

    private function buildNormalizedMatrix(array $decisionMatrix, array $criteria): array
    {
        return array_map(function (array $row) use ($criteria): array {
            $normalized = [
                'id'     => $row['id'],
                'name'   => $row['name'],
                'status' => $row['status'],
            ];

            foreach ($criteria as $c) {
                $key    = $c['key'];
                $raw    = $row[$key]['value'] ?? 0;
                $ideal  = $this->getIdealValue($c);
                $sifat  = $c['sifat'] ?? 'benefit';
                $type   = $c['type'] ?? 'number';

                $normalized[$key] = $this->normalize($raw, $ideal, $sifat, $type);
                $normalized[$key . '_raw'] = (float) $raw;
            }

            return $normalized;
        }, $decisionMatrix);
    }

    private function buildWeightedMatrix(array $normalizedMatrix, array $criteria): array
    {
        return array_map(function (array $row) use ($criteria): array {
            $weighted = [
                'id'     => $row['id'],
                'name'   => $row['name'],
                'status' => $row['status'],
            ];

            $total = 0.0;

            foreach ($criteria as $c) {
                $key    = $c['key'];
                $weight = (float) ($c['weight'] ?? 0) / 100;
                $norm   = $row[$key];
                $score  = $norm * $weight;

                $weighted[$key . '_weighted'] = round($score, 4);
                $weighted[$key . '_norm'] = $norm;
                $weighted[$key . '_weight'] = round($weight, 4);
                $total += $score;
            }

            $weighted['total'] = round($total, 4);

            return $weighted;
        }, $normalizedMatrix);
    }

    private function buildRanking(array $weightedMatrix): array
    {
        usort($weightedMatrix, fn(array $a, array $b): int => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));

        $thresholds = config('sabana.smart.thresholds', [
            'highly_recommended' => 0.70,
            'recommended'        => 0.50,
            'considered'         => 0.30,
        ]);

        return array_values(array_map(function (array $row, int $index) use ($thresholds): array {
            $score = $row['total'];

            return [
                'rank'           => $index + 1,
                'id'             => $row['id'],
                'name'           => $row['name'],
                'total'          => $score,
                'status'         => $row['status'],
                'recommendation' => $this->getRecommendation($score, $thresholds),
            ];
        }, $weightedMatrix, array_keys($weightedMatrix)));
    }

    private function buildSummary(array $weightedMatrix, ?int $quota): array
    {
        $scores = array_column($weightedMatrix, 'total');
        $count  = count($scores);

        if ($count === 0) {
            return [
                'total'     => 0,
                'quota'     => $quota ?? 0,
                'highest'   => 0,
                'lowest'    => 0,
                'average'   => 0,
                'eligible'  => 0,
            ];
        }

        $eligible = count(array_filter($scores, fn(float $s): bool => $s >= 0.50));

        return [
            'total'     => $count,
            'quota'     => $quota ?? 0,
            'highest'   => round(max($scores), 4),
            'lowest'    => round(min($scores), 4),
            'average'   => round(array_sum($scores) / $count, 4),
            'eligible'  => $eligible,
        ];
    }

    private function filterSmartInputs(array $inputs): array
    {
        return array_values(array_filter($inputs, function (array $input): bool {
            $sifat  = $input['sifat'] ?? 'none';
            $weight = (float) ($input['weight'] ?? 0);
            $type   = $input['type'] ?? '';

            if (!in_array($sifat, ['benefit', 'cost'], true)) return false;
            if ($weight <= 0) return false;
            if (!in_array($type, ['number', 'decimal', 'currency', 'select'], true)) return false;

            return true;
        }));
    }

    private function getIdealValue(array $criteria): float
    {
        if (($criteria['type'] ?? '') === 'select') {
            $options = $criteria['options'] ?? [];
            $max = !empty($options) ? collect($options)->max('score') : 100;
            return (float) ($max ?: 100);
        }

        return (float) ($criteria['ideal_value'] ?? 0);
    }

    private function normalize(float $value, float $ideal, string $sifat, string $type): float
    {
        if ($ideal <= 0) return 0.0;

        if ($type === 'select') {
            return round(min($value / $ideal, 1.0), 4);
        }

        if ($sifat === 'benefit') {
            return round(min($value / $ideal, 1.0), 4);
        }

        return round(max(($ideal - $value) / $ideal, 0.0), 4);
    }

    private function getRecommendation(float $score, array $thresholds): array
    {
        if ($score >= $thresholds['highly_recommended']) {
            return ['label' => 'Sangat Direkomendasikan', 'color' => 'green'];
        }
        if ($score >= $thresholds['recommended']) {
            return ['label' => 'Direkomendasikan', 'color' => 'yellow'];
        }
        if ($score >= $thresholds['considered']) {
            return ['label' => 'Dipertimbangkan', 'color' => 'orange'];
        }
        return ['label' => 'Tidak Direkomendasikan', 'color' => 'red'];
    }
}