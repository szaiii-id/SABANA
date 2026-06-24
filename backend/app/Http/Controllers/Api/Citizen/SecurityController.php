<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\UpdatePinRequest;
use App\Models\ActivityLog;
use App\Models\CitizenRegistrationLog;
use App\Services\CitizenAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;
use Exception;

final class SecurityController extends Controller
{
    public function __construct(
        private readonly CitizenAuthService $citizenAuthService,
    ) {}

    /**
     * Update PIN for authenticated citizen.
     * 
     * Security:
     * - Rate limited by citizen ID
     * - Current PIN must match
     * - Deletes admin registration log (force PIN change flag)
     */
    public function updatePin(UpdatePinRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->citizenAuthService->updatePin(
                $request->user(),
                $validated['current_pin'],
                $validated['new_pin']
            );

            ActivityLog::log(
                'citizen', (string) $request->user()->id, $request->user()->full_name, null,
                'account', 'update_pin', 'Mengubah PIN',
            );

            // Hapus flag force PIN change setelah PIN diganti
            CitizenRegistrationLog::where('citizen_id', $request->user()->id)->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'PIN berhasil diperbarui. Silakan login ulang.',
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