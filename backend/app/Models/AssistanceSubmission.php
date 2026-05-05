<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistanceSubmission extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $fillable = [
        'citizen_id',
        'program_id',
        'registration_number',
        'regency_id',
        'district_id',
        'village_id',
        'status',
        'submission_data',
        'saw_score',
        'disbursement_method',
        'bank_account_number',
        'needs_data_update',
        'last_submission_date' 
    ];

    protected $casts = [
        'submission_data' => 'array',
        'saw_score' => 'double',
        'needs_data_update' => 'boolean',
        'last_submission_date' => 'datetime',
    ];

    /**
     * Relasi: Pengajuan ini milik satu warga.
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    /**
     * Relasi: Pengajuan ini ditujukan untuk satu program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AssistanceProgram::class, 'program_id');
    }

    /**
     * Relasi: Pengajuan ini memiliki banyak foto bukti.
     */
    public function evidences(): HasMany
    {
        return $this->hasMany(AssistanceEvidence::class, 'submission_id');
    }

    public function village() {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function district() {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function regency() {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

 
}