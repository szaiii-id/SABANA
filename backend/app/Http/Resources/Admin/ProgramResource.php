<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'criteria'        => $this->criteria,
            'start_date'      => $this->start_date?->format('Y-m-d'),
            'end_date'        => $this->end_date?->format('Y-m-d'),
            'quota_total'     => $this->quota_total,
            'benefit_amount'  => $this->benefit_amount,
            'banner_url'      => $this->banner_url,
            'status'          => $this->status,
            'is_active'       => $this->is_active,
            'total_anggaran'  => $this->when(
                $this->quota_total && $this->benefit_amount,
                fn() => $this->quota_total * $this->benefit_amount
            ),
            'submissions_count' => $this->whenCounted('submissions'),
            'anomaly_count' => $this->whenCounted('submissions', function () {
                return $this->submissions->whereNotNull('submission_data->anomalies')->count();
            }),
            'ai_config'       => $this->ai_config,
            'has_submitted'   => $this->has_submitted ?? false,
            'created_at'      => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'      => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}