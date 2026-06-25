<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisteredCitizenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'nik'                => $this->nik,
            'full_name'          => $this->full_name,
            'family_card_number' => $this->family_card_number,
            'whatsapp_number'    => $this->whatsapp_number,
            'is_verified'        => $this->is_verified,
            'access_status'      => $this->accessStatus(),
            'last_login_at'      => $this->last_login_at?->format('Y-m-d H:i:s'),
            'created_at'         => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function accessStatus(): string
    {
        if ($this->pin) {
            return 'activated';
        }

        if ($this->temporary_pin && $this->temporary_pin_expired_at > now()) {
            return 'pin_active';
        }

        if ($this->temporary_pin && $this->temporary_pin_expired_at <= now()) {
            return 'pin_expired';
        }

        return 'no_access';
    }
}