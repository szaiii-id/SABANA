<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    // Konfigurasi Primary Key Non-Incrementing
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'regency_id', 'name'];

    /**
     * Relasi: Kecamatan ini milik satu Kabupaten/Kota.
     */
    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    /**
     * Relasi: Satu kecamatan memiliki banyak Desa/Kelurahan.
     */
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'district_id');
    }
}