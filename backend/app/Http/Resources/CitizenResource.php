<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nik' => $this->nik,
            'family_card_number' => $this->family_card_number,
            'full_name' => $this->full_name,
            'whatsapp_number' => $this->whatsapp_number,
            'is_verified' => (bool) $this->is_verified,
            'last_login' => $this->last_login_at ? $this->last_login_at->translatedFormat('d M Y H:i') : null,
        ];
    }
}
