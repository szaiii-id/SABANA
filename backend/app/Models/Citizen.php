<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 */
class Citizen extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Searchable, HasUuids;

    protected $fillable = [
        'nik',
        'family_card_number',
        'full_name',
        'whatsapp_number',
        'pin',
        'temporary_pin',
        'temporary_pin_expired_at',
        'last_login_at',
        'is_verified',
    ];

    protected $hidden = [
        'pin',
        'temporary_pin',
    ];

    protected $casts = [
        'temporary_pin_expired_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Relasi: Warga ini punya riwayat pengajuan bantuan apa saja?
     */
    public function assistanceSubmissions()
    {
        return $this->hasMany(AssistanceSubmission::class, 'citizen_id');
    }

    public function syncWithSearchUsingQueue() {
        return true; 
    }

}
