<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectSubmissionRequest;
use App\Http\Resources\Admin\VerificationDetailResource;
use App\Http\Resources\Admin\VerificationResource;
use App\Models\ActivityLog;
use App\Services\Admin\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class VerificationController extends Controller
{
    public function __construct(private readonly VerificationService $verificationService) {}

    private function verificationResource($submission): VerificationResource
    {
        return (new VerificationResource($submission))
            ->withVerificationService($this->verificationService);
    }

    private function verificationDetailResource($submission): VerificationDetailResource
    {
        return (new VerificationDetailResource($submission))
            ->withVerificationService($this->verificationService);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $submissions = $this->verificationService->getList(
                $request->user(),
                $request->only(['status', 'program_id', 'search'])
            );

            return response()->json([
                'status'       => 'success',
                'data'         => $submissions->map(fn($s) => $this->verificationResource($s)),
                'current_page' => $submissions->currentPage(),
                'total'        => $submissions->total(),
                'last_page'    => $submissions->lastPage(),
                'per_page'     => $submissions->perPage(),
            ], 200);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $submission = $this->verificationService->getDetail($id);

            if (!$submission) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Pengajuan tidak ditemukan.',
                ], 404);
            }

            Log::info('Verification detail viewed', [
                'submission_id' => $id,
                'admin_id'      => $request->user()?->id,
                'admin_name'    => $request->user()?->name,
                'ip'            => $request->ip(),
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $this->verificationDetailResource($submission),
            ], 200);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $submission = $this->verificationService->approve($id, $request->user());

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'verification', 'approve', 'Menyetujui Pengajuan',
                'submission', $id, $submission->registration_number,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengajuan berhasil disetujui.',
                'data'    => $this->verificationResource($submission),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function reject(RejectSubmissionRequest $request, string $id): JsonResponse
    {
        try {
            $submission = $this->verificationService->reject(
                $id, $request->validated()['notes'], $request->user()
            );

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'verification', 'reject', 'Menolak Pengajuan',
                'submission', $id, $submission->registration_number,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengajuan berhasil ditolak.',
                'data'    => $this->verificationResource($submission),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function requestRevision(RejectSubmissionRequest $request, string $id): JsonResponse
    {
        try {
            $submission = $this->verificationService->requestRevision(
                $id,
                $request->validated()['notes'],
                $request->user(),
                $request->validated()['revision_items'] ?? []
            );

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'verification', 'request_revision', 'Meminta Perbaikan',
                'submission', $id, $submission->registration_number,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Permintaan perbaikan berhasil dikirim.',
                'data'    => $this->verificationResource($submission),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        try {
            $submission = $this->verificationService->complete($id, $request->user());

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'verification', 'complete', 'Menandai Selesai',
                'submission', $id, $submission->registration_number,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengajuan berhasil ditandai selesai.',
                'data'    => $this->verificationResource($submission),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function unvalidate(RejectSubmissionRequest $request, string $id): JsonResponse
    {
        try {
            $submission = $this->verificationService->unvalidate(
                $id, $request->validated()['notes'], $request->user()
            );

            ActivityLog::log(
                'admin', $request->user()->id, $request->user()->name, $request->user()->role,
                'verification', 'unvalidate', 'Membatalkan Persetujuan',
                'submission', $id, $submission->registration_number,
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Persetujuan berhasil dibatalkan. Pengajuan kembali ke pending.',
                'data'    => $this->verificationResource($submission),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }

    public function bulkComplete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'string|exists:assistance_submissions,id',
        ]);

        try {
            $count = $this->verificationService->bulkComplete(
                $request->validated()['ids'],
                $request->user()
            );

            return response()->json([
                'status'  => 'success',
                'message' => "{$count} pengajuan berhasil ditandai selesai.",
                'data'    => ['count' => $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada sistem.'], 500);
        }
    }
}