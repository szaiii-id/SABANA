<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ProgramHasActiveSubmissionsException extends RuntimeException
{
    public function __construct(
        public readonly string $programId,
        public readonly int $activeSubmissionsCount,
    ) {
        parent::__construct(
            sprintf(
                "Tidak dapat menghapus program dengan %d pengajuan aktif. Tutup program terlebih dahulu.",
                $this->activeSubmissionsCount
            )
        );
    }
}