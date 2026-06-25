<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveEvaluationRequest;
use App\Http\Requests\Admin\RevokeEvaluationRequest;
use App\Http\Resources\Admin\EvaluationResource;
use App\Models\ActivityLog;
use App\Services\Admin\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $evaluations = $this->evaluationService->getList(
                $request->user(),
                $request->only(['status'])
            );

            return response()->json([
                'status' => 'success',
                'data'   => EvaluationResource::collection($evaluations),
                'meta'   => ['current_role' => $request->user()->role],
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    public function approve(string $id, ApproveEvaluationRequest $request): JsonResponse
    {
        try {
            $this->evaluationService->approve($id, $request->user());
            
            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'evaluation', 'approve', 'Menyetujui Evaluasi',
                'evaluation', $id, null,
            );



            return response()->json([
                'status'  => 'success',
                'message' => 'Evaluasi disetujui. Bantuan dilanjutkan.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function revoke(string $id, RevokeEvaluationRequest $request): JsonResponse
    {
        try {
            $this->evaluationService->revoke($id, $request->validated('notes'), $request->user());

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'evaluation', 'revoke', 'Menolak Evaluasi',
                'evaluation', $id, null,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Evaluasi ditolak. Bantuan dihentikan.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }
}