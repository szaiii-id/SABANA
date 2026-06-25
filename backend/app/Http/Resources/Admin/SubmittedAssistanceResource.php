<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SubmittedAssistanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'registration_number' => $this->registration_number,
            'status'              => $this->status,
            'program'             => [
                'id'   => $this->program->id,
                'name' => $this->program->name,
            ],
            'citizen'             => [
                'id'        => $this->citizen->id,
                'nik'       => $this->citizen->nik,
                'full_name' => $this->citizen->full_name,
            ],
            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}