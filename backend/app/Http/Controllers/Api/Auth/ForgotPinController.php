<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPinRequest;
use App\Http\Requests\ResetPinRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForgotPinController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function sendOtp(ForgotPinRequest $request): JsonResponse
    {
        $this->authService->requestOtp($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'PIN sementara telah dikirim ke nomor WhatsApp Anda.'
        ]);
    }

    public function resetPin(ResetPinRequest $request): JsonResponse
    {
        $this->authService->resetPin($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'PIN berhasil diubah. Silakan login menggunakan PIN baru Anda.'
        ]);
    }

}