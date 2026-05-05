<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistanceProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name, 
            'slug' => $this->slug,
            'description' => $this->description,
            'badge' => $this->is_active ? 'Aktif' : 'Non-Aktif',
            'inputs' => $this->criteria['inputs'] ?? [],
            'files' => $this->criteria['files'] ?? [],
        ];
    }
}