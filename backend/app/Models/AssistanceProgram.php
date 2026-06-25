<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class AssistanceProgram extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CLOSED    = 'closed';
    public const STATUS_COMPLETED = 'completed';

    public const ALL_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_CLOSED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'criteria',
        'ai_config',
        'start_date',
        'end_date',
        'quota_total',
        'benefit_amount',
        'banner_url',
        'banner_public_id',
        'status',
        'is_active',
    ];

    protected $casts = [
        'criteria'        => 'json',
        'ai_config'       => 'json',
        'start_date'      => 'date',
        'end_date'        => 'date',
        'quota_total'     => 'integer',
        'benefit_amount'  => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $program): void {
            if (empty($program->slug)) {
                $program->slug = Str::slug($program->name);
            }
        });

        static::updating(function (self $program): void {
            if (!$program->isDirty('name')) {
                return;
            }

            $autoSlug = Str::slug($program->name);
            $originalSlug = $program->getOriginal('slug');

            // Jika slug sudah di-set manual oleh Service (beda dari auto-slug),
            // atau slug baru bukan hasil auto-generate → jangan overwrite
            if ($program->isDirty('slug') && $program->slug !== $autoSlug) {
                return;
            }

            // Jika slug masih sama dengan auto-slug dari name baru → update
            // Jika slug masih original (belum diubah) → auto-update
            $program->slug = $autoSlug;
        });
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssistanceSubmission::class, 'program_id');
    }

    public function scopeActive($query): mixed
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByStatus($query, string $status): mixed
    {
        return $query->where('status', $status);
    }

    public function getTotalAnggaranAttribute(): float
    {
        return ($this->quota_total ?? 0) * ($this->benefit_amount ?? 0);
    }

    public function getAnomalyCountAttribute(): int
    {
        return $this->submissions()
            ->whereNotNull('submission_data->anomalies')
            ->count();
    }

    public function isOcrEnabled(string $documentKey): bool
    {
        return isset($this->ai_config['documents'][$documentKey]['ocr'])
            && $this->ai_config['documents'][$documentKey]['ocr'] === true;
    }

    public function isNlpEnabled(string $documentKey): bool
    {
        return isset($this->ai_config['documents'][$documentKey]['nlp_match'])
            && $this->ai_config['documents'][$documentKey]['nlp_match'] === true;
    }
}