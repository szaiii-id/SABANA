<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Http\Resources\Admin\ProgramResource;
use App\Jobs\LogActivityJob;
use App\Services\Admin\ProgramService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class ProgramController extends Controller
{
    public function __construct(private readonly ProgramService $programService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $programs = $this->programService->getList(
                $request->only(['search', 'status'])
            );

            return response()->json([
                'status'       => 'success',
                'data'         => ProgramResource::collection($programs),
                'current_page' => $programs->currentPage(),
                'total'        => $programs->total(),
                'last_page'    => $programs->lastPage(),
                'per_page'     => $programs->perPage(),
            ], 200);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        try {
            $program = $this->programService->createProgram(
                $request->validated(),
                $request->file('banner')
            );

            dispatch(new LogActivityJob([
                'actor_type'   => 'admin',
                'actor_id'     => $request->user()->id,
                'actor_name'   => $request->user()->name,
                'actor_role'   => $request->user()->role,
                'module'       => 'program',
                'action'       => 'create',
                'action_label' => 'Membuat Program',
                'target_type'  => 'program',
                'target_id'    => $program->id,
                'target_name'  => $program->name,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'created_at'   => now(),
            ]))->afterCommit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Program berhasil dibuat.',
                'data'    => new ProgramResource($program),
            ], 201);

        } catch (\App\Exceptions\ProgramHasActiveSubmissionsException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (QueryException $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function update(UpdateProgramRequest $request, string $id): JsonResponse
    {
        try {
            $program = $this->programService->updateProgram(
                $id, $request->validated(), $request->file('banner')
            );

            dispatch(new LogActivityJob([
                'actor_type'   => 'admin',
                'actor_id'     => $request->user()->id,
                'actor_name'   => $request->user()->name,
                'actor_role'   => $request->user()->role,
                'module'       => 'program',
                'action'       => 'update',
                'action_label' => 'Mengubah Program',
                'target_type'  => 'program',
                'target_id'    => $program->id,
                'target_name'  => $program->name,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'created_at'   => now(),
            ]))->afterCommit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Program berhasil diperbarui.',
                'data'    => new ProgramResource($program),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (QueryException $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->programService->deleteProgram($id);

            dispatch(new LogActivityJob([
                'actor_type'   => 'admin',
                'actor_id'     => $request->user()->id,
                'actor_name'   => $request->user()->name,
                'actor_role'   => $request->user()->role,
                'module'       => 'program',
                'action'       => 'delete',
                'action_label' => 'Menghapus Program',
                'target_type'  => 'program',
                'target_id'    => $id,
                'target_name'  => null,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'created_at'   => now(),
            ]))->afterCommit();

            return response()->json(['status' => 'success', 'message' => 'Program berhasil dihapus.'], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (\App\Exceptions\ProgramHasActiveSubmissionsException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (QueryException $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function uploadBanner(string $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'banner' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $program = $this->programService->uploadBanner($id, $request->file('banner'));

            dispatch(new LogActivityJob([
                'actor_type'   => 'admin',
                'actor_id'     => $request->user()->id,
                'actor_name'   => $request->user()->name,
                'actor_role'   => $request->user()->role,
                'module'       => 'program',
                'action'       => 'upload_banner',
                'action_label' => 'Mengunggah Banner',
                'target_type'  => 'program',
                'target_id'    => $id,
                'target_name'  => $program->name,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'created_at'   => now(),
            ]))->afterCommit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Banner berhasil diunggah.',
                'data'    => new ProgramResource($program),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (QueryException $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function close(string $id): JsonResponse
    {
        try {
            $program = $this->programService->closeProgram($id);

            dispatch(new LogActivityJob([
                'actor_type'   => 'admin',
                'actor_id'     => request()->user()->id,
                'actor_name'   => request()->user()->name,
                'actor_role'   => request()->user()->role,
                'module'       => 'program',
                'action'       => 'close',
                'action_label' => 'Menutup Program',
                'target_type'  => 'program',
                'target_id'    => $id,
                'target_name'  => $program->name,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'created_at'   => now(),
            ]))->afterCommit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Program berhasil ditutup.',
                'data'    => new ProgramResource($program),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (QueryException $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function reopen(string $id): JsonResponse
    {
        try {
            $program = $this->programService->reopenProgram($id);

            dispatch(new LogActivityJob([
                'actor_type'   => 'admin',
                'actor_id'     => request()->user()->id,
                'actor_name'   => request()->user()->name,
                'actor_role'   => request()->user()->role,
                'module'       => 'program',
                'action'       => 'reopen',
                'action_label' => 'Membuka Kembali Program',
                'target_type'  => 'program',
                'target_id'    => $id,
                'target_name'  => $program->name,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'created_at'   => now(),
            ]))->afterCommit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Program berhasil dibuka kembali.',
                'data'    => new ProgramResource($program),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (QueryException $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function duplicate(string $id): JsonResponse
    {
        try {
            $program = $this->programService->duplicateProgram($id);

            dispatch(new LogActivityJob([
                'actor_type'   => 'admin',
                'actor_id'     => request()->user()->id,
                'actor_name'   => request()->user()->name,
                'actor_role'   => request()->user()->role,
                'module'       => 'program',
                'action'       => 'duplicate',
                'action_label' => 'Menduplikasi Program',
                'target_type'  => 'program',
                'target_id'    => $program->id,
                'target_name'  => $program->name,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'created_at'   => now(),
            ]))->afterCommit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Program berhasil diduplikasi.',
                'data'    => new ProgramResource($program),
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (QueryException $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function activePrograms(Request $request): JsonResponse
    {
        try {
            $programs = $this->programService->getActiveProgramsWithSubmissionStatus(
                $request->query('citizen_id')
            );

            return response()->json([
                'status' => 'success',
                'data'   => ProgramResource::collection($programs),
            ], 200);

        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }
}