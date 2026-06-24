<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyRegistrationRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AccountVerificationController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Verify registration OTP.
     * 
     * Flow:
     * 1. Validate input via VerifyRegistrationRequest
     * 2. Call AuthService with validated data + client IP
     * 3. Return success or error response
     * 
     * Security:
     * - IP-based rate limiting (via AuthService)
     * - OTP hash verification
     * - Expiry check
     */
    public function verify(VerifyRegistrationRequest $request): JsonResponse
    {
        try {
            $this->authService->verifyRegistrationOtp(
                $request->validated(),
                $request->ip()
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Akun berhasil diverifikasi. Silakan login menggunakan NIK dan PIN Anda.',
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
     * Resend registration OTP.
     * 
     * Security:
     * - Rate limited (max 3x per NIK per minute)
     * - Cooldown check (OTP still active = reject)
     * - OTP sent via WhatsApp (async job)
     */
    public function resend(ResendOtpRequest $request): JsonResponse
    {
        try {
            $this->authService->resendRegistrationOtp($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Kode OTP baru telah berhasil dikirim ke WhatsApp Anda.',
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