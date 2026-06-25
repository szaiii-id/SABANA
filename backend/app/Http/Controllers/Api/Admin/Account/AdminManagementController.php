<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Models\ActivityLog;
use App\Services\Admin\AdminManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AdminManagementController extends Controller
{
    public function __construct(
        private readonly AdminManagementService $managementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $admins = $this->managementService->getList(
                $request->user(),
                $request->only(['search', 'role', 'is_active'])
            );

            return response()->json([
                'status'       => 'success',
                'data'         => AdminResource::collection($admins),
                'current_page' => $admins->currentPage(),
                'total'        => $admins->total(),
                'last_page'    => $admins->lastPage(),
                'per_page'     => $admins->perPage(),
            ], 200);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        try {
            $admin = $this->managementService->createAdmin($request->user(), $request->validated());

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'account', 'create', 'Membuat Akun Admin',
                'admin', $admin->id, $admin->name,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Akun admin berhasil dibuat.',
                'data'    => new AdminResource($admin),
            ], 201);

        } catch (AccessDeniedHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        } catch (BadRequestHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function update(UpdateAdminRequest $request, string $id): JsonResponse
    {
        try {
            $admin = $this->managementService->updateAdmin($request->user(), $id, $request->validated());

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'account', 'update', 'Mengubah Akun Admin',
                'admin', $admin->id, $admin->name,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Data admin berhasil diperbarui.',
                'data'    => new AdminResource($admin),
            ], 200);

        } catch (NotFoundHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        } catch (BadRequestHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->managementService->deleteAdmin($request->user(), $id);

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'account', 'delete', 'Menonaktifkan Akun Admin',
                'admin', $id, null,
            );

            return response()->json(['status' => 'success', 'message' => 'Akun admin berhasil dinonaktifkan.'], 200);

        } catch (NotFoundHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        } catch (BadRequestHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function activate(Request $request, string $id): JsonResponse
    {
        try {
            $admin = $this->managementService->activateAdmin($request->user(), $id);

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'account', 'activate', 'Mengaktifkan Akun Admin',
                'admin', $admin->id, $admin->name,
            );

            return response()->json(['status' => 'success', 'message' => 'Akun berhasil diaktifkan.', 'data' => new AdminResource($admin)], 200);

        } catch (NotFoundHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function resetPassword(Request $request, string $id): JsonResponse
    {
        $request->validate(['password' => 'required|string|min:8']);

        try {
            $this->managementService->resetPassword($request->user(), $id, $request->input('password'));

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'account', 'reset_password', 'Mereset Password Admin',
                'admin', $id, null,
            );

            return response()->json(['status' => 'success', 'message' => 'Password berhasil direset.'], 200);

        } catch (NotFoundHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }
}