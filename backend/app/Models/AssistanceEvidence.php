<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssistanceEvidence extends Model
{
    use HasFactory;
    protected $table = 'assistance_evidences';

    protected $fillable = [
        'submission_id', 
        'image_type', 
        'image_url', 
        'cloud_public_id'
    ];

    /**
     * Relasi: Foto ini milik satu pengajuan spesifik.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssistanceSubmission::class, 'submission_id');
    }

    /**
     * Accessor: Memanipulasi image_url otomatis menjadi format f_auto,q_auto (WebP/AVIF)
     * Ini hanya mengubah output saat API dipanggil, data asli di DB tetap aman.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value && str_contains($value, 'res.cloudinary.com')) {
                    return str_replace('/upload/', '/upload/f_auto,q_auto/', $value);
                }
                
                return $value;
            }
        );
    }
}