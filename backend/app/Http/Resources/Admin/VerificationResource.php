<?php

namespace App\Http\Resources\Admin;

use App\Services\Admin\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
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

        // Fallback hanya jika Service tidak diinject (seharusnya tidak terjadi)
        return ['label' => 'Tidak Diketahui', 'color' => 'gray'];
    }

    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'registration_number' => $this->registration_number,
            'status'              => $this->status,
            'smart_score'           => $this->smart_score,
            'recommendation'      => $this->getRecommendation(),

            'citizen' => $this->citizen ? [
                'id'        => $this->citizen->id,
                'nik'       => $this->citizen->nik,
                'full_name' => $this->citizen->full_name,
            ] : null,

            'program' => $this->program ? [
                'id'   => $this->program->id,
                'name' => $this->program->name,
            ] : null,

            'wilayah' => [
                'village'  => $this->village->name ?? null,
                'district' => $this->district->name ?? null,
                'regency'  => $this->regency->name ?? null,
            ],

            'verified_by_name' => $this->verified_by_name,
            'verified_at'      => $this->verified_at
                ? \Carbon\Carbon::parse($this->verified_at)->format('Y-m-d H:i:s')
                : null,

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}