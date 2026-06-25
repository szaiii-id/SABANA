<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DisbursementReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'registration_number' => $this->registration_number,
            'program_name'        => $this->program->name ?? null,
            'citizen_name'        => $this->citizen->full_name ?? null,
            'citizen_nik'         => $this->citizen->nik ?? null,
            'family_card_number'  => $this->citizen->family_card_number ?? null,
            'amount'              => $this->disbursement->amount,
            'method'              => $this->disbursement->method === 'village_cash' ? 'Tunai (Kantor Desa)' : 'Transfer BPD',
            'reference_number'    => $this->disbursement->reference_number,
            'disbursed_at'        => $this->disbursement->disbursed_at?->format('d M Y'),
            'officer_name'        => $this->disbursement->officer->name ?? null,
        ];
    }
}