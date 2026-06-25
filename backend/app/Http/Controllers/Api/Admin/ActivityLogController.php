<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'module'     => 'nullable|string',
                'actor_name' => 'nullable|string',
                'actor_role' => 'nullable|string',
                'from'       => 'nullable|date',
                'to'         => 'nullable|date',
                'per_page'   => 'nullable|integer|min:10|max:100',
            ]);

            $logs = $this->activityLogService->getAll(
                $validated,
                (int) ($validated['per_page'] ?? 15)
            );

            return response()->json([
                'status' => 'success',
                'data'   => $logs->items(),
                'meta'   => [
                    'current_page' => $logs->currentPage(),
                    'total'        => $logs->total(),
                    'last_page'    => $logs->lastPage(),
                    'per_page'     => $logs->perPage(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data filter tidak valid.',
                'errors'  => $e->errors(),
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