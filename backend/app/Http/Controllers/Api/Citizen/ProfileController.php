<?php

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\UpdateProfileRequest;
use App\Http\Resources\CitizenResource;
use App\Services\CitizenProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private CitizenProfileService $citizenProfileService) {}

    public function show(Request $request): JsonResponse {
        return response()->json([
            'data' => new CitizenResource($request->user())
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
