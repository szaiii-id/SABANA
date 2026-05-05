<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'status' => $this->status,
            'program' => $this->program ? [
                'id' => $this->program->id,
                'name' => $this->program->name,
            ] : null,
            'citizen' => $this->citizen ? [
                'nik' => $this->citizen->nik,
                'full_name' => $this->citizen->full_name,
            ] : null,
            'submission_data' => $this->submission_data, // Data dinamis JSONB
            'disbursement_method' => $this->disbursement_method,
            'bank_account_number' => $this->bank_account_number,
            
            // PERBAIKAN: Menambahkan ID ke dalam objek wilayah agar Frontend bisa melakukan mapping
            'village' => [
                'id' => $this->village_id, 
                'name' => $this->village->name ?? '---'
            ],
            'district' => [
                'id' => $this->district_id, 
                'name' => $this->district->name ?? '---'
            ],
            'regency' => [
                'id' => $this->regency_id, 
                'name' => $this->regency->name ?? '---'
            ],
            
            'evidences' => $this->evidences->map(function ($ev) {
                return [
                    'id' => $ev->id,
                    'image_type' => $ev->image_type,
                    'image_url' => $ev->image_url, 
                ];
            }),
        ];
    }
}