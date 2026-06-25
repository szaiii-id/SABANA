<?php

namespace App\Services\Assistance;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AssistanceSubmission;
use Carbon\Carbon;

class AssistanceExportService
{

    public function generateReceiptPdf(string $id)
    {
        $submission = AssistanceSubmission::with([
            'citizen',
            'program',
            'evidences',
            'regency.province',
            'regency',
            'district',
            'village',
            'verifications'
        ])->find($id);

        if (!$submission) {
            throw new \Exception('Data pengajuan tidak ditemukan.', 404);
        }

        if ($submission->status === 'needs_revision') {
            throw new \Exception('Tidak dapat download PDF. Silakan lengkapi perbaikan terlebih dahulu.', 403);
        }

        $data = [
            'title'      => 'BUKTI PENDAFTARAN SABANA',
            'date'       => Carbon::now()->format('d/m/Y H:i') . ' WITA',
            'submission' => $submission
        ];

        $pdf = Pdf::loadView('pdf.assistance_receipt', $data);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}