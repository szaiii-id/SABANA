<?php

namespace App\Models;

use App\Contracts\ActivityLogs\Loggable;
use App\Contracts\ActivityLogs\HasDateFilter;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenRegistrationLog extends Model implements Loggable
{
    use HasUuids, HasDateFilter;

    protected $table = 'citizen_registration_logs';

    protected $fillable = [
        'citizen_id',
        'admin_id',
        'admin_name',
        'admin_role',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function toActivityLog(): array
    {
        return [
            'id'           => $this->id,
            'actor_type'   => 'admin',
            'actor_name'   => $this->admin_name,
            'actor_role'   => $this->admin_role,
            'module'       => 'registration',
            'action'       => $this->action,
            'action_label' => $this->registrationLabel(),
            'target_type'  => 'citizen',
            'target_name'  => $this->metadata['nik'] ?? null,
            'metadata'     => $this->metadata,
            'ip_address'   => null,
            'created_at'   => $this->created_at->toDateTimeString(),
            'source'       => 'citizen_registration_logs',
        ];
    }

    private function registrationLabel(): string
    {
        return match ($this->action) {
            'register_with_pin'    => 'Mendaftarkan Warga (dengan PIN)',
            'register_without_pin' => 'Mendaftarkan Warga (tanpa PIN)',
            'resend_pin'           => 'Mengirim Ulang PIN',
            'update_data'          => 'Mengubah Data Warga',
            default                => $this->action,
        };
    }
}