<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ActivityLogs\Loggable;
use App\Contracts\ActivityLogs\HasDateFilter;
use App\Enums\EvaluationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EvaluationLog extends Model implements Loggable
{
    use HasUuids, HasDateFilter;

    protected $fillable = [
        'submission_id',
        'new_submission_id',
        'program_id',
        'citizen_id',
        'village_id',
        'district_id',
        'regency_id',
        'status',
        'old_data',
        'decision_notes',
        'triggered_by',
        'triggered_at',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'old_data'      => 'array',
        'status'        => EvaluationStatus::class,
        'triggered_at'  => 'datetime',
        'decided_at'    => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssistanceSubmission::class, 'submission_id');
    }

    public function newSubmission(): BelongsTo
    {
        return $this->belongsTo(AssistanceSubmission::class, 'new_submission_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AssistanceProgram::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'decided_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', EvaluationStatus::active());
    }

    public function scopeByVillage($query, string $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeByDistrict($query, string $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeByRegency($query, string $regencyId)
    {
        return $query->where('regency_id', $regencyId);
    }

    public function scopePendingDecision($query)
    {
        return $query->where('status', EvaluationStatus::UPDATED);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function toActivityLog(): array
    {
        return [
            'id'           => $this->id,
            'actor_type'   => $this->triggered_by === 'system' ? 'system' : 'admin',
            'actor_name'   => $this->triggered_by === 'system' ? 'Sistem' : $this->decider?->name,
            'actor_role'   => $this->triggered_by === 'system' ? null : $this->decider?->role,
            'module'       => 'evaluation',
            'action'       => $this->status->value,
            'action_label' => $this->status->label(),
            'target_type'  => 'submission',
            'target_name'  => $this->submission?->registration_number,
            'metadata'     => ['decision_notes' => $this->decision_notes],
            'ip_address'   => null,
            'created_at'   => $this->created_at->toDateTimeString(),
            'source'       => 'evaluation_logs',
        ];
    }
}