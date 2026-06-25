<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Jobs\SendWhatsAppJob;
use App\Jobs\SubmissionIndexingJob;
use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Repositories\Contracts\VerificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class VerificationService
{
    public function __construct(
        private VerificationRepositoryInterface $repository,
        private SmartCalculationService $smartService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | LIST & DETAIL
    |--------------------------------------------------------------------------
    */

    public function getList(Admin $admin, array $filters): LengthAwarePaginator
    {
        return $this->repository->getByWilayah($admin, $filters);
    }

    public function getDetail(string $id): ?AssistanceSubmission
    {
        return $this->repository->findById($id);
    }

    /*
    |--------------------------------------------------------------------------
    | AKSI VERIFIKASI
    |--------------------------------------------------------------------------
    */

    public function approve(string $id, Admin $actor): AssistanceSubmission
    {
        $submission = DB::transaction(function () use ($id, $actor) {
            $submission = $this->repository->findByIdWithLock($id);

            $this->validateStatus($submission, 'pending', 'disetujui');
            $this->validateProgramActive($submission);
            $this->validateQuotaAvailable($submission);

            $this->repository->updateStatus($submission, 'validated');

            $this->repository->createVerificationRecord([
                'submission_id' => $submission->id,
                'admin_id'      => $actor->id,
                'admin_name'    => $actor->name,
                'action_type'   => 'approved',
            ]);

            // ✅ Jika kuota penuh setelah approve, tutup program
            $this->closeProgramIfQuotaFull($submission);

            $this->repository->clearVerificationListCache();

            return $submission;
        });

        $this->sendNotification($submission, 'DISETUJUI');
        SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

        return $submission->fresh();
    }

    public function reject(string $id, string $notes, Admin $actor): AssistanceSubmission
    {
        $submission = DB::transaction(function () use ($id, $notes, $actor) {
            $submission = $this->repository->findByIdWithLock($id);

            $this->validateStatus($submission, 'pending', 'ditolak');

            if (empty(trim($notes))) {
                throw new \Exception('Alasan penolakan wajib diisi.');
            }

            $this->repository->updateStatus($submission, 'rejected');

            $this->repository->createVerificationRecord([
                'submission_id' => $submission->id,
                'admin_id'      => $actor->id,
                'admin_name'    => $actor->name,
                'action_type'   => 'rejected',
                'notes'         => trim($notes),
            ]);

            $this->repository->clearVerificationListCache();

            return $submission;
        });

        $this->sendNotification($submission, 'DITOLAK', $notes);
        SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

        return $submission->fresh();
    }

    public function requestRevision(
        string $id,
        string $notes,
        Admin $actor,
        array $revisionItems = []
    ): AssistanceSubmission {
        $submission = DB::transaction(function () use ($id, $notes, $actor, $revisionItems) {
            $submission = $this->repository->findByIdWithLock($id);

            $this->validateStatus($submission, 'pending', 'diminta revisi');

            if (empty(trim($notes))) {
                throw new \Exception('Catatan revisi wajib diisi.');
            }

            $this->repository->updateStatus($submission, 'needs_revision');

            $this->repository->createVerificationRecord([
                'submission_id'  => $submission->id,
                'admin_id'       => $actor->id,
                'admin_name'     => $actor->name,
                'action_type'    => 'revision_requested',
                'notes'          => trim($notes),
                'revision_items' => $revisionItems,
            ]);

            $this->repository->clearVerificationListCache();

            return $submission;
        });

        $this->sendNotification($submission, 'DIMINTA PERBAIKAN', $notes);
        SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

        return $submission->fresh();
    }

    public function complete(string $id, Admin $actor): AssistanceSubmission
    {
        $submission = DB::transaction(function () use ($id, $actor) {
            $submission = $this->repository->findByIdWithLock($id);

            $this->validateStatus($submission, 'validated', 'diselesaikan');

            $submission->update([
                'status'              => 'completed',
                'last_submission_date' => now(),
            ]);

            $this->repository->createVerificationRecord([
                'submission_id' => $submission->id,
                'admin_id'      => $actor->id,
                'admin_name'    => $actor->name,
                'action_type'   => 'completed',
            ]);

            $this->repository->clearVerificationListCache();

            return $submission;
        });

        $this->sendNotification($submission, 'SELESAI DISALURKAN');
        SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

        return $submission->fresh();
    }

    public function unvalidate(string $id, string $notes, Admin $actor): AssistanceSubmission
    {
        $submission = DB::transaction(function () use ($id, $notes, $actor) {
            $submission = $this->repository->findByIdWithLock($id);

            $this->validateStatus($submission, 'validated', 'dibatalkan persetujuannya');

            if (empty(trim($notes))) {
                throw new \Exception('Alasan pembatalan wajib diisi.');
            }

            $this->repository->updateStatus($submission, 'pending');

            // ✅ Jika program tertutup karena kuota, cek apakah bisa dibuka kembali
            $this->reopenProgramIfQuotaAvailable($submission);

            $this->repository->createVerificationRecord([
                'submission_id' => $submission->id,
                'admin_id'      => $actor->id,
                'admin_name'    => $actor->name,
                'action_type'   => 'unvalidated',
                'notes'         => trim($notes),
            ]);

            $this->repository->clearVerificationListCache();

            return $submission;
        });

        $this->sendNotification($submission, 'DIBATALKAN PERSETUJUANNYA', $notes);
        SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

        return $submission->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | BULK COMPLETE
    |--------------------------------------------------------------------------
    */

    public function bulkComplete(array $ids, Admin $actor): int
    {
        return DB::transaction(function () use ($ids, $actor) {
            $submissions = [];
            foreach ($ids as $id) {
                $submission = $this->repository->findByIdWithLock($id);

                if (!$submission) {
                    throw new \Exception("Pengajuan dengan ID {$id} tidak ditemukan.");
                }

                if ($submission->status !== 'validated') {
                    throw new \Exception("Pengajuan {$submission->registration_number} tidak dalam status validated.");
                }

                $submissions[] = $submission;
            }

            foreach ($submissions as $submission) {
                $submission->update([
                    'status'              => 'completed',
                    'last_submission_date' => now(),
                ]);

                $this->repository->createVerificationRecord([
                    'submission_id' => $submission->id,
                    'admin_id'      => $actor->id,
                    'admin_name'    => $actor->name,
                    'action_type'   => 'completed',
                ]);
            }

            $this->repository->clearVerificationListCache();

            return count($submissions);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPERS
    |--------------------------------------------------------------------------
    */

    private function validateStatus(?AssistanceSubmission $submission, string $expected, string $actionLabel): void
    {
        if (!$submission) {
            throw new \Exception('Pengajuan tidak ditemukan.');
        }

        if ($submission->status !== $expected) {
            throw new \Exception("Hanya pengajuan dengan status '{$expected}' yang bisa {$actionLabel}.");
        }
    }

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

    private function validateQuotaAvailable(AssistanceSubmission $submission): void
    {
        $program = $submission->program;

        if (!$program || !$program->quota_total || $program->quota_total <= 0) {
            return;
        }

        $approvedCount = AssistanceSubmission::where('program_id', $program->id)
            ->excludeEvaluation()
            ->where('id', '!=', $submission->id)
            ->whereIn('status', AssistanceSubmission::QUOTA_STATUSES)
            ->whereNull('deleted_at')
            ->count();

        if ($approvedCount >= $program->quota_total) {
            throw new \Exception("Kuota program '{$program->name}' sudah terpenuhi ({$program->quota_total}/{$program->quota_total}).");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PROGRAM STATUS HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Tutup program jika kuota validated + completed sudah penuh.
     */
    private function closeProgramIfQuotaFull(AssistanceSubmission $submission): void
    {
        $program = $submission->program;

        if (!$program || !$program->quota_total || $program->quota_total <= 0) {
            return;
        }

        $count = AssistanceSubmission::where('program_id', $program->id)
            ->excludeEvaluation()
            ->whereIn('status', ['validated', 'completed'])
            ->count();

        if ($count >= $program->quota_total && $program->status === 'active') {
            $program->update(['status' => 'closed']);

            Log::info('Program closed after approve', [
                'program_id'   => $program->id,
                'program_name' => $program->name,
                'count'        => $count,
                'quota'        => $program->quota_total,
            ]);
        }
    }

    /**
     * Buka kembali program jika kuota validated + completed masih tersedia.
     */
    private function reopenProgramIfQuotaAvailable(AssistanceSubmission $submission): void
    {
        $program = $submission->program;

        if (!$program || !$program->quota_total || $program->quota_total <= 0) {
            return;
        }

        if ($program->status !== 'closed') {
            return;
        }

        $count = AssistanceSubmission::where('program_id', $program->id)
            ->excludeEvaluation()
            ->whereIn('status', ['validated', 'completed'])
            ->count();

        if ($count < $program->quota_total) {
            $program->update(['status' => 'active']);

            Log::info('Program reopened after unvalidate', [
                'program_id'   => $program->id,
                'program_name' => $program->name,
                'count'        => $count,
                'quota'        => $program->quota_total,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION
    |--------------------------------------------------------------------------
    */

    private function sendNotification(AssistanceSubmission $submission, string $statusLabel, ?string $notes = null): void
    {
        $message = "*[SABANA KALSEL]*\n\n"
                . "Halo {$submission->citizen->full_name},\n\n"
                . "Pengajuan Anda dengan nomor *{$submission->registration_number}* telah *{$statusLabel}*.\n\n";

        if ($notes) {
            $message .= "Catatan: {$notes}\n\n";
        }

        $message .= "Silakan cek aplikasi SABANA untuk informasi lebih lanjut.";

        SendWhatsAppJob::dispatch($submission->citizen->whatsapp_number, $message);
    }

    /*
    |--------------------------------------------------------------------------
    | SMART
    |--------------------------------------------------------------------------
    */

    public function getRecommendation(?float $score, ?array $customThresholds = null): array
    {
        if ($score === null) {
            return ['label' => 'Belum Dinilai', 'color' => 'gray'];
        }

        $thresholds = $customThresholds ?? config('sabana.smart.thresholds', [
            'highly_recommended' => 0.70,
            'recommended'        => 0.50,
            'considered'         => 0.30,
        ]);

        if ($score >= $thresholds['highly_recommended']) {
            return ['label' => 'Sangat Direkomendasikan', 'color' => 'green'];
        }
        if ($score >= $thresholds['recommended']) {
            return ['label' => 'Direkomendasikan', 'color' => 'yellow'];
        }
        if ($score >= $thresholds['considered']) {
            return ['label' => 'Dipertimbangkan', 'color' => 'orange'];
        }
        return ['label' => 'Tidak Direkomendasikan', 'color' => 'red'];
    }
}