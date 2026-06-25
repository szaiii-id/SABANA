<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->nip,
            'name' => $this->name,
            'role' => $this->role,
            'is_active' => $this->is_active,
            
            'regency_id' => $this->regency_id,
            'district_id' => $this->district_id,
            'village_id' => $this->village_id,
            
            'region' => [
                'regency' => $this->whenLoaded('regency', fn() => $this->regency?->name),
                'district' => $this->whenLoaded('district', fn() => $this->district?->name),
                'village' => $this->whenLoaded('village', fn() => $this->village?->name),
            ],
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}