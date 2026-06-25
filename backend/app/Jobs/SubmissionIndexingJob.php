<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AssistanceSubmission;
use App\Services\Admin\SubmissionSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SubmissionIndexingJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum retry attempts.
     */
    public int $tries = 3;

    /**
     * Jitter backoff untuk retry: 5s, 15s, 30s.
     */
    public array $backoff = [5, 15, 30];

    /**
     * Unique lock untuk mencegah duplicate job.
     */
    public int $uniqueFor = 60;

    public function __construct(
        private readonly string $submissionId,
        private readonly string $action = 'index'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SubmissionSearchService $searchService): void
    {
        try {
            if ($this->action === 'delete') {
                $searchService->delete($this->submissionId);
                Log::info('SubmissionIndexingJob: deleted', ['id' => $this->submissionId]);
                return;
            }

            $submission = AssistanceSubmission::with(['citizen', 'program'])->find($this->submissionId);

            if (!$submission) {
                Log::warning('SubmissionIndexingJob: submission not found', [
                    'id' => $this->submissionId,
                ]);
                return;
            }

            $searchService->index($submission);

            Log::info('SubmissionIndexingJob: indexed', [
                'id'     => $submission->id,
                'status' => $submission->status,
            ]);
        } catch (\Exception $e) {
            Log::error('SubmissionIndexingJob failed', [
                'submission_id' => $this->submissionId,
                'action'        => $this->action,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 30);
                return;
            }

            throw $e;
        }
    }

    /**
     * Unique ID untuk mencegah duplicate job.
     */
    public function uniqueId(): string
    {
        return "submission_indexing:{$this->submissionId}:{$this->action}";
    }

    /**
     * Handle a job failure after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('SubmissionIndexingJob permanent failure', [
            'submission_id' => $this->submissionId,
            'action'        => $this->action,
            'error'         => $exception->getMessage(),
        ]);
    }
}