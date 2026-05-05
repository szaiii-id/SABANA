<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistanceProgram extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $fillable = [
        'name', 
        'slug', 
        'description', 
        'criteria',
        'is_active', 
    ];

    protected $casts = [
        'criteria' => 'array', 
        'is_active' => 'boolean',
    ];

    /**
     * Relasi: Satu program bisa punya banyak pengajuan.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssistanceSubmission::class, 'program_id');
    }
}