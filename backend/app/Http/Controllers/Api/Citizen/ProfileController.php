<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\UpdateProfileRequest;
use App\Http\Resources\CitizenResource;
use App\Services\CitizenProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly CitizenProfileService $citizenProfileService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new CitizenResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $citizen = $this->citizenProfileService->updateProfile(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Data diri berhasil diperbarui.',
                'data'    => new CitizenResource($citizen),
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