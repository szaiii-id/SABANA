<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\CitizenService;
use Illuminate\Http\JsonResponse;
use Exception;

class RegisterController extends Controller
{
    protected $service;

    public function __construct(CitizenService $service)
    {
        $this->service = $service;
    }

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            $citizen = $this->service->registerCitizen($request->validated());
            
            return response()->json([
                'status'  => 'success',
                'message' => 'Kode verifikasi telah dikirim melalui WhatsApp.',
                'data'    => [
                    'nik' => $citizen->nik,
                    'whatsapp_number' => $citizen->whatsapp_number,
                    'full_name' => $citizen->full_name
                ]
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}