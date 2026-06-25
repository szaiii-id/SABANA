<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkStoreDisbursementRequest;
use App\Http\Requests\Admin\StoreDisbursementRequest;
use App\Http\Resources\Admin\DisbursementResource;
use App\Models\ActivityLog;
use App\Services\Admin\DisbursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class DisbursementController extends Controller
{
    public function __construct(
        private readonly DisbursementService $disbursementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $submissions = $this->disbursementService->getList(
                $request->user(),
                $request->only(['program_id', 'search'])
            );

            return response()->json([
                'status' => 'success',
                'data'   => DisbursementResource::collection($submissions),
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    public function store(StoreDisbursementRequest $request): JsonResponse
    {
        try {
            $this->disbursementService->disburse(
                $request->validated('submission_id'),
                $request->user(),
                $request->validated('notes')
            );

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'disbursement', 'disburse', 'Menyalurkan Bantuan',
                'submission', $request->validated('submission_id'), null,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Bantuan berhasil disalurkan.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function bulkStore(BulkStoreDisbursementRequest $request): JsonResponse
    {
        try {
            $count = 0;
            $errors = [];

            foreach ($request->validated('submission_ids') as $id) {
                try {
                    $this->disbursementService->disburse($id, $request->user(), $request->validated('notes'));
                    $count++;

                    ActivityLog::log(
                        'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                        'disbursement', 'disburse', 'Menyalurkan Bantuan',
                        'submission', $id, null, 
                    );

                } catch (\Exception $e) {
                    $errors[] = ['submission_id' => $id, 'error' => $e->getMessage()];
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => "{$count} bantuan berhasil disalurkan.",
                'count'   => $count,
                'errors'  => $errors,
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }
}