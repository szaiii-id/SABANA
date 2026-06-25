<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Jobs\SendWhatsAppJob;
use App\Jobs\SubmissionIndexingJob;
use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Models\EvaluationLog;
use App\Repositories\Contracts\EvaluationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class EvaluationService
{
    public function __construct(
        private EvaluationRepositoryInterface $repository
    ) {}

    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    */

    public function getList(Admin $admin, array $filters): LengthAwarePaginator
    {
        $cacheKey = 'evaluations:list:' . md5(serialize([
            'admin_id' => $admin->id,
            'role'     => $admin->role,
            'filters'  => $filters,
        ]));

        return Cache::tags(['evaluations'])->remember($cacheKey, 60, function () use ($admin, $filters) {
            return $this->repository->getByWilayah($admin, $filters);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */

    /**
     * Setujui evaluasi — lanjutkan bantuan.
     */
    public function approve(string $evaluationLogId, Admin $actor): AssistanceSubmission
    {
        $submission = DB::transaction(function () use ($evaluationLogId, $actor) {
            $evaluationLog = EvaluationLog::where('id', $evaluationLogId)
                ->where('status', 'updated')
                ->lockForUpdate()
                ->first();

            if (!$evaluationLog) {
                throw new \Exception('Log evaluasi tidak ditemukan atau sudah diputuskan.');
            }

            if (!$evaluationLog->new_submission_id) {
                throw new \Exception('Submission evaluasi belum tersedia.');
            }

            $submission = AssistanceSubmission::where('id', $evaluationLog->new_submission_id)
                ->lockForUpdate()
                ->first();

            if (!$submission) {
                throw new \Exception('Submission tidak ditemukan.');
            }

            // Validasi bisnis
            $this->validateProgramActive($submission);

            // Update submission
            $submission->update([
                'status'              => 'validated',
                'last_submission_date' => now(),
            ]);

            // Update evaluation log
            $this->repository->updateStatus($evaluationLog, [
                'status'     => 'approved',
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            Log::info('Evaluation approved', [
                'evaluation_id' => $evaluationLogId,
                'submission_id' => $submission->id,
                'admin_id'      => $actor->id,
            ]);

            return $submission;
        });

        // Kirim notifikasi (after commit)
        SendWhatsAppJob::dispatch(
            $submission->citizen->whatsapp_number,
            $this->buildApprovedMessage($submission)
        )->afterCommit();

        // Indexing Elasticsearch (after commit)
        SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

        // Clear cache
        $this->clearEvaluationCache();

        return $submission->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | REVOKE
    |--------------------------------------------------------------------------
    */

    /**
     * Tolak evaluasi — hentikan bantuan.
     */
    public function revoke(string $evaluationLogId, string $notes, Admin $actor): AssistanceSubmission
    {
        $submission = DB::transaction(function () use ($evaluationLogId, $notes, $actor) {
            $evaluationLog = EvaluationLog::where('id', $evaluationLogId)
                ->where('status', 'updated')
                ->lockForUpdate()
                ->first();

            if (!$evaluationLog) {
                throw new \Exception('Log evaluasi tidak ditemukan atau sudah diputuskan.');
            }

            if (!$evaluationLog->new_submission_id) {
                throw new \Exception('Submission evaluasi belum tersedia.');
            }

            if (empty(trim($notes))) {
                throw new \Exception('Alasan penolakan evaluasi wajib diisi.');
            }

            $submission = AssistanceSubmission::where('id', $evaluationLog->new_submission_id)
                ->lockForUpdate()
                ->first();

            if (!$submission) {
                throw new \Exception('Submission tidak ditemukan.');
            }

            // Update submission baru → rejected
            $submission->update([
                'status'              => 'rejected',
                'last_submission_date' => now(),
            ]);

            // Update submission lama → revoked
            if ($evaluationLog->submission_id) {
                $oldSubmission = AssistanceSubmission::where('id', $evaluationLog->submission_id)
                    ->lockForUpdate()
                    ->first();

                if ($oldSubmission) {
                    $oldSubmission->update(['status' => 'revoked']);
                }
            }

            // Update evaluation log
            $this->repository->updateStatus($evaluationLog, [
                'status'         => 'revoked',
                'decision_notes' => trim($notes),
                'decided_by'     => $actor->id,
                'decided_at'     => now(),
            ]);

            Log::info('Evaluation revoked', [
                'evaluation_id' => $evaluationLogId,
                'submission_id' => $submission->id,
                'admin_id'      => $actor->id,
            ]);

            return $submission;
        });

        // Kirim notifikasi (after commit)
        SendWhatsAppJob::dispatch(
            $submission->citizen->whatsapp_number,
            $this->buildRevokedMessage($submission, trim($notes))
        )->afterCommit();

        // Indexing Elasticsearch (after commit)
        SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

        // Clear cache
        $this->clearEvaluationCache();

        return $submission->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPERS
    |--------------------------------------------------------------------------
    */

    private function validateProgramActive(AssistanceSubmission $submission): void
    {
        $program = $submission->program;

        if (!$program) {
            throw new \Exception('Program tidak ditemukan.');
        }

        $now = now();

        if ($program->start_date && $now->lt($program->start_date)) {
            throw new \Exception("Program '{$program->name}' belum dimulai. Mulai tanggal: {$program->start_date->format('d M Y')}.");
        }

        if ($program->end_date && $now->gt($program->end_date)) {
            throw new \Exception("Program '{$program->name}' sudah berakhir pada: {$program->end_date->format('d M Y')}.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION MESSAGES
    |--------------------------------------------------------------------------
    */

    private function buildApprovedMessage(AssistanceSubmission $submission): string
    {
        return "*[SABANA KALSEL - EVALUASI]*\n\n"
            . "Halo {$submission->citizen->full_name},\n\n"
            . "Hasil evaluasi program *{$submission->program->name}* Anda telah *DISETUJUI*.\n\n"
            . "Bantuan Anda akan dilanjutkan. Silakan cek aplikasi SABANA untuk informasi lebih lanjut.";
    }

    private function buildRevokedMessage(AssistanceSubmission $submission, string $notes): string
    {
        return "*[SABANA KALSEL - EVALUASI]*\n\n"
            . "Halo {$submission->citizen->full_name},\n\n"
            . "Hasil evaluasi program *{$submission->program->name}* Anda telah *DITOLAK dan DIHENTIKAN*.\n\n"
            . "Catatan: {$notes}\n\n"
            . "Bantuan Anda dihentikan. Silakan hubungi Petugas Desa.";
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE
    |--------------------------------------------------------------------------
    */

    private function clearEvaluationCache(): void
    {
        Cache::tags(['evaluations'])->flush();
    }
}