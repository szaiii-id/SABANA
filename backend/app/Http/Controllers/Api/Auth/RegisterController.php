<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\CitizenResource;
use App\Services\CitizenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Throwable;

final class RegisterController extends Controller
{
    public function __construct(
        private readonly CitizenService $service,
    ) {}

    // ===== REGISTER =====

    /**
     * Register a new citizen (self-registration via mobile app).
     * 
     * Flow:
     * 1. Validate input via RegisterRequest
     * 2. Call CitizenService::registerCitizen()
     * 3. Return citizen resource with 201
     * 
     * Error handling:
     * - Business exceptions → 422 with user-friendly message
     * - System exceptions → 500 with generic message (logged)
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            $citizen = $this->service->registerCitizen($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Kode verifikasi telah dikirim melalui WhatsApp.',
                'data'    => new CitizenResource($citizen),
            ], 201);
        } catch (\Throwable $e) {
            if ($e instanceof \Exception) {
                // Business errors → 422
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            // System errors → 500
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }

    // ===== PREFILL REGISTRATION DATA =====

    /**
     * Get prefill data for unverified citizen.
     * 
     * Used when citizen needs to fix incorrect WhatsApp number
     * before OTP verification.
     * 
     * Query params: ?nik=6301234567890123
     */
    public function prefill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]+$/'],
        ]);

        try {
            $data = $this->service->getRegistrationData($validated['nik']);

            return response()->json([
                'status' => 'success',
                'data'   => $data,
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof \Exception) {
                // Business errors → 422
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            // System errors → 500
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }
}