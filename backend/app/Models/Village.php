<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Village extends Model
{
    // Konfigurasi Primary Key Non-Incrementing
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'district_id', 'name'];

    /**
     * Relasi: Desa ini milik satu Kecamatan.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}