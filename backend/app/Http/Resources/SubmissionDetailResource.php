<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Admin\VerificationService;

final class SubmissionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $smartScore = $this->smart_score;
        $recommendation = $this->recommendation_label;

        $submissionData = $this->submission_data;
        if (isset($submissionData['anomalies'])) {
            unset($submissionData['anomalies']);
        }

        return [
            'id'                  => $this->id,
            'registration_number' => $this->registration_number,
            'status'              => $this->status,
            'smart_score'           => $smartScore,
            'recommendation'      => $recommendation,
            'submitted_at'        => $this->submitted_at?->format('Y-m-d H:i:s'),

            'program' => $this->program ? [
                'id'             => $this->program->id,
                'name'           => $this->program->name,
                'title'          => $this->program->title ?? null,
                'benefit_amount' => $this->program->benefit_amount,
                'criteria'       => $this->program->criteria,
            ] : null,

            'citizen' => $this->citizen ? [
                'id'        => $this->citizen->id,
                'nik'       => $this->citizen->nik,
                'full_name' => $this->citizen->full_name,
            ] : null,

            'submission_data' => $submissionData,
            'disbursement' => $this->disbursement ? [
                'id'               => $this->disbursement->id,
                'amount'           => $this->disbursement->amount,
                'disbursed_at'     => optional($this->disbursement->disbursed_at)->format('Y-m-d'),
                'method'           => $this->disbursement->method,
                'reference_number' => $this->disbursement->reference_number,
            ] : null,

            'revision_items'   => $this->revision_items ?? [],
            'verified_by_name' => $this->verified_by_name,
            'verified_at'      => $this->verified_at ? Carbon::parse($this->verified_at)->format('Y-m-d H:i:s') : null,
            'revision_by_name' => $this->revision_by_name,
            'revision_at'      => $this->revision_at ? Carbon::parse($this->revision_at)->format('Y-m-d H:i:s') : null,

            'rejection_note'   => $this->verifications()
                                    ->where('action_type', 'rejected')
                                    ->latest()
                                    ->value('notes'),
            'revision_note'    => $this->verifications()
                                    ->where('action_type', 'revision_requested')
                                    ->latest()
                                    ->value('notes'),

            'evaluation_notes' => $this->evaluationLog?->decision_notes ?? $this->evaluationLogAsNew?->decision_notes,

            'disbursement_method' => $this->disbursement_method,
            'bank_account_number' => $this->bank_account_number,

            'village' => [
                'id'   => $this->village_id,
                'name' => $this->village->name ?? '---',
            ],
            'district' => [
                'id'   => $this->district_id,
                'name' => $this->district->name ?? '---',
            ],
            'regency' => [
                'id'   => $this->regency_id,
                'name' => $this->regency->name ?? '---',
            ],

            'evidences' => $this->evidences->map(function ($ev) {
                return [
                    'id'         => $ev->id,
                    'image_type' => $ev->image_type,
                    'image_url'  => $ev->image_url,
                    'ai_result'  => $ev->ai_result,
                ];
            }),
        ];
    }
}