<?php

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreAssistanceRequest;
use App\Http\Resources\SubmissionDetailResource;
use App\Http\Resources\SubmissionResource;
use App\Services\Assistance\AssistanceExportService;
use App\Services\Assistance\AssistanceSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistanceController extends Controller
{
    public function __construct(
        private AssistanceSubmissionService $submissionService
    ) {}

    public function store(StoreAssistanceRequest $request): JsonResponse
    {
        try {
            $files = $request->allFiles(); 

            $payload = $request->all();
            $submission = $this->submissionService->submit(
                $request->user(), 
                $payload, 
                $files
            );

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran bantuan berhasil diajukan.',
                'data' => [
                    'registration_number' => $submission->registration_number,
                    'status' => $submission->status
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show(string $registrationNumber): JsonResponse
    {
        try {
            $submission = $this->submissionService->getByRegistrationNumber($registrationNumber);
            
            return response()->json([
                'success' => true,
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan tidak ditemukan.'
            ], 404);
        }
    }


    public function destroy(Request $request, string $registrationNumber): JsonResponse
    {
        try {
            $this->submissionService->cancelSubmission(
                $registrationNumber, 
                $request->user()->id
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan berhasil dibatalkan dan berkas telah dihapus.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    public function history(Request $request): JsonResponse {
        $submissions = $this->submissionService->getCitizenHistory($request->user()->id);
        return response()->json([
            'success' => true,
            'data' => SubmissionResource::collection($submissions)
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $files = $request->allFiles(); 
            $payload = $request->all();

            $submission = $this->submissionService->update($id, $payload, $files);

            return response()->json([
                'success' => true,
                'message' => 'Perbaikan data berhasil disimpan.',
                'data' => [
                    'registration_number' => $submission->registration_number,
                    'status' => $submission->status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan perubahan: ' . $e->getMessage()
            ], 400);
        }
    }


    public function showById(string $id): JsonResponse {
        try {
            $submission = $this->submissionService->getById($id);
            return response()->json([
                'success' => true,
                'data' => new SubmissionDetailResource($submission) 
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }
    }


    public function downloadReceipt(string $id, AssistanceExportService $exportService)
    {
        try {
            $pdf = $exportService->generateReceiptPdf($id);
            
            $fileName = 'BUKTI_SABANA_' . $id . '.pdf';

            return $pdf->stream($fileName); 
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}