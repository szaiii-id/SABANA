<?php

namespace App\Enums;

enum AdminAssistanceAction: string
{
    case SUBMIT   = 'submit';
    case RESUBMIT = 'resubmit';
    case CANCEL   = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::SUBMIT   => 'Mengajukan bantuan',
            self::RESUBMIT => 'Mengajukan ulang bantuan',
            self::CANCEL   => 'Membatalkan pengajuan',
        };
    }
}