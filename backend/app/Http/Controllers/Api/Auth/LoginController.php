<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\CitizenResource;
use App\Models\ActivityLog;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

final class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Authenticate citizen with NIK + PIN.
     * 
     * Security:
     * - IP-based rate limiting (via AuthService)
     * - Admin-registered citizens forced to change PIN
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $results = $this->authService->login(
                $request->validated(),
                $request->ip()
            );

            // Simpan citizen asli untuk ActivityLog (sebelum di-wrap Resource)
            $citizen = $results['citizen'];

            // Wrap dengan Resource untuk response
            $results['citizen'] = new CitizenResource($citizen);

            // Log aktivitas login
            ActivityLog::log(
                'citizen',
                $citizen->id ?? null,
                $citizen->full_name ?? null,
                null,
                'auth',
                'login',
                'Login',
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Login berhasil.',
                'data'    => [
                    'citizen'            => $results['citizen'],
                    'token'              => $results['token'],
                    'require_pin_change' => $results['require_pin_change'] ?? false,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }
}