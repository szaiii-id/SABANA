<?php

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\UpdatePinRequest;
use App\Services\CitizenAuthService;
use Illuminate\Http\JsonResponse;

class SecurityController extends Controller
{
    public function __construct(private CitizenAuthService $citizenAuthService) {}

    public function updatePin(UpdatePinRequest $request): JsonResponse
    {
        try {
            $this->citizenAuthService->updatePin(
                $request->user(),
                $request->validated('current_pin'),
                $request->validated('new_pin')
            );

            return response()->json(['message' => 'PIN berhasil diperbarui.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}