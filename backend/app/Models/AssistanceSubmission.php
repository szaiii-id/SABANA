<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssistanceSubmission extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    /*
    |--------------------------------------------------------------------------
    | KONSTANTA STATUS
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING        = 'pending';
    public const STATUS_VALIDATED      = 'validated';
    public const STATUS_REJECTED       = 'rejected';
    public const STATUS_COMPLETED      = 'completed';
    public const STATUS_NEEDS_REVISION = 'needs_revision';
    public const STATUS_EVALUATION_PENDING = 'evaluation_pending';
    public const STATUS_REVOKED = 'revoked';

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VALIDATED,
        self::STATUS_EVALUATION_PENDING,
        self::STATUS_NEEDS_REVISION,
        self::STATUS_REVOKED,
    ];

    /**
     * Status yang memakai kuota program (pendaftaran awal, bukan evaluasi).
     * Tidak termasuk 'evaluation_pending' & 'revoked' karena itu status submission lama.
     */
    public const QUOTA_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VALIDATED,
        self::STATUS_NEEDS_REVISION,
        self::STATUS_COMPLETED,
    ];

    public const ALL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VALIDATED,
        self::STATUS_REJECTED,
        self::STATUS_COMPLETED,
        self::STATUS_NEEDS_REVISION,
        self::STATUS_EVALUATION_PENDING,
        self::STATUS_REVOKED,
    ];

    protected $fillable = [
        'citizen_id',
        'program_id',
        'registration_number',
        'regency_id',
        'district_id',
        'village_id',
        'status',
        'submission_data',
        'smart_score',
        'disbursement_method',
        'bank_account_number',
        'needs_data_update',
        'last_submission_date',
        'submitted_at',
    ];

    protected $casts = [
        'submission_data'      => 'array',
        'smart_score'            => 'double',
        'needs_data_update'    => 'boolean',
        'last_submission_date' => 'datetime',
        'submitted_at'         => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI
    |--------------------------------------------------------------------------
    */

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AssistanceProgram::class, 'program_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(AssistanceEvidence::class, 'submission_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(SubmissionVerification::class, 'submission_id');
    }

    public function latestVerification(): BelongsTo
    {
        return $this->belongsTo(SubmissionVerification::class, 'latest_verification_id');
    }

    public function evaluationLog(): HasOne
    {
        return $this->hasOne(EvaluationLog::class, 'submission_id');
    }


    public function disbursement(): HasOne
    {
        return $this->hasOne(Disbursement::class, 'submission_id');
    }

    public function evaluationLogAsNew(): HasOne
    {
        return $this->hasOne(EvaluationLog::class, 'new_submission_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR
    |--------------------------------------------------------------------------
    */

    public function getIsEvaluationAttribute(): bool
    {
        return $this->evaluationLog()
            ->whereIn('status', ['triggered', 'updated'])
            ->exists();
    }
    
    public function getVerifiedByNameAttribute(): ?string
    {
        return $this->verifications()
                    ->whereIn('action_type', ['approved', 'rejected', 'completed', 'evaluation_approved', 'evaluation_revoked'])
                    ->latest()
                    ->value('admin_name');
    }

    public function getVerifiedAtAttribute(): ?string
    {
        return $this->verifications()
                    ->whereIn('action_type', ['approved', 'rejected', 'completed', 'evaluation_approved', 'evaluation_revoked'])
                    ->latest()
                    ->value('created_at');
    }

    public function getRevisionByNameAttribute(): ?string
    {
        return $this->verifications()
                    ->where('action_type', 'revision_requested')
                    ->latest()
                    ->value('admin_name');
    }

    public function getRevisionAtAttribute(): ?string
    {
        return $this->verifications()
                    ->where('action_type', 'revision_requested')
                    ->latest()
                    ->value('created_at');
    }

    public function getRevisionItemsAttribute(): ?array
    {
        return $this->verifications()
                    ->where('action_type', 'revision_requested')
                    ->latest()
                    ->value('revision_items');
    }

    public function getIsDisbursedAttribute(): bool
    {
        return $this->disbursement()->exists();
    }

    public function getDisbursementReferenceAttribute(): ?string
    {
        return $this->disbursement?->reference_number;
    }

    public function getRecommendationLabelAttribute(): array
    {
        $score = $this->smart_score;
        
        if ($score === null) {
            return ['label' => 'Belum Dinilai', 'color' => 'gray'];
        }

        $thresholds = config('sabana.smart.thresholds', [
            'highly_recommended' => 70,
            'recommended'        => 50,
            'considered'         => 30,
        ]);

        if ($score >= $thresholds['highly_recommended']) {
            return ['label' => 'Sangat Direkomendasikan', 'color' => 'green'];
        }
        if ($score >= $thresholds['recommended']) {
            return ['label' => 'Direkomendasikan', 'color' => 'yellow'];
        }
        if ($score >= $thresholds['considered']) {
            return ['label' => 'Dipertimbangkan', 'color' => 'orange'];
        }
        return ['label' => 'Tidak Direkomendasikan', 'color' => 'red'];
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeHasAnomalies($query)
    {
        return $query->whereNotNull('submission_data->anomalies');
    }

    public function scopeReadyForDisbursement($query)
    {
        return $query->where('status', 'validated')
            ->whereDoesntHave('disbursement');
    }

    /**
     * Exclude submission hasil evaluasi (new_submission_id di evaluation_logs).
     * Submission evaluasi TIDAK memakai kuota program.
     */
    public function scopeExcludeEvaluation($query)
    {
        return $query->whereNotIn('id', function ($subQuery) {
            $subQuery->select('new_submission_id')
                  ->from('evaluation_logs')
                  ->whereNotNull('new_submission_id');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | QUOTA HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Hitung submission pemakai kuota (bukan evaluasi).
     * Status: pending, validated, needs_revision, completed.
     */
    public static function countQuotaUsage(string $programId): int
    {
        return static::where('program_id', $programId)
            ->excludeEvaluation()
            ->whereIn('status', static::QUOTA_STATUSES)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Hitung submission pemakai kuota dengan row lock.
     * Lock program row terlebih dahulu, lalu hitung.
     * PostgreSQL tidak mengizinkan FOR UPDATE dengan aggregate COUNT().
     */
    public static function countQuotaUsageLocked(string $programId): int
    {
        // Lock row program untuk mencegah concurrent approve
        AssistanceProgram::where('id', $programId)->lockForUpdate()->first();

        // Hitung kuota (tanpa lock — sudah aman karena program di-lock)
        return static::countQuotaUsage($programId);
    }
}