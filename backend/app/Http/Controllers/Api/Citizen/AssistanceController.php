<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreAssistanceRequest;
use App\Http\Resources\SubmissionDetailResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Citizen;
use App\Services\Assistance\AssistanceExportService;
use App\Services\Assistance\AssistanceSubmissionService;
use App\Exceptions\SubmissionException;
use App\Http\Resources\SubmissionHistoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class AssistanceController extends Controller
{
    public function __construct(
        private readonly AssistanceSubmissionService $submissionService,
    ) {}

    // ===== STORE =====

    public function store(StoreAssistanceRequest $request): JsonResponse
    {
        try {
            $submission = $this->submissionService->submit(
                $this->citizen($request),
                $request->all(),
                $request->allFiles(),
                $request->header('X-Idempotency-Key')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran bantuan berhasil diajukan.',
                'data'    => [
                    'registration_number' => $submission->registration_number,
                    'status'              => $submission->status,
                ],
            ], 201);

        } catch (SubmissionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    // ===== SHOW =====

    public function show(string $registrationNumber): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => $this->submissionService->getByRegistrationNumber($registrationNumber),
            ]);
        } catch (Throwable) {
            return response()->json(['success' => false, 'message' => 'Data pengajuan tidak ditemukan.'], 404);
        }
    }

    // ===== DESTROY =====

    public function destroy(Request $request, string $registrationNumber): JsonResponse
    {
        try {
            $this->submissionService->cancelSubmission($registrationNumber, $this->citizen($request)->id);

            return response()->json(['success' => true, 'message' => 'Pengajuan berhasil dibatalkan.']);
        } catch (SubmissionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    // ===== HISTORY =====

    public function history(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 10);
            $result = $this->submissionService->getCitizenHistory($this->citizen($request)->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => SubmissionHistoryResource::collection($result),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page'    => $result->lastPage(),
                    'total'        => $result->total(),
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    // ===== UPDATE =====

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $submission = $this->submissionService->update($id, $request->all(), $request->allFiles());

            return response()->json([
                'success' => true,
                'message' => 'Perbaikan data berhasil disimpan.',
                'data'    => ['registration_number' => $submission->registration_number, 'status' => $submission->status],
            ]);
        } catch (SubmissionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    // ===== SHOW BY ID =====

    public function showById(string $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => new SubmissionDetailResource($this->submissionService->getById($id)),
            ]);
        } catch (Throwable) {
            return response()->json(['success' => false, 'message' => 'Data pengajuan tidak ditemukan.'], 404);
        }
    }

    // ===== DOWNLOAD RECEIPT =====

    public function downloadReceipt(string $id, AssistanceExportService $exportService): mixed
    {
        try {
            return $exportService->generateReceiptPdf($id)->download('BUKTI_SABANA_' . $id . '.pdf');
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() === 403 ? 403 : 404);
        }
    }

    // ===== HELPER =====

    private function citizen(Request $request): Citizen
    {
        return $request->user();
    }
}