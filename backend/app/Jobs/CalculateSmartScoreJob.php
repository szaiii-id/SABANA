<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AssistanceSubmission;
use App\Services\Admin\SmartCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CalculateSmartScoreJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum retry attempts.
     */
    public int $tries = 3;

    /**
     * Jitter backoff untuk retry: 5 detik → 15 detik → 30 detik.
     */
    public array $backoff = [5, 15, 30];

    /**
     * Unique lock untuk mencegah duplicate job dalam 60 detik.
     */
    public int $uniqueFor = 60;

    /**
     * Delete job jika model tidak ditemukan.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private readonly string $submissionId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SmartCalculationService $smartService): void
    {
        try {
            $submission = AssistanceSubmission::with('program')
                ->find($this->submissionId);

            if (!$submission) {
                Log::warning('SMART Job: Submission not found', [
                    'id' => $this->submissionId,
                ]);
                return;
            }

            // Skip jika program tidak punya criteria
            if (!$submission->program?->criteria) {
                Log::info('SMART Job: No criteria for program', [
                    'submission_id' => $submission->id,
                    'program_id'    => $submission->program_id,
                ]);
                return;
            }

            $score = $smartService->calculate($submission);

            $submission->update(['smart_score' => $score]);

            Log::info('SMART Job: Score calculated', [
                'submission_id' => $submission->id,
                'score'         => $score,
            ]);

        } catch (\Exception $e) {
            Log::error('SMART Job Failed', [
                'submission_id' => $this->submissionId,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            // Retry dengan jitter
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
        return "SMART_calculation:{$this->submissionId}";
    }

    /**
     * Handle a job failure after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('SMART Job Permanent Failure', [
            'submission_id' => $this->submissionId,
            'error'         => $exception->getMessage(),
        ]);
    }
}