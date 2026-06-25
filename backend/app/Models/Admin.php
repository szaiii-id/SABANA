<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'nip', 'name', 'password', 'role',
        'regency_id', 'district_id', 'village_id',
        'is_active', 'last_login_at'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'is_active'      => 'boolean',
            'last_login_at'  => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI WILAYAH
    |--------------------------------------------------------------------------
    */

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI VERIFIKASI
    |--------------------------------------------------------------------------
    */

    /**
     * Semua aksi verifikasi yang dilakukan admin ini.
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(SubmissionVerification::class, 'admin_id');
    }

    /**
     * Submission yang disetujui oleh admin ini.
     */
    public function approvedSubmissions()
    {
        return $this->verifications()->where('action_type', 'approved');
    }

    /**
     * Submission yang ditolak oleh admin ini.
     */
    public function rejectedSubmissions()
    {
        return $this->verifications()->where('action_type', 'rejected');
    }

    /**
     * Submission yang diminta revisi oleh admin ini.
     */
    public function revisionSubmissions()
    {
        return $this->verifications()->where('action_type', 'revision_requested');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER ROLE
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isRegencyAdmin(): bool
    {
        return $this->role === 'regency_admin';
    }

    public function isDistrictAdmin(): bool
    {
        return $this->role === 'district_admin';
    }

    public function isVillageOfficer(): bool
    {
        return $this->role === 'village_officer';
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByRegency($query, string $regencyId)
    {
        return $query->where('regency_id', $regencyId);
    }
}