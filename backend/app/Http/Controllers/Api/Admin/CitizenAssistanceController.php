<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubmitCitizenAssistanceRequest;
use App\Http\Resources\Admin\SubmittedAssistanceResource;
use App\Models\AdminAssistanceLog;
use App\Models\Citizen;
use App\Services\Assistance\AssistanceSubmissionService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class CitizenAssistanceController extends Controller
{
    public function __construct(
        private readonly AssistanceSubmissionService $submissionService
    ) {}

    public function store(SubmitCitizenAssistanceRequest $request): JsonResponse
    {
        try {
            $admin   = $request->user();
            $citizen = Citizen::find($request->citizen_id);

            if (!$citizen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Warga tidak ditemukan.',
                ], 404);
            }

            $files   = $request->allFiles();
            $payload = $request->all();
            unset($payload['citizen_id']);

            $submission = $this->submissionService->submit(
                $citizen,
                $payload,
                $files
            );

            AdminAssistanceLog::create([
                'admin_id'      => $admin->id,
                'admin_name'    => $admin->name,
                'admin_role'    => $admin->role,
                'citizen_id'    => $citizen->id,
                'submission_id' => $submission->id,
                'program_id'    => $payload['program_id'],
                'action'        => 'submit',
                'metadata'      => [
                    'registration_number' => $submission->registration_number,
                    'program_name'        => $submission->program->name ?? null,
                    'citizen_nik'         => $citizen->nik,
                    'citizen_name'        => $citizen->full_name,
                ],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengajuan bantuan berhasil dibuat.',
                'data'    => new SubmittedAssistanceResource($submission),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }
}