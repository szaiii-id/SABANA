<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class LogActivityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $logData,
    ) {}

    public function handle(): void
    {
        ActivityLog::create($this->logData);
    }
}