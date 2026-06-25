<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdminLogoutController extends Controller
{
    protected AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Tidak ada sesi aktif.',
                ], 401);
            }

            ActivityLog::log(
                'admin',
                $user->id,
                $user->name,
                $user->role,
                'auth',
                'logout',
                'Logout',
            );

            $this->authService->logout($user);

            return response()->json([
                'status'  => 'success',
                'message' => 'Sesi telah berakhir.',
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }
}