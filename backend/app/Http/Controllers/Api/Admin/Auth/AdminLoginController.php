<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Models\ActivityLog;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AdminLoginController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $authService
    ) {}

    public function __invoke(AdminLoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $result = $this->authService->login($request->validated());

            RateLimiter::clear($request->throttleKey());

            ActivityLog::log(
                'admin',
                $result['admin']->id,
                $result['admin']->name,
                $result['admin']->role,
                'auth',
                'login',
                'Login',
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Login berhasil. Selamat bertugas.',
                'data'    => [
                    'admin' => new AdminResource($result['admin']),
                    'token' => $result['token'],
                ],
            ], 200);

        } catch (ValidationException $e) {
            $decayMinutes = config('sabana.rate_limit.decay_minutes', 5);
            RateLimiter::hit($request->throttleKey(), $decayMinutes * 60);
            throw $e;

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }
}