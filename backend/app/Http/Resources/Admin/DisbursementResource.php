<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DisbursementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'                  => $this->id,
            'registration_number' => $this->registration_number,
            'status'              => $this->status,
            'disbursement_method' => $this->disbursement_method,
        ];

        if ($this->relationLoaded('citizen') && $this->citizen) {
            $data['citizen'] = [
                'id'        => $this->citizen->id,
                'full_name' => $this->citizen->full_name,
                'nik'       => $this->citizen->nik,
            ];
        }

        if ($this->relationLoaded('program') && $this->program) {
            $data['program'] = [
                'id'             => $this->program->id,
                'name'           => $this->program->name,
                'benefit_amount' => $this->program->benefit_amount,
            ];
        }

        if ($this->relationLoaded('disbursement') && $this->disbursement) {
            $data['disbursement'] = [
                'id'               => $this->disbursement->id,
                'amount'           => $this->disbursement->amount,
                'disbursed_at'     => $this->disbursement->disbursed_at?->format('Y-m-d'),
                'method'           => $this->disbursement->method,
                'reference_number' => $this->disbursement->reference_number,
                'notes'            => $this->disbursement->notes,
            ];
        }

        return $data;
    }
}