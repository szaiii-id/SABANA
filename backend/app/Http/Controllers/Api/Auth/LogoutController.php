<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Logout citizen - revoke current token.
     * 
     * Middleware auth:api ensures user is authenticated.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Log aktivitas logout
            ActivityLog::log(
                'citizen',
                (string) $user->id,
                $user->full_name,
                null,
                'auth',
                'logout',
                'Logout',
            );

            // Revoke current token
            $this->authService->logout($user);

            return response()->json([
                'status'  => 'success',
                'message' => 'Anda telah keluar dari sistem.',
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