<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegisterCitizenRequest;
use App\Http\Resources\Admin\RegisteredCitizenResource;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\CitizenRegistrationLog;
use App\Services\Admin\CitizenRegistrationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CitizenRegistrationController extends Controller
{
    public function __construct(
        private CitizenRegistrationService $registrationService
    ) {}

    // ===== INDEX (LIST) - PostgreSQL =====

    public function index(Request $request): JsonResponse
    {
        try {
            $citizens = $this->registrationService->getCitizenList(
                $request->only(['search'])
            );

            return response()->json([
                'status'       => 'success',
                'data'         => RegisteredCitizenResource::collection($citizens),
                'current_page' => $citizens->currentPage(),
                'total'        => $citizens->total(),
                'last_page'    => $citizens->lastPage(),
                'per_page'     => $citizens->perPage(),
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== SEARCH (Dropdown) - Elasticsearch =====

    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:1|max:100',
            ]);

            $results = $this->registrationService->searchCitizen($request->q);

            return response()->json([
                'status' => 'success',
                'data'   => $results,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter pencarian minimal 1 karakter.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== SEARCH PAGINATED - Elasticsearch =====

    public function searchPaginated(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q'        => 'required|string|min:1|max:100',
                'page'     => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:5|max:50',
            ]);

            $results = $this->registrationService->searchCitizenPaginated(
                $request->q,
                (int) $request->get('page', 1),
                (int) $request->get('per_page', 10)
            );

            return response()->json([
                'status' => 'success',
                'data'   => $results,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter pencarian minimal 1 karakter.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== STORE - Register Warga =====

    public function store(RegisterCitizenRequest $request): JsonResponse
    {
        try {
            if ($request->with_pin) {
                $result = $this->registrationService->registerWithPin(
                    $request->validated(),
                    $request->user()->id
                );

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Warga berhasil didaftarkan. PIN akses telah dikirim via WhatsApp.',
                    'data'    => [
                        'citizen'    => new RegisteredCitizenResource($result['citizen']),
                        'access_pin' => $result['access_pin'],
                    ],
                ], 201);
            }

            $result = $this->registrationService->registerWithoutPin(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Warga berhasil didaftarkan.',
                'data'    => [
                    'citizen'    => new RegisteredCitizenResource($result['citizen']),
                    'access_pin' => $result['access_pin'],
                ],
            ], 201);
        } catch (Throwable $e) {
            if ($e instanceof \Exception) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== SHOW - Detail Warga =====

    public function show(string $id): JsonResponse
    {
        try {
            $citizen = Citizen::find($id);

            if (!$citizen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Warga tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => new RegisteredCitizenResource($citizen),
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== UPDATE - Edit Data Warga =====

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $citizen = Citizen::find($id);

            if (!$citizen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Warga tidak ditemukan.',
                ], 404);
            }

            $request->validate([
                'nik'                => 'sometimes|string|size:16|regex:/^63[0-9]{14}$/',
                'full_name'          => 'sometimes|string|min:3|max:255|regex:/^[a-zA-Z\s\.\,\-\'\/]+$/',
                'family_card_number' => 'sometimes|string|size:16|regex:/^[0-9]+$/',
                'whatsapp_number'    => 'sometimes|string|min:10|max:15|regex:/^[0-9]+$/',
            ]);

            $citizen = $this->registrationService->updateCitizen(
                $id,
                $request->only(['nik', 'full_name', 'family_card_number', 'whatsapp_number']),
                $request->user()->id
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Data warga berhasil diperbarui.',
                'data'    => new RegisteredCitizenResource($citizen),
            ], 200);
        } catch (Throwable $e) {
            if ($e instanceof \Exception) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== RESEND PIN =====

    public function resendPin(Request $request, string $id): JsonResponse
    {
        try {
            $citizen = Citizen::find($id);

            if (!$citizen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Warga tidak ditemukan.',
                ], 404);
            }

            $result = $this->registrationService->resendAccessPin(
                $id,
                $request->user()->id
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'PIN akses baru telah dikirim via WhatsApp.',
                'data'    => $result,
            ], 200);
        } catch (Throwable $e) {
            if ($e instanceof \Exception) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== LOGS - Riwayat Pendaftaran =====

    public function logs(string $id): JsonResponse
    {
        try {
            $citizen = Citizen::find($id);

            if (!$citizen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Warga tidak ditemukan.',
                ], 404);
            }

            $logs = CitizenRegistrationLog::where('citizen_id', $id)
                ->latest()
                ->get()
                ->map(function ($log) {
                    return [
                        'id'         => $log->id,
                        'admin_name' => $log->admin_name,
                        'admin_role' => $log->admin_role,
                        'action'     => $log->action,
                        'metadata'   => $log->metadata,
                        'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data'   => $logs,
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== SUBMISSIONS - Riwayat Pengajuan =====

    public function submissions(string $id): JsonResponse
    {
        try {
            $citizen = Citizen::find($id);

            if (!$citizen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Warga tidak ditemukan.',
                ], 404);
            }

            $submissions = AssistanceSubmission::where('citizen_id', $id)
                ->with('program')
                ->latest()
                ->get()
                ->map(function ($sub) {
                    return [
                        'id'                  => $sub->id,
                        'registration_number' => $sub->registration_number,
                        'status'              => $sub->status,
                        'program'             => [
                            'id'   => $sub->program->id ?? null,
                            'name' => $sub->program->name ?? null,
                        ],
                        'created_at'          => $sub->created_at?->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data'   => $submissions,
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // ===== RESET PIN & PRINT CARD =====

    public function resetAndPreviewCard(string $id, Request $request)
    {
        try {
            $citizen = Citizen::find($id);

            if (!$citizen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Warga tidak ditemukan.',
                ], 404);
            }

            $result = $this->registrationService->resetPinAndGetCardData(
                $id,
                $request->user()->id
            );

            $pdf = Pdf::loadView('pdf.citizen_access_card', [
                'citizen'   => $result['citizen'],
                'accessPin' => $result['access_pin'],
                'adminName' => $request->user()->name,
                'date'      => now()->format('d M Y H:i'),
            ]);

            $pdf->setPaper([0, 0, 297.64, 209.76], 'landscape');

            return $pdf->stream('Kartu_Akses_' . $result['citizen']->full_name . '.pdf');
        } catch (Throwable $e) {
            if ($e instanceof \Exception) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }
}