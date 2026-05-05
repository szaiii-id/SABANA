<?php

namespace App\Services\Assistance;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AssistanceSubmission;

class AssistanceExportService
{
    /**
     * Generate PDF Bukti Pendaftaran
     */
    public function generateReceiptPdf(string $id)
    {
        // Load data lengkap dengan relasi
        $submission = AssistanceSubmission::with([
            'citizen', 
            'program', 
            'evidences', 
            'regency.province', // Tambahkan ini
            'regency',  // Tambahkan ini
            'district', // Tambahkan ini
            'village'
         ])->findOrFail($id);

        // Data yang dikirim ke view Blade
        $data = [
            'title' => 'BUKTI PENDAFTARAN SABANA',
            'date'  => date('d/m/Y'),
            'submission' => $submission
        ];

        // Load view khusus PDF (Kita buat nanti)
        $pdf = Pdf::loadView('pdf.assistance_receipt', $data);

        // Atur ukuran kertas (A4 potrait)
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}