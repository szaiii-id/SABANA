<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'registration_number' => $this->registration_number,
            
            'program' => [
                'id'       => $this->program->id ?? null,
                'name'     => $this->program->name ?? 'Program Terhapus',
                'criteria' => $this->program->criteria ?? null,
            ],
            
            'status'          => $this->status,
            'is_evaluation'   => $this->status === 'evaluation_pending',
            'revision_items'  => $this->revision_items ?? [],
            'admin_note'      => $this->verifications()
                                    ->whereIn('action_type', ['revision_requested', 'rejected'])
                                    ->latest()
                                    ->value('notes'),
            'submitted_at'    => $this->created_at->translatedFormat('d M Y'),
            
            'location' => [
                'village'  => $this->village->name ?? '---',
                'district' => $this->district->name ?? '---',
                'regency'  => $this->regency->name ?? '---',
                'province' => $this->regency->province->name ?? 'KALIMANTAN SELATAN',
            ],

            'disbursement' => $this->when(
                $this->relationLoaded('disbursement') && $this->disbursement,
                fn () => [
                    'amount'           => $this->disbursement->amount,
                    'method'           => $this->disbursement->method,
                    'reference_number' => $this->disbursement->reference_number,
                    'disbursed_at'     => $this->disbursement->disbursed_at?->translatedFormat('d M Y'),
                    'officer_name'     => $this->disbursement->officer?->name,
                ]
            ),
        ];
    }
}