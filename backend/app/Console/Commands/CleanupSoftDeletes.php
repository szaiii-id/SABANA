<?php

namespace App\Console\Commands;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceEvidence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupSoftDeletes extends Command
{
    protected $signature = 'sabana:cleanup-soft-deletes {--dry-run : Run without actually deleting}';
    protected $description = 'Permanently delete soft-deleted records older than threshold';

    private int $submissionRetentionDays = 30;
    private int $programRetentionDays = 90;

    public function handle(): void
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted.');
        }

        $this->cleanupSubmissions($isDryRun);
        $this->cleanupPrograms($isDryRun);
        $this->cleanupOrphanedEvidences($isDryRun);

        $this->info('Cleanup completed.');
    }

    private function cleanupSubmissions(bool $isDryRun): void
    {
        $threshold = now()->subDays($this->submissionRetentionDays);

        $query = AssistanceSubmission::onlyTrashed()
            ->where('deleted_at', '<', $threshold);

        $count = $query->count();

        if ($count === 0) {
            $this->line("No submissions to cleanup.");
            return;
        }

        $this->info("Found {$count} submissions soft-deleted > {$this->submissionRetentionDays} days.");

        if ($isDryRun) {
            $this->line("Would delete {$count} submissions.");
            return;
        }

        $query->forceDelete();

        Log::info("SABANA Cleanup: {$count} submissions permanently deleted.");
        $this->info("Deleted {$count} submissions.");
    }

    private function cleanupPrograms(bool $isDryRun): void
    {
        $threshold = now()->subDays($this->programRetentionDays);

        $query = AssistanceProgram::onlyTrashed()
            ->where('deleted_at', '<', $threshold);

        $count = $query->count();

        if ($count === 0) {
            $this->line("No programs to cleanup.");
            return;
        }

        $this->info("Found {$count} programs soft-deleted > {$this->programRetentionDays} days.");

        if ($isDryRun) {
            $this->line("Would delete {$count} programs.");
            return;
        }

        $query->forceDelete();

        Log::info("SABANA Cleanup: {$count} programs permanently deleted.");
        $this->info("Deleted {$count} programs.");
    }

    private function cleanupOrphanedEvidences(bool $isDryRun): void
    {
        $query = AssistanceEvidence::whereDoesntHave('submission')
            ->orWhereHas('submission', function ($q) {
                $q->onlyTrashed()
                    ->where('deleted_at', '<', now()->subDays($this->submissionRetentionDays));
            });

        $count = $query->count();

        if ($count === 0) {
            $this->line("No orphaned evidences to cleanup.");
            return;
        }

        $this->info("Found {$count} orphaned evidences.");

        if ($isDryRun) {
            $this->line("Would delete {$count} evidences.");
            return;
        }

        $query->delete();

        Log::info("SABANA Cleanup: {$count} orphaned evidences deleted.");
        $this->info("Deleted {$count} evidences.");
    }
}