<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EvaluationResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status,
            'status_label'     => $this->status_label,
            'decision_notes'   => $this->decision_notes,
            'triggered_at'     => $this->triggered_at?->format('Y-m-d H:i:s'),
            'decided_at'       => $this->decided_at?->format('Y-m-d H:i:s'),
            
            'submission'       => $this->when($this->submission, [
                'id'                  => $this->submission->id,
                'registration_number' => $this->submission->registration_number,
                'status'              => $this->submission->status,
            ]),
            
            'new_submission'   => $this->when($this->newSubmission, fn() => [
                'id'                  => $this->newSubmission->id,
                'registration_number' => $this->newSubmission->registration_number,
                'status'              => $this->newSubmission->status,
                'smart_score'           => $this->newSubmission->smart_score,
                'recommendation' => $this->newSubmission?->recommendation_label,
            ]),
            
            'citizen'          => $this->when($this->citizen, [
                'id'        => $this->citizen->id,
                'full_name' => $this->citizen->full_name,
                'nik'       => $this->citizen->nik,
            ]),
            
            'program'          => $this->when($this->program, [
                'id'   => $this->program->id,
                'name' => $this->program->name,
            ]),
            
            'old_data'         => $this->when($request->user()?->role !== 'village_officer', 
                fn() => $this->old_data
            ),
            
            'location'         => $this->when($this->submission, [
                'village'  => $this->submission->village->name ?? null,
                'district' => $this->submission->district->name ?? null,
                'regency'  => $this->submission->regency->name ?? null,
            ]),
        ];
    }
}