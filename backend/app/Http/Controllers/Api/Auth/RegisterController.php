<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\CitizenService;
use Illuminate\Http\JsonResponse;

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
                'message' => 'Warga berhasil terdaftar',
                'data'    => $citizen
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}