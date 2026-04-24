<?php

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\SendWhatsappRequest;
use App\Http\Requests\Citizen\SendEmailRequest;
use App\Services\CitizenReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    // Hanya butuh CitizenReportService di sini
    public function __construct(private CitizenReportService $citizenReportService) {}

    public function whatsapp(SendWhatsappRequest $request): JsonResponse
    {
        try {
            $this->citizenReportService->sendViaWhatsapp(
                $request->user(),
                $request->validated('pesan')
            );

            // KEMBALIKAN KEY KE 'message' AGAR VUE BISA MEMBACA NOTIFIKASINYA
            return response()->json(['message' => 'Laporan berhasil diteruskan ke WhatsApp Admin.']);
        } catch (\Exception $e) {
            Log::error('WA Controller Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengirim laporan WhatsApp.'], 500);
        }
    }

    public function email(SendEmailRequest $request): JsonResponse
    {
        try {
            // Ambil data dari validasi (subjek, pesan, email) lalu lempar ke Service
            $this->citizenReportService->sendViaEmail(
                $request->user(),
                $request->validated('subjek'),
                $request->validated('pesan'),
                $request->validated('email')
            );

            // KEMBALIKAN KEY KE 'message'
            return response()->json(['message' => 'Laporan berhasil dikirim ke Email Instansi.']);
        } catch (\Exception $e) {
            Log::error('Email Controller Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengirim laporan email.'], 500);
        }
    }
}