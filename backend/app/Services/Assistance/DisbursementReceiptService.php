<?php

namespace App\Services\Assistance;

use App\Http\Resources\DisbursementReceiptResource;
use App\Models\AssistanceSubmission;
use App\Repositories\Contracts\DisbursementReceiptRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;

class DisbursementReceiptService
{
    public function __construct(
        private DisbursementReceiptRepositoryInterface $repository
    ) {}

    public function getReceipt(string $submissionId, string $citizenId): ?AssistanceSubmission
    {
        $submission = $this->repository->findBySubmissionId($submissionId, $citizenId);

        if (!$submission) {
            throw new \Exception('Pengajuan tidak ditemukan.');
        }

        if (!$submission->disbursement) {
            throw new \Exception('Bantuan belum disalurkan.');
        }

        return $submission;
    }


    public function generatePdf(string $submissionId, string $citizenId): array
    {
        $submission = $this->getReceipt($submissionId, $citizenId);
        
        $resource = new DisbursementReceiptResource($submission);
        $data = $resource->toArray(request());

        $pdf = Pdf::loadView('pdf.disbursement_receipt', [
            'data'       => $data,
            'printed_at' => now()->format('d M Y H:i'),
        ])->setPaper('A4', 'portrait');

        return [
            'pdf'                 => $pdf,
            'registration_number' => $submission->registration_number,
        ];
    }
}