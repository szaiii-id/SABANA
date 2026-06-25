<?php

declare(strict_types=1);

namespace App\Enums;

enum EvaluationStatus: string
{
    case TRIGGERED = 'triggered';
    case UPDATED   = 'updated';
    case APPROVED  = 'approved';
    case REVOKED   = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::TRIGGERED => 'Menunggu Warga',
            self::UPDATED   => 'Menunggu Verifikasi',
            self::APPROVED  => 'Disetujui',
            self::REVOKED   => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TRIGGERED => 'yellow',
            self::UPDATED   => 'blue',
            self::APPROVED  => 'green',
            self::REVOKED   => 'red',
        };
    }

    /**
     * Status yang masih aktif (belum selesai).
     */
    public static function active(): array
    {
        return [self::TRIGGERED->value, self::UPDATED->value];
    }

    /**
     * Status yang sudah selesai (final).
     */
    public static function final(): array
    {
        return [self::APPROVED->value, self::REVOKED->value];
    }
}