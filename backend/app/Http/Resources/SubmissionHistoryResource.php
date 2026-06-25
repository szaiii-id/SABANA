<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SubmissionHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'program_id'           => $this->resource['program_id'],
            'program_name'         => $this->resource['program_name'],
            'registration_number'  => $this->resource['registration_number'],
            'current_status'       => $this->resource['current_status'],
            'current_status_label' => $this->resource['current_status_label'],
            'is_evaluation'        => $this->resource['is_evaluation'],
            'latest_submission_id' => $this->resource['latest_submission_id'],
            
            // ✅ TAMBAH INI
            'revision_items'       => $this->resource['revision_items'] ?? [],
            'admin_note'           => $this->resource['admin_note'] ?? null,

            'program' => $this->when(
                isset($this->resource['program']),
                fn () => [
                    'id'   => $this->resource['program']->id,
                    'name' => $this->resource['program']->name,
                ]
            ),

            'timeline' => $this->resource['timeline'],

            'disbursement' => $this->when(
                isset($this->resource['latest_submission']) && $this->resource['latest_submission']->relationLoaded('disbursement') && $this->resource['latest_submission']->disbursement,
                fn () => [
                    'amount'           => $this->resource['latest_submission']->disbursement->amount,
                    'method'           => $this->resource['latest_submission']->disbursement->method,
                    'reference_number' => $this->resource['latest_submission']->disbursement->reference_number,
                    'disbursed_at'     => $this->resource['latest_submission']->disbursement->disbursed_at?->format('Y-m-d'),
                ]
            ),
        ];
    }
}