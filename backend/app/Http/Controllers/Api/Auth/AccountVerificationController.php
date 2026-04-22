<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyRegistrationRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AccountVerificationController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function verify(VerifyRegistrationRequest $request): JsonResponse
    {
        $this->authService->verifyRegistrationOtp($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Akun berhasil diverifikasi. Silakan login menggunakan NIK dan PIN Anda.'
        ]);
    }

    public function resend(ResendOtpRequest $request): JsonResponse
    {
        $this->authService->resendRegistrationOtp($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Kode OTP baru telah berhasil dikirim ke WhatsApp Anda.'
        ]);
    }
}