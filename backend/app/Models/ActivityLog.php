<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ActivityLogs\Loggable;
use App\Contracts\ActivityLogs\HasDateFilter;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ActivityLog extends Model implements Loggable
{
    use HasUuids, HasDateFilter;

    public $timestamps = false;

    protected $fillable = [
        'actor_type', 'actor_id', 'actor_name', 'actor_role',
        'module', 'action', 'action_label',
        'target_type', 'target_id', 'target_name',
        'metadata', 'ip_address', 'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function toActivityLog(): array
    {
        return [
            'id'           => $this->id,
            'actor_type'   => $this->actor_type,
            'actor_name'   => $this->actor_name,
            'actor_role'   => $this->actor_role,
            'module'       => $this->module,
            'action'       => $this->action,
            'action_label' => $this->action_label,
            'target_type'  => $this->target_type,
            'target_name'  => $this->target_name,
            'metadata'     => $this->metadata,
            'ip_address'   => $this->ip_address,
            'created_at'   => $this->created_at->toDateTimeString(),
            'source'       => 'activity_logs',
        ];
    }

    public function scopeByAdmin($query, string $adminId)
    {
        return $query->where('actor_type', 'admin')->where('actor_id', $adminId);
    }

    public function scopeByCitizen($query, string $citizenId)
    {
        return $query->where('actor_type', 'citizen')->where('actor_id', $citizenId);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public static function log(
        string $actorType,
        ?string $actorId,
        ?string $actorName,
        ?string $actorRole,
        string $module,
        string $action,
        string $actionLabel,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $targetName = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'actor_type'   => $actorType,
            'actor_id'     => $actorId,
            'actor_name'   => $actorName,
            'actor_role'   => $actorRole,
            'module'       => $module,
            'action'       => $action,
            'action_label' => $actionLabel,
            'target_type'  => $targetType,
            'target_id'    => $targetId,
            'target_name'  => $targetName,
            'metadata'     => $metadata,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'created_at'   => now(),
        ]);
    }
}