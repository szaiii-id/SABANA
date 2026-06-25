<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ActivityLogs\Loggable;
use App\Contracts\ActivityLogs\HasDateFilter;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionVerification extends Model implements Loggable
{
    use HasUuids, HasFactory, HasDateFilter;

    protected $table = 'submission_verifications';

    protected $fillable = [
        'submission_id',
        'admin_id',
        'admin_name',
        'action_type',
        'notes',
        'revision_items',
    ];

    protected $casts = [
        'revision_items' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssistanceSubmission::class, 'submission_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function scopeLatestForSubmission($query, string $submissionId)
    {
        return $query->where('submission_id', $submissionId)->latest()->first();
    }

    public function scopeHistoryForSubmission($query, string $submissionId)
    {
        return $query->where('submission_id', $submissionId)->latest()->get();
    }

    public function toActivityLog(): array
    {
        return [
            'id'           => $this->id,
            'actor_type'   => 'admin',
            'actor_name'   => $this->admin_name,
            'actor_role'   => null,
            'module'       => 'verification',
            'action'       => $this->action_type,
            'action_label' => $this->verificationLabel(),
            'target_type'  => 'submission',
            'target_name'  => $this->submission?->registration_number,
            'metadata'     => ['notes' => $this->notes],
            'ip_address'   => null,
            'created_at'   => $this->created_at->toDateTimeString(),
            'source'       => 'submission_verifications',
        ];
    }

    public function verificationLabel(): string
    {
        return match ($this->action_type) {
            'approved'             => 'Menyetujui Pengajuan',
            'rejected'             => 'Menolak Pengajuan',
            'revision_requested'   => 'Meminta Perbaikan',
            'completed'            => 'Menandai Selesai',
            'unvalidated'          => 'Membatalkan Persetujuan',
            'evaluation_triggered' => 'Trigger Evaluasi',
            'evaluation_approved'  => 'Evaluasi Disetujui',
            'evaluation_revoked'   => 'Evaluasi Ditolak',
            default                => $this->action_type,
        };
    }
}