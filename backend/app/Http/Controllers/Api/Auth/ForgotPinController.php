<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPinRequest;
use App\Http\Requests\ResetPinRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ForgotPinController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Request OTP for forgot PIN.
     * Security: IP-based rate limiting (via AuthService)
     */
    public function sendOtp(ForgotPinRequest $request): JsonResponse
    {
        try {
            $this->authService->requestOtp(
                $request->validated(),
                $request->ip()
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'PIN sementara telah dikirim ke nomor WhatsApp Anda.',
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

    /**
     * Reset PIN after OTP verification.
     * Security: All tokens revoked after PIN change.
     */
    public function resetPin(ResetPinRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPin($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'PIN berhasil diubah. Silakan login menggunakan PIN baru Anda.',
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