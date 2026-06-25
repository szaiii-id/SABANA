<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Services\Admin\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VerificationDetailResource extends JsonResource
{
    private ?VerificationService $verificationService = null;

    public function withVerificationService(VerificationService $service): self
    {
        $this->verificationService = $service;
        return $this;
    }

    private function getRecommendation(): array
    {
        if ($this->verificationService) {
            return $this->verificationService->getRecommendation($this->smart_score);
        }

        return ['label' => 'Tidak Diketahui', 'color' => 'gray'];
    }

    public function toArray(Request $request): array
    {
        $recommendation = $this->getRecommendation();

        $data = [
            'id'                  => $this->id,
            'registration_number' => $this->registration_number,
            'status'              => $this->status,
            'smart_score'           => $this->smart_score,
            'recommendation' => [
                'label' => $recommendation['label'] ?? 'Tidak Diketahui',
                'color' => $recommendation['color'] ?? 'gray',
            ],
            'disbursement_method' => $this->disbursement_method,
            'bank_account_number' => $this->bank_account_number,

            'citizen' => $this->citizen ? [
                'id'              => $this->citizen->id,
                'nik'             => $this->citizen->nik,
                'full_name'       => $this->citizen->full_name,
                'whatsapp_number' => $this->citizen->whatsapp_number,
            ] : null,

            'program' => $this->program ? [
                'id'             => $this->program->id,
                'name'           => $this->program->name,
                'benefit_amount' => $this->program->benefit_amount,
                'criteria'       => $this->program->criteria,
            ] : null,

            'submission_data' => $this->submission_data,
        ];

        if ($this->relationLoaded('disbursement') && $this->disbursement) {
            $data['disbursement'] = [
                'id'               => $this->disbursement->id,
                'amount'           => $this->disbursement->amount,
                'disbursed_at'     => optional($this->disbursement->disbursed_at)->format('Y-m-d'),
                'method'           => $this->disbursement->method,
                'reference_number' => $this->disbursement->reference_number,
            ];
        }

        $data['verified_by_name'] = $this->verified_by_name;
        $data['verified_at'] = $this->verified_at ? \Carbon\Carbon::parse($this->verified_at)->format('Y-m-d H:i:s') : null;
        $data['revision_by_name'] = $this->revision_by_name;
        $data['revision_at'] = $this->revision_at ? \Carbon\Carbon::parse($this->revision_at)->format('Y-m-d H:i:s') : null;
        $data['revision_items'] = $this->revision_items ?? [];

        $data['verification_history'] = $this->whenLoaded('verifications', function () {
            return $this->verifications
                ->sortByDesc('created_at')
                ->values()
                ->map(fn($v) => [
                    'action_type'   => $v->action_type,
                    'action_label'  => $v->verificationLabel(),
                    'admin_name'    => $v->admin_name,
                    'notes'         => $v->notes,
                    'revision_items' => $v->revision_items,
                    'created_at'    => $v->created_at->format('Y-m-d H:i:s'),
                ]);
        });

        $data['wilayah'] = [
            'village'  => $this->village->name ?? null,
            'district' => $this->district->name ?? null,
            'regency'  => $this->regency->name ?? null,
        ];
        $data['evidences'] = $this->whenLoaded('evidences', fn() => $this->evidences->map(fn($ev) => [
            'id'         => $ev->id,
            'image_type' => $ev->image_type,
            'image_url'  => $ev->image_url,
            'ai_result'  => $ev->ai_result,
        ]));
        $data['created_at'] = $this->created_at->format('Y-m-d H:i:s');

        return $data;
    }
}