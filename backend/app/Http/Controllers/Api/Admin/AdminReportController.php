<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\ActivityLog;
use App\Services\Admin\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Throwable;

final class AdminReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    private function adminWithRegion($request)
    {
        return $request->user()->load('regency', 'district', 'village');
    }

    private function logActivity($request, string $reportName): void
    {
        try {
            ActivityLog::log(
                'admin',
                $request->user()->id,
                $request->user()->name,
                $request->user()->role,
                'report',
                'cetak',
                "Mencetak Laporan: {$reportName}",
            );
        } catch (Throwable) {
            // Silent
        }
    }

    private function streamPdf(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data);
        $pdf->getDomPDF()->set_option("isPhpEnabled", true);

        return $pdf->stream($filename);
    }

    // ===== LAPORAN 1 =====

    public function budgetSummary(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->budgetSummary($filters);
        $this->logActivity($request, 'Detail Anggaran Program');

        return $this->streamPdf('reports.admin.budget-summary', [
            'data'    => $data,
            'judul'   => 'Detail Anggaran Program',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'ringkasan-anggaran-program.pdf');
    }

    // ===== LAPORAN 2 =====

    public function programRecipients(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->programRecipients($filters);
        $this->logActivity($request, 'Daftar Penerima per Program');

        return $this->streamPdf('reports.admin.program-recipients', [
            'data'    => $data,
            'judul'   => 'Daftar Penerima per Program',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'daftar-penerima-program.pdf');
    }

    // ===== LAPORAN 3 =====

    public function mostAppliedPrograms(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->mostAppliedPrograms($filters);
        $this->logActivity($request, 'Program Paling Banyak Diminati');

        return $this->streamPdf('reports.admin.most-applied-programs', [
            'data'    => $data,
            'judul'   => 'Program Paling Banyak Diminati',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'program-paling-diminati.pdf');
    }

    // ===== LAPORAN 4 =====

    public function citizenRegisteredByAdmin(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->citizenRegisteredByAdmin($filters);
        $this->logActivity($request, 'Pendaftaran Warga Dibantu Petugas');

        return $this->streamPdf('reports.admin.citizen-registered-by-admin', [
            'data'    => $data,
            'judul'   => 'Pendaftaran Warga Dibantu Petugas',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'warga-didaftarkan-admin.pdf');
    }

    // ===== LAPORAN 5 =====

    public function readyForDisbursement(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->readyForDisbursement($filters);
        $this->logActivity($request, 'Data Warga Siap Disalurkan');

        return $this->streamPdf('reports.admin.ready-for-disbursement', [
            'data'    => $data,
            'judul'   => 'Data Warga Siap Disalurkan',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'data-warga-siap-disalurkan.pdf');
    }

    // ===== LAPORAN 6 =====

    public function pendingEvaluation(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->pendingEvaluation($filters);
        $this->logActivity($request, 'Data Warga Akan Dievaluasi');

        return $this->streamPdf('reports.admin.pending-evaluation', [
            'data'    => $data,
            'judul'   => 'Data Warga Akan Dievaluasi',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'data-warga-akan-dievaluasi.pdf');
    }

    // ===== LAPORAN 7 =====

    public function revokedRecipients(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->revokedRecipients($filters);
        $this->logActivity($request, 'Data Warga Bantuan Dihentikan');

        return $this->streamPdf('reports.admin.revoked-recipients', [
            'data'    => $data,
            'judul'   => 'Data Warga Bantuan Dihentikan',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'data-warga-bantuan-dihentikan.pdf');
    }

    // ===== LAPORAN 8 =====

    public function approvedRecipients(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->approvedRecipients($filters);
        $this->logActivity($request, 'Data Warga Tetap Menerima Bantuan');

        return $this->streamPdf('reports.admin.approved-recipients', [
            'data'    => $data,
            'judul'   => 'Data Warga Tetap Menerima Bantuan',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'data-warga-tetap-menerima.pdf');
    }

    // ===== LAPORAN 9 =====

    public function disbursedRecipients(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $data = $this->reportService->disbursedRecipients($filters);
        $this->logActivity($request, 'Data Warga Sudah Disalurkan');

        return $this->streamPdf('reports.admin.disbursed-recipients', [
            'data'    => $data,
            'judul'   => 'Data Warga Sudah Disalurkan',
            'filters' => $filters,
            'admin'   => $this->adminWithRegion($request),
        ], 'data-warga-sudah-disalurkan.pdf');
    }
}