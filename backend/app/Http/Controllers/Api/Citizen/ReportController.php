<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\SendWhatsappRequest;
use App\Http\Requests\Citizen\SendEmailRequest;
use App\Services\CitizenReportService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ReportController extends Controller
{
    public function __construct(
        private readonly CitizenReportService $citizenReportService,
    ) {}

    public function whatsapp(SendWhatsappRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->citizenReportService->sendViaWhatsapp(
                $request->user(),
                $validated['pesan']
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Laporan berhasil diteruskan ke WhatsApp Admin.',
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }

    public function email(SendEmailRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->citizenReportService->sendViaEmail(
                $request->user(),
                $validated['subjek'],
                $validated['pesan'],
                $validated['email']
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Laporan berhasil dikirim ke Email Instansi.',
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }
}