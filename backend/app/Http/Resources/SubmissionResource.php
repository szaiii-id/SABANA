<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'program' => [
                'name' => $this->program->name ?? 'Program Terhapus',
            ],
            'status' => $this->status,
            'submitted_at' => $this->created_at->translatedFormat('d M Y'),
            'location' => [
                'village' => $this->village->name ?? '---',
                'district' => $this->district->name ?? '---',
                'regency' => $this->regency->name ?? '---',
                'province' => $this->regency->province->name ?? 'KALIMANTAN SELATAN',
            ]
        ];
    }
}
