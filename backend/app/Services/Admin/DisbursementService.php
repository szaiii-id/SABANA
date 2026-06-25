<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Jobs\SendWhatsAppJob;
use App\Jobs\SubmissionIndexingJob;
use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Repositories\Contracts\DisbursementRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class DisbursementService
{
    public function __construct(
        private DisbursementRepositoryInterface $repository
    ) {}

    public function getList(Admin $admin, array $filters): LengthAwarePaginator
    {
        return $this->repository->getByWilayah($admin, $filters);
    }

    public function disburse(string $submissionId, Admin $officer, ?string $notes = null): void
    {
        DB::transaction(function () use ($submissionId, $officer, $notes) {
            $submission = AssistanceSubmission::with('program', 'citizen')
                ->lockForUpdate()
                ->findOrFail($submissionId);

            if ($submission->status !== 'validated') {
                throw new \Exception('Hanya submission yang sudah disetujui yang bisa disalurkan.');
            }

            if ($submission->disbursement()->exists()) {
                throw new \Exception('Bantuan sudah disalurkan.');
            }

            $disbursement = $this->repository->create([
                'submission_id' => $submission->id,
                'program_id'    => $submission->program_id,
                'citizen_id'    => $submission->citizen_id,
                'amount'        => $submission->program->benefit_amount ?? 0,
                'method'        => $submission->disbursement_method,
                'disbursed_by'  => $officer->id,
                'notes'         => $notes,
            ]);

            $submission->update([
                'status'              => 'completed',
                'last_submission_date' => now(),
            ]);

            Log::info('Disbursement completed', [
                'submission_id' => $submission->id,
                'amount'        => $disbursement->amount,
                'officer_id'    => $officer->id,
            ]);

            // Kirim notifikasi after commit
            SendWhatsAppJob::dispatch(
                $submission->citizen->whatsapp_number,
                $this->buildMessage($submission, $disbursement)
            )->afterCommit();

            // Indexing Elasticsearch
            SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();
        });
    }

    private function buildMessage(AssistanceSubmission $submission, $disbursement): string
    {
        return "*[SABANA KALSEL - PENYALURAN]*\n\n"
            . "Halo {$submission->citizen->full_name},\n\n"
            . "Bantuan *{$submission->program->name}* sebesar Rp "
            . number_format((float) $disbursement->amount, 0, ',', '.')
            . " telah disalurkan.\n"
            . "No. Referensi: *{$disbursement->reference_number}*\n\n"
            . "Terima kasih.";
    }
}