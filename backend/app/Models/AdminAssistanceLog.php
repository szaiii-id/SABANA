<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ActivityLogs\Loggable;
use App\Contracts\ActivityLogs\HasDateFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdminAssistanceLog extends Model implements Loggable
{
    use HasUuids, HasDateFilter;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'admin_role',
        'citizen_id',
        'submission_id',
        'program_id',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssistanceSubmission::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AssistanceProgram::class);
    }

    public function scopeByAdmin($query, string $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByCitizen($query, string $citizenId)
    {
        return $query->where('citizen_id', $citizenId);
    }

    public function scopeByProgram($query, string $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('admin_role', $role);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'submit'              => 'Mengajukan bantuan',
            'resubmit'            => 'Mengajukan ulang bantuan',
            'cancel'              => 'Membatalkan pengajuan',
            'evaluation_resubmit' => 'Mengajukan evaluasi',
            default               => $this->action,
        };
    }

    public function getFormattedMetadataAttribute(): string
    {
        if (!$this->metadata) return '-';
        $parts = [];
        if (isset($this->metadata['registration_number'])) {
            $parts[] = 'No: ' . $this->metadata['registration_number'];
        }
        if (isset($this->metadata['program_name'])) {
            $parts[] = 'Program: ' . $this->metadata['program_name'];
        }
        return implode(' | ', $parts) ?: '-';
    }

    public function toActivityLog(): array
    {
        return [
            'id'           => $this->id,
            'actor_type'   => 'admin',
            'actor_name'   => $this->admin_name,
            'actor_role'   => $this->admin_role,
            'module'       => 'submission',
            'action'       => $this->action,
            'action_label' => $this->action_label,
            'target_type'  => 'submission',
            'target_name'  => $this->metadata['registration_number'] ?? null,
            'metadata'     => $this->metadata,
            'ip_address'   => null,
            'created_at'   => $this->created_at->toDateTimeString(),
            'source'       => 'admin_assistance_logs',
        ];
    }
}