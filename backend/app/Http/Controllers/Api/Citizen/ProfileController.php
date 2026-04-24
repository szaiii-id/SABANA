<?php

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\UpdateProfileRequest;
use App\Services\CitizenProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private CitizenProfileService $citizenProfileService) {}

    public function show(Request $request): JsonResponse
    {
        $citizen = $request->user();

        return response()->json([
            'data' => [
                'nik' => $citizen->nik,
                'family_card_number' => $citizen->family_card_number,
                'full_name' => $citizen->full_name,
                'whatsapp_number' => $citizen->whatsapp_number,
            ]
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $this->citizenProfileService->updateProfile(
                $request->user(),
                $request->validated()
            );

            return response()->json(['message' => 'Data diri berhasil diperbarui.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }
}
