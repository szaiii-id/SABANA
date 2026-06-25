<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AssistanceProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'badge'          => $this->is_active ? 'Aktif' : 'Non-Aktif',
            'banner_url'     => $this->banner_url,
            'start_date'     => $this->start_date?->format('Y-m-d'),
            'end_date'       => $this->end_date?->format('Y-m-d'),
            'quota_total'    => $this->quota_total,
            'benefit_amount' => $this->benefit_amount,
            'inputs'         => $this->criteria['inputs'] ?? [],
            'documents'      => $this->criteria['documents'] ?? [],
            'has_submitted'  => $this->has_submitted ?? false,
        ];
    }
}