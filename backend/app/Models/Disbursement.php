<?php

namespace App\Models;

use App\Contracts\ActivityLogs\Loggable;
use App\Contracts\ActivityLogs\HasDateFilter;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disbursement extends Model implements Loggable
{
    use HasUuids, HasDateFilter;

    protected $fillable = [
        'submission_id',
        'program_id',
        'citizen_id',
        'amount',
        'disbursed_at',
        'method',
        'reference_number',
        'disbursed_by',
        'notes',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'disbursed_at'  => 'date',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssistanceSubmission::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AssistanceProgram::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'disbursed_by');
    }

    protected static function booted(): void
    {
        static::creating(function (Disbursement $disbursement) {
            if (empty($disbursement->reference_number)) {
                $disbursement->reference_number = 'SBN-DSB-' . strtoupper(
                    \Illuminate\Support\Str::random(8)
                );
            }

            if (empty($disbursement->disbursed_at)) {
                $disbursement->disbursed_at = now()->toDateString();
            }
        });
    }

    public function toActivityLog(): array
    {
        return [
            'id'           => $this->id,
            'actor_type'   => 'admin',
            'actor_name'   => $this->officer?->name,
            'actor_role'   => $this->officer?->role,
            'module'       => 'disbursement',
            'action'       => 'disburse',
            'action_label' => 'Menyalurkan Bantuan',
            'target_type'  => 'submission',
            'target_name'  => $this->submission?->registration_number,
            'metadata'     => ['amount' => $this->amount, 'reference_number' => $this->reference_number],
            'ip_address'   => null,
            'created_at'   => $this->created_at->toDateTimeString(),
            'source'       => 'disbursements',
        ];
    }
}