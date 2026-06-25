<?php

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisbursementReceiptResource;
use App\Services\Assistance\DisbursementReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class DisbursementReceiptController extends Controller
{
    public function __construct(
        private DisbursementReceiptService $receiptService
    ) {}

    public function show(string $submissionId): JsonResponse
    {
        try {
            $submission = $this->receiptService->getReceipt(
                $submissionId,
                request()->user()->id
            );

            return response()->json([
                'status' => 'success',
                'data'   => new DisbursementReceiptResource($submission),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function pdf(string $submissionId): Response
    {
        try {
            $result = $this->receiptService->generatePdf(
                $submissionId,
                request()->user()->id
            );

            return $result['pdf']->download(
                'Bukti_Penyaluran_' . $result['registration_number'] . '.pdf'
            );

        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }
}