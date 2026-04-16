<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Citizen extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nik',
        'family_card_number',
        'full_name',
        'whatsapp_number',
        'pin',
        'temporary_pin',
        'temporary_pin_expired_at',
        'last_login_at',
    ];

    protected $hidden = [
        'pin',
        'temporary_pin',
    ];

    protected $casts = [
        'temporary_pin_expired_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function validateForPassportPasswordGrant($pin)
    {
        return \Hash::check($pin, $this->pin);
    }
}
