<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AspirasiRequest;
use App\Services\AspirasiService;
use Illuminate\Http\JsonResponse;

class AspirasiController extends Controller
{
    protected $aspirasiService;

    public function __construct(AspirasiService $aspirasiService)
    {
        $this->aspirasiService = $aspirasiService;
    }

    public function store(AspirasiRequest $request): JsonResponse
    {
        try {
            $this->aspirasiService->prosesDanKirimAspirasi($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Aspirasi berhasil terkirim'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim aspirasi: ' . $e->getMessage()
            ], 500);
        }
    }
}