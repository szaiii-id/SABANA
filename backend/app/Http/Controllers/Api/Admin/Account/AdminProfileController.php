<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AdminProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new AdminResource(
                $request->user()->load(['regency', 'district', 'village'])
            ),
        ], 200);
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['name' => 'required|string|max:255']);

            $admin = $request->user();
            $admin->update(['name' => $validated['name']]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Profil berhasil diperbarui.',
                'data'    => new AdminResource($admin->fresh(['regency', 'district', 'village'])),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password'     => 'required|string|min:8',
            ]);

            $admin = $request->user();

            if (!Hash::check($validated['current_password'], $admin->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ]);
            }

            $admin->update(['password' => Hash::make($validated['new_password'])]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Password berhasil diubah.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }
}