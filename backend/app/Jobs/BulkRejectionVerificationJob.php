<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SubmissionVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class BulkRejectionVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly array $submissionIds,
        private readonly string $notes,
    ) {}

    public function handle(): void
    {
        $records = array_map(fn(string $id): array => [
            'submission_id' => $id,
            'admin_name'    => 'Sistem',
            'action_type'   => 'rejected',
            'notes'         => $this->notes,
            'created_at'    => now(),
            'updated_at'    => now(),
        ], $this->submissionIds);

        // Batch insert — 1 query
        SubmissionVerification::insert($records);

        Log::info('BulkRejectionVerificationJob: completed', [
            'count' => count($this->submissionIds),
        ]);
    }
}