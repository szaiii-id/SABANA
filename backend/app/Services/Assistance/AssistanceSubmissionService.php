<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Contracts\Storage\FileStorageInterface;
use App\Exceptions\SubmissionException;
use App\Jobs\AnalyzeEvidenceJob;
use App\Jobs\CalculateSmartScoreJob;
use App\Jobs\SendWhatsAppJob;
use App\Jobs\SubmissionIndexingJob;
use App\Models\AssistanceProgram;
use App\Models\EvaluationLog;
use App\Repositories\Contracts\AssistanceRepositoryInterface;
use App\Services\Admin\SmartCalculationService;
use App\Services\Ai\AnomalyDetectionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class AssistanceSubmissionService
{
    public function __construct(
        private readonly AssistanceRepositoryInterface $repository,
        private readonly FileStorageInterface $storage,
        private readonly SmartCalculationService $smartService,
    ) {}

    // ===== SUBMIT =====

    public function submit(object $citizen, array $payload, array $files, ?string $idempotencyKey = null): mixed
    {
        $rateLimitKey = 'submit-assistance:' . $citizen->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $minutes = ceil(RateLimiter::availableIn($rateLimitKey) / 60);
            throw new SubmissionException("Terlalu banyak pengajuan. Silakan coba lagi dalam {$minutes} menit.");
        }

        $uploadedPublicIds = [];

        try {
            $submission = DB::transaction(function () use ($citizen, $payload, $files, &$uploadedPublicIds, $idempotencyKey, $rateLimitKey) {

                if ($idempotencyKey) {
                    $exists = $this->repository->findByIdempotencyKey($citizen->id, $idempotencyKey);
                    if ($exists) {
                        throw new SubmissionException('Pengajuan sedang diproses. Harap tunggu sebentar.');
                    }
                }

                $program = AssistanceProgram::where('id', $payload['program_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$program) {
                    throw new SubmissionException('Program tidak ditemukan.');
                }

                if ($program->start_date && now()->lessThan($program->start_date)) {
                    throw new SubmissionException('Program belum dibuka untuk pendaftaran.');
                }

                if ($program->end_date && now()->greaterThan($program->end_date)) {
                    throw new SubmissionException('Program sudah melewati batas tanggal pendaftaran.');
                }

                if ($program->quota_total) {
                    $submissionCount = $this->repository->countActiveByProgram($program->id);
                    if ($submissionCount >= $program->quota_total) {
                        throw new SubmissionException('Kuota program sudah penuh. Silakan pilih program lain.');
                    }
                }

                $staticKeys = [
                    'program_id', 'regency_id', 'district_id', 'village_id',
                    'disbursement_method', 'bank_account_number', 'citizen_id',
                    'is_evaluation', 'files',
                ];

                $allowedInputKeys = collect($program->criteria['inputs'] ?? [])->pluck('key')->toArray();

                $dynamicData = collect($payload)
                    ->except($staticKeys)
                    ->filter(fn($value) => !($value instanceof UploadedFile))
                    ->only($allowedInputKeys) 
                    ->toArray();

                $dynamicData['nik'] = $citizen->nik;
                $dynamicData['full_name'] = $citizen->full_name;
                $dynamicData['family_card_number'] = $citizen->family_card_number;
                $dynamicData['_idempotency_key'] = $idempotencyKey;

                $submission = $this->repository->createSubmission([
                    'citizen_id'           => $citizen->id,
                    'program_id'           => $payload['program_id'],
                    'regency_id'           => $payload['regency_id'],
                    'district_id'          => $payload['district_id'],
                    'village_id'           => $payload['village_id'],
                    'registration_number'  => 'SBN-' . strtoupper(str()->random(8)),
                    'status'               => 'pending',
                    'submission_data'      => $dynamicData,
                    'disbursement_method'  => $payload['disbursement_method'],
                    'bank_account_number'  => $payload['bank_account_number'] ?? null,
                    'last_submission_date' => now(),
                ]);

                foreach ($files as $fileKey => $file) {
                    if ($file instanceof UploadedFile) {
                        if ($file->getSize() > 2048 * 1024) {
                            throw new SubmissionException("Ukuran file {$fileKey} melebihi batas 2MB.");
                        }
                        $upload = $this->storage->upload($file, 'submissions/' . $submission->registration_number);
                        $uploadedPublicIds[] = $upload['public_id'];
                        $this->repository->storeEvidence([
                            'submission_id'   => $submission->id,
                            'image_type'      => $fileKey,
                            'image_url'       => $upload['url'],
                            'cloud_public_id' => $upload['public_id'],
                        ]);
                    }
                }

                RateLimiter::hit($rateLimitKey, 3600);

                return $submission;
            });

            foreach ($submission->evidences as $evidence) {
                AnalyzeEvidenceJob::dispatch($evidence->id, $this->buildAiFields($submission))
                    ->afterCommit();
            }
            CalculateSmartScoreJob::dispatch($submission->id)->afterCommit();
            SubmissionIndexingJob::dispatch($submission->id, 'index')->afterCommit();

            return $submission;

        } catch (SubmissionException $e) {
            $this->cleanupUploads($uploadedPublicIds);
            throw $e;
        } catch (\Throwable $e) {
            $this->cleanupUploads($uploadedPublicIds);
            Log::error('SUBMISSION_FAILED: ' . $e->getMessage());
            throw new SubmissionException('Gagal memproses pengajuan: ' . $e->getMessage());
        }
    }

    // ===== GET BY REGISTRATION NUMBER =====

    public function getByRegistrationNumber(string $registrationNumber): mixed
    {
        return $this->repository->findByRegistrationNumber($registrationNumber);
    }

    // ===== CANCEL SUBMISSION =====

    public function cancelSubmission(string $registrationNumber, string $citizenId): bool
    {
        return DB::transaction(function () use ($registrationNumber, $citizenId) {
            $submission = $this->repository->findByRegistrationNumber($registrationNumber);
            $publicIds = $submission->evidences->pluck('cloud_public_id')->filter()->toArray();
            $this->repository->deleteByRegistrationNumber($registrationNumber, $citizenId);

            foreach ($publicIds as $publicId) {
                $this->storage->delete($publicId);
            }

            app(AnomalyDetectionService::class)->clearAnomaliesForRelatedSubmissions($submission);

            return true;
        });
    }

    // ===== GET CITIZEN HISTORY =====

    public function getCitizenHistory(string $citizenId, int $perPage = 10): LengthAwarePaginator
    {
        $allSubmissions = $this->repository->getAllHistoryByCitizenId($citizenId);
        
        $grouped = $this->groupAndBuildTimeline($allSubmissions);
        
        $page = (int) request()->get('page', 1);
        
        return new LengthAwarePaginator(
            $grouped->forPage($page, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
    // ===== UPDATE =====

    public function update(string $id, array $payload, array $files): mixed
    {
        $submission = $this->repository->findById($id);
        $key = 'update-assistance:' . $submission->citizen_id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = ceil(RateLimiter::availableIn($key) / 60);
            throw new SubmissionException("Terlalu banyak perubahan. Silakan coba lagi dalam {$minutes} menit.");
        }

        $isEvaluation = $payload['is_evaluation'] ?? false;

        $result = DB::transaction(function () use ($id, $payload, $files, $isEvaluation) {

            $submission = $this->repository->findById($id);

            $staticKeys = [
                'program_id', 'regency_id', 'district_id', 'village_id',
                'disbursement_method', 'bank_account_number', 'citizen_id',
                'is_evaluation', 'files',
            ];

            $allowedInputKeys = collect($submission->program->criteria['inputs'] ?? [])->pluck('key')->toArray();

            $dynamicData = collect($payload)->except($staticKeys)
                ->filter(fn($v) => !($v instanceof UploadedFile))
                ->only($allowedInputKeys)
                ->toArray();

            $citizen = $submission->citizen;
            $dynamicData['nik'] = $citizen->nik;
            $dynamicData['full_name'] = $citizen->full_name;
            $dynamicData['family_card_number'] = $citizen->family_card_number;

            // ===== EVALUASI: Buat submission BARU =====
            if ($isEvaluation) {
                $newSubmission = $this->repository->createSubmission([
                    'citizen_id'           => $citizen->id,
                    'program_id'           => $submission->program_id,
                    'regency_id'           => $payload['regency_id'],
                    'district_id'          => $payload['district_id'],
                    'village_id'           => $payload['village_id'],
                    'registration_number'  => 'SBN-' . strtoupper(str()->random(8)),
                    'status'               => 'pending',
                    'submission_data'      => $dynamicData,
                    'disbursement_method'  => $payload['disbursement_method'],
                    'bank_account_number'  => $payload['bank_account_number'] ?? null,
                    'last_submission_date' => now(),
                ]);

                // Upload file ke submission BARU
                foreach ($files as $key => $file) {
                    if ($file instanceof UploadedFile) {
                        if ($file->getSize() > 2048 * 1024) {
                            throw new SubmissionException("Ukuran file {$key} melebihi batas 2MB.");
                        }
                        $upload = $this->storage->upload($file, 'submissions/' . $newSubmission->registration_number);
                        $this->repository->storeEvidence([
                            'submission_id'   => $newSubmission->id,
                            'image_type'      => $key,
                            'image_url'       => $upload['url'],
                            'cloud_public_id' => $upload['public_id'],
                        ]);
                    }
                }

                // Update EvaluationLog: link ke submission BARU
                EvaluationLog::where('submission_id', $submission->id)
                    ->where('status', 'triggered')
                    ->update([
                        'status'            => 'updated',
                        'new_submission_id' => $newSubmission->id,
                    ]);

                // Submission LAMA tetap 'evaluation_pending'
                $submission->update(['last_submission_date' => now()]);

                // Notif WA
                if ($citizen->whatsapp_number) {
                    $message = "*[SABANA KALSEL - EVALUASI]*\n\n"
                        . "Halo {$citizen->full_name},\n\n"
                        . "Data evaluasi program *{$submission->program->name}* Anda telah diterima.\n"
                        . "Menunggu verifikasi selanjutnya.\n\n"
                        . "Terima kasih.";

                    SendWhatsAppJob::dispatch($citizen->whatsapp_number, $message)
                        ->afterCommit();
                }

                return $newSubmission;
            }

            // ===== REVISI: Update submission yang SAMA =====
            $submission->update([
                'regency_id'          => $payload['regency_id'],
                'district_id'         => $payload['district_id'],
                'village_id'          => $payload['village_id'],
                'status'              => 'pending',
                'submission_data'     => $dynamicData,
                'disbursement_method' => $payload['disbursement_method'],
                'bank_account_number' => $payload['bank_account_number'] ?? null,
            ]);

            foreach ($files as $key => $file) {
                if ($file instanceof UploadedFile) {

                    $oldEvidence = $submission->evidences()->where('image_type', $key)->first();

                    if ($oldEvidence && $oldEvidence->cloud_public_id) {
                        try {
                            $this->storage->delete($oldEvidence->cloud_public_id);
                        } catch (\Exception $e) {
                            Log::warning("Gagal menghapus foto lama: " . $oldEvidence->cloud_public_id);
                        }
                    }

                    $upload = $this->storage->upload($file, 'submissions/' . $submission->registration_number);

                    $submission->evidences()->updateOrCreate(
                        ['image_type' => $key],
                        [
                            'image_url'       => $upload['url'],
                            'cloud_public_id' => $upload['public_id'],
                        ]
                    );
                }
            }

            return $submission;
        });

        // afterCommit: dispatch jobs
        foreach ($result->evidences as $evidence) {
            AnalyzeEvidenceJob::dispatch($evidence->id, $this->buildAiFields($result))
                ->afterCommit();
        }
        CalculateSmartScoreJob::dispatch($result->id)
            ->afterCommit();

        RateLimiter::hit($key, 1800);

        return $result;
    }

    // ===== GET BY ID =====

    public function getById(string $id): mixed
    {
        try {
            return $this->repository->findById($id)->load(['program', 'evidences']);
        } catch (\Exception $e) {
            throw new SubmissionException('Data pengajuan tidak ditemukan.', 404);
        }
    }

    // ===== ATTACH SUBMISSION STATUS =====

    public function attachSubmissionStatus($programs, string $citizenId): mixed
    {
        return $programs->each(function ($program) use ($citizenId) {
            $program->has_submitted = $this->repository
                ->hasActiveSubmission($citizenId, $program->id);
        });
    }

    // ===== PRIVATE METHODS =====

    private function buildAiFields($submission): array
    {
        $criteria = $submission->program->criteria ?? [];
        $inputs = $criteria['inputs'] ?? [];
        $submissionData = $submission->submission_data ?? [];

        $fields = [];

        foreach ($inputs as $input) {
            $fields[] = [
                'label' => $input['label'],
                'key'   => $input['key'],
                'value' => $submissionData[$input['key']] ?? '',
            ];
        }

        return $fields;
    }

    private function cleanupUploads(array $publicIds): void
    {
        foreach ($publicIds as $publicId) {
            try {
                $this->storage->delete($publicId);
            } catch (\Exception $e) {
                Log::warning('Gagal membersihkan: ' . $publicId);
            }
        }
    }

    private function groupAndBuildTimeline(Collection $allSubmissions): Collection
    {
        return $allSubmissions
            ->groupBy('program_id')
            ->map(function (Collection $submissions) {
                
                $sorted = $submissions->sortBy('created_at');
                $first = $sorted->first();
                $latest = $sorted->last();
                
                // Build timeline dari semua status
                $timeline = $sorted->map(function ($sub) {
                    return [
                        'id'     => $sub->id,
                        'status' => $sub->status,
                        'label'  => $this->getTimelineLabel($sub->status),
                        'date'   => $sub->created_at->format('Y-m-d'),
                        'color'  => $this->getTimelineColor($sub->status),
                    ];
                })->values()->toArray();
                
                // Tambahkan node akhir jika hasil evaluasi
                if ($latest->id !== $first->id) {
                    if ($latest->status === 'validated') {
                        $timeline[] = [
                            'id'     => $latest->id,
                            'status' => 'evaluation_approved',
                            'label'  => 'Dilanjutkan',
                            'date'   => $latest->created_at->format('Y-m-d'),
                            'color'  => 'green',
                        ];
                    } elseif (in_array($latest->status, ['rejected', 'revoked'])) {
                        $timeline[] = [
                            'id'     => $latest->id,
                            'status' => 'evaluation_revoked',
                            'label'  => 'Dihentikan',
                            'date'   => $latest->created_at->format('Y-m-d'),
                            'color'  => 'red',
                        ];
                    }
                }
                
                return [
                    'program_id'           => $first->program_id,
                    'program_name'         => $first->program->name ?? null,
                    'registration_number'  => $first->registration_number,
                    'current_status'       => $latest->status,
                    'current_status_label' => $this->getTimelineLabel($latest->status),
                    'is_evaluation'        => $first->status === 'evaluation_pending',
                    'timeline'             => $timeline,
                    'latest_submission_id' => $latest->id,
                    'latest_submission'    => $latest,
                    'program'              => $first->program,
                    'revision_items' => $latest->revision_items ?? [],
                    'admin_note'     => $latest->verifications()
                                            ->whereIn('action_type', ['revision_requested', 'rejected'])
                                            ->latest()
                                            ->value('notes'),
                                    ];
            })
            ->sortByDesc(fn($item) => $item['latest_submission']->created_at ?? '0')
            ->values();
    }

    private function getTimelineLabel(string $status): string
    {
        return match ($status) {
            'pending'              => 'Diajukan',
            'needs_revision'       => 'Perlu Revisi',
            'validated'            => 'Disetujui',
            'completed'            => 'Disalurkan',
            'rejected'             => 'Ditolak',
            'evaluation_pending'   => 'Evaluasi 6 Bulan',
            'revoked'              => 'Dihentikan',
            'evaluation_approved'  => 'Dilanjutkan',
            'evaluation_revoked'   => 'Dihentikan',
            default                => $status,
        };
    }

    private function getTimelineColor(string $status): string
    {
        return match ($status) {
            'pending'              => 'blue',
            'needs_revision'       => 'yellow',
            'validated'            => 'green',
            'completed'            => 'green',
            'rejected'             => 'red',
            'evaluation_pending'   => 'orange',
            'revoked'              => 'red',
            'evaluation_approved'  => 'green',
            'evaluation_revoked'   => 'red',
            default                => 'gray',
        };
    }
}