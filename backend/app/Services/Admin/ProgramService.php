<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Contracts\Storage\FileStorageInterface;
use App\Exceptions\ProgramHasActiveSubmissionsException;
use App\Jobs\BulkRejectionVerificationJob;
use App\Jobs\CalculateSmartScoreJob;
use App\Jobs\IndexProgramJob;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ProgramService
{
    public function __construct(
        private readonly ProgramRepositoryInterface $programRepository,
        private readonly FileStorageInterface $storage
    ) {}

    public function getList(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->programRepository->getAll($filters);
    }

    public function createProgram(array $data, ?UploadedFile $banner = null): AssistanceProgram
    {
        // Cegah duplikasi nama (user klik 2x saat jaringan lambat)
        if (AssistanceProgram::where('name', $data['name'])->exists()) {
            throw new \RuntimeException("Program dengan nama '{$data['name']}' sudah ada.");
        }

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;

        while (AssistanceProgram::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . bin2hex(random_bytes(3));
        }

        $data['slug'] = $slug;
        $data['status'] = $data['status'] ?? 'draft';

        if ($banner) {
            $upload = $this->storage->upload($banner, 'programs');
            $data['banner_url'] = $upload['url'];
            $data['banner_public_id'] = $upload['public_id'];
        }

        if (!empty($data['criteria']['files'])) {
            $aiConfig = [];
            foreach ($data['criteria']['files'] as $file) {
                $aiConfig[$file['key']] = [
                    'ocr'       => $file['ocr'] ?? false,
                    'nlp_match' => $file['nlp_match'] ?? false,
                ];
            }
            $data['ai_config'] = $aiConfig;
        }

        $program = $this->programRepository->create($data);

        $this->clearActiveProgramsCache();

        dispatch(new IndexProgramJob($program->id, 'index'))
            ->afterCommit();

        return $program;
    }

    public function updateProgram(string $id, array $data, ?UploadedFile $banner = null): AssistanceProgram
    {
        $program = $this->programRepository->findById($id);

        if (!$program) {
            throw new ModelNotFoundException("Program dengan ID {$id} tidak ditemukan.");
        }

        if (!empty($data['name'])) {
            if ($this->programRepository->existsByNameExcludingId($data['name'], $id)) {
                throw new \RuntimeException("Nama program '{$data['name']}' sudah digunakan.");
            }

            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug;

            while (AssistanceProgram::withTrashed()->where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $baseSlug . '-' . bin2hex(random_bytes(3));
            }

            $data['slug'] = $slug;
        }

        if ($banner) {
            if ($program->banner_public_id) {
                $this->storage->delete($program->banner_public_id);
            }
            $upload = $this->storage->upload($banner, 'programs');
            $data['banner_url'] = $upload['url'];
            $data['banner_public_id'] = $upload['public_id'];
        }

        if (!empty($data['criteria']['files'])) {
            $aiConfig = [];
            foreach ($data['criteria']['files'] as $file) {
                $aiConfig[$file['key']] = [
                    'ocr'       => $file['ocr'] ?? false,
                    'nlp_match' => $file['nlp_match'] ?? false,
                ];
            }
            $data['ai_config'] = $aiConfig;
        }

        $oldAiConfig = $program->ai_config;
        $newAiConfig = $data['ai_config'] ?? null;
        $shouldReAnalyze = $newAiConfig !== null && $newAiConfig !== $oldAiConfig;

        $oldCriteria = $program->criteria['inputs'] ?? [];
        $newCriteria = $data['criteria']['inputs'] ?? [];

        $this->programRepository->update($program, $data);
        $program = $this->programRepository->findById($id);

        if ($shouldReAnalyze) {
            $submissions = $program->submissions()
                ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
                ->get();

            foreach ($submissions as $submission) {
                foreach ($submission->evidences as $evidence) {
                    \App\Jobs\AnalyzeEvidenceJob::dispatch($evidence->id, [])
                        ->afterCommit();
                }
            }
        }

        if ($this->isCriteriaChanged($oldCriteria, $newCriteria)) {
            $submissions = $program->submissions()
                ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
                ->get();

            foreach ($submissions as $submission) {
                CalculateSmartScoreJob::dispatch($submission->id)
                    ->afterCommit();
            }
        }

        $this->clearActiveProgramsCache();

        dispatch(new IndexProgramJob($program->id, 'update'))
            ->afterCommit();

        return $program;
    }

    public function deleteProgram(string $id): void
    {
        $program = $this->programRepository->findById($id);

        if (!$program) {
            throw new ModelNotFoundException("Program dengan ID {$id} tidak ditemukan.");
        }

        $activeSubmissions = $program->submissions()
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->count();

        if ($activeSubmissions > 0) {
            throw new ProgramHasActiveSubmissionsException($id, $activeSubmissions);
        }

        if ($program->banner_public_id) {
            $this->storage->delete($program->banner_public_id);
        }

        $this->programRepository->delete($program);

        $this->clearActiveProgramsCache();

        dispatch(new IndexProgramJob($id, 'delete'))
            ->afterCommit();
    }

    public function uploadBanner(string $id, UploadedFile $file): AssistanceProgram
    {
        $program = $this->programRepository->findById($id);

        if (!$program) {
            throw new ModelNotFoundException("Program dengan ID {$id} tidak ditemukan.");
        }

        if ($program->banner_public_id) {
            $this->storage->delete($program->banner_public_id);
        }

        $upload = $this->storage->upload($file, 'programs');

        $this->programRepository->update($program, [
            'banner_url' => $upload['url'],
            'banner_public_id' => $upload['public_id'],
        ]);

        $program = $this->programRepository->findById($id);

        dispatch(new IndexProgramJob($program->id, 'update'))
            ->afterCommit();

        return $program;
    }

    public function closeProgram(string $id): AssistanceProgram
    {
        $program = $this->programRepository->findById($id);

        if (!$program) {
            throw new ModelNotFoundException("Program dengan ID {$id} tidak ditemukan.");
        }

        if ($program->status !== 'active') {
            throw new \RuntimeException('Hanya program aktif yang dapat ditutup.');
        }

        $this->programRepository->update($program, ['status' => 'closed']);
        $program = $this->programRepository->findById($id);

        $this->clearActiveProgramsCache();

        dispatch(new IndexProgramJob($program->id, 'update'))
            ->afterCommit();

        return $program;
    }

    public function reopenProgram(string $id): AssistanceProgram
    {
        $program = $this->programRepository->findById($id);

        if (!$program) {
            throw new ModelNotFoundException("Program dengan ID {$id} tidak ditemukan.");
        }

        if ($program->status !== 'closed') {
            throw new \RuntimeException('Hanya program tertutup yang dapat dibuka kembali.');
        }

        $this->programRepository->update($program, ['status' => 'active']);
        $program = $this->programRepository->findById($id);

        $this->clearActiveProgramsCache();

        dispatch(new IndexProgramJob($program->id, 'update'))
            ->afterCommit();

        return $program;
    }

    public function autoToggleStatuses(): array
    {
        $now = now();
        $logs = [];

        // 1. Aktifkan program draft
        AssistanceProgram::where('status', 'draft')
            ->where('start_date', '<=', $now)
            ->chunkById(100, function ($programs) use (&$logs) {
                foreach ($programs as $program) {
                    $program->update(['status' => 'active']);
                    $logs[] = "Program '{$program->name}' diaktifkan otomatis.";

                    dispatch(new IndexProgramJob($program->id, 'update'))
                        ->afterCommit();
                }
            });

        // 2. Tutup program expired
        AssistanceProgram::where('status', 'active')
            ->where('end_date', '<', $now)
            ->chunkById(100, function ($programs) use (&$logs) {
                foreach ($programs as $program) {
                    $program->update(['status' => 'closed']);
                    $logs[] = "Program '{$program->name}' ditutup otomatis (melewati end_date).";

                    dispatch(new IndexProgramJob($program->id, 'update'))
                        ->afterCommit();
                }
            });

        // 3. Tutup program kuota penuh (validated + completed >= quota)
        AssistanceProgram::where('status', 'active')
            ->whereNotNull('quota_total')
            ->chunkById(100, function ($programs) use (&$logs) {
                foreach ($programs as $program) {
                    $count = AssistanceSubmission::where('program_id', $program->id)
                        ->excludeEvaluation()
                        ->whereIn('status', ['validated', 'completed'])
                        ->count();

                    if ($count >= $program->quota_total) {
                        $program->update(['status' => 'closed']);
                        $logs[] = "Program '{$program->name}' ditutup otomatis (kuota penuh: {$count}/{$program->quota_total}).";

                        dispatch(new IndexProgramJob($program->id, 'update'))
                            ->afterCommit();
                    }
                }
            });

        // 4. Auto-reject pending untuk program closed yang completed >= quota
        AssistanceProgram::where('status', 'closed')
            ->whereNotNull('quota_total')
            ->chunkById(100, function ($programs) use (&$logs) {
                foreach ($programs as $program) {
                    $completedCount = AssistanceSubmission::where('program_id', $program->id)
                        ->excludeEvaluation()
                        ->where('status', 'completed')
                        ->count();

                    if ($completedCount >= $program->quota_total) {
                        // Ambil ID submission pending yang akan ditolak
                        $pendingIds = AssistanceSubmission::where('program_id', $program->id)
                            ->excludeEvaluation()
                            ->where('status', 'pending')
                            ->pluck('id')
                            ->toArray();

                        if (empty($pendingIds)) {
                            continue;
                        }

                        // Update massal: pending → rejected
                        $rejectedCount = AssistanceSubmission::whereIn('id', $pendingIds)
                            ->update([
                                'status'              => 'rejected',
                                'last_submission_date' => now(),
                            ]);

                        // Dispatch Job untuk catatan verifikasi (async)
                        BulkRejectionVerificationJob::dispatch(
                            $pendingIds,
                            "Kuota program '{$program->name}' sudah tersalurkan penuh ({$completedCount}/{$program->quota_total}). Pengajuan ditolak otomatis oleh sistem."
                        )->afterCommit();

                        $logs[] = "Program '{$program->name}': {$rejectedCount} pengajuan pending ditolak otomatis (kuota tersalurkan penuh: {$completedCount}/{$program->quota_total}).";
                    }
                }
            });

        if (!empty($logs)) {
            $this->clearActiveProgramsCache();
        }

        return $logs;
    }

    public function duplicateProgram(string $id): AssistanceProgram
    {
        $original = $this->programRepository->findById($id);

        if (!$original) {
            throw new ModelNotFoundException("Program dengan ID {$id} tidak ditemukan.");
        }

        $baseName = $original->name;
        $newName = $baseName;
        $counter = 2;

        while (AssistanceProgram::where('name', $newName)->exists()) {
            $newName = $baseName . ' ' . $counter;
            $counter++;
        }

        $newProgram = $this->programRepository->create([
            'name'            => $newName,
            'slug'            => Str::slug($newName . '-' . now()->timestamp),
            'description'     => $original->description,
            'criteria'        => $original->criteria,
            'ai_config'       => $original->ai_config,
            'start_date'      => null,
            'end_date'        => null,
            'quota_total'     => null,
            'benefit_amount'  => $original->benefit_amount,
            'status'          => 'draft',
        ]);

        dispatch(new IndexProgramJob($newProgram->id, 'index'))
            ->afterCommit();

        return $newProgram;
    }

    public function notifyQuotaThreshold(): array
    {
        $logs = [];

        AssistanceProgram::where('status', AssistanceProgram::STATUS_ACTIVE)
            ->whereNotNull('quota_total')
            ->chunkById(100, function ($programs) use (&$logs) {
                foreach ($programs as $program) {
                    $count = AssistanceSubmission::where('program_id', $program->id)
                        ->excludeEvaluation()
                        ->whereIn('status', ['validated', 'completed'])
                        ->count();

                    if ($program->quota_total == 0) continue;

                    $percentage = round(($count / $program->quota_total) * 100, 1);

                    if ($percentage >= 80) {
                        $cacheKey = "quota_warning_{$program->id}_" . floor($percentage / 10) * 10;

                        if (!Cache::has($cacheKey)) {
                            Log::warning("SABANA: Kuota program '{$program->name}' sudah {$percentage}% ({$count}/{$program->quota_total}).");

                            Cache::put($cacheKey, true, now()->addHours(24));

                            $logs[] = "Peringatan kuota {$percentage}% untuk '{$program->name}' dicatat.";
                        }
                    }
                }
            });

        return $logs;
    }

    public function getActivePrograms(): Collection
    {
        $activeIds = Cache::tags(['programs'])->remember(
            'active_program_ids',
            now()->addMinutes(30),
            fn(): array => AssistanceProgram::active()
                ->where('start_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
                })
                ->pluck('id')
                ->toArray()
        );

        return AssistanceProgram::whereIn('id', $activeIds)->get();
    }

    public function getActiveProgramsWithSubmissionStatus(?string $citizenId): Collection
    {
        $programs = $this->getActivePrograms();

        if ($citizenId) {
            $submittedIds = AssistanceSubmission::where('citizen_id', $citizenId)
                ->whereIn('program_id', $programs->pluck('id'))
                ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
                ->pluck('program_id')
                ->unique()
                ->toArray();

            $programs->each(function ($program) use ($submittedIds) {
                $program->has_submitted = in_array($program->id, $submittedIds, true);
            });
        }

        return $programs;
    }

    private function clearActiveProgramsCache(): void
    {
        Cache::tags(['programs'])->flush();
        Cache::tags(['spk'])->flush();
        Cache::forget('citizen_active_programs');
    }

    private function isCriteriaChanged(array $oldInputs, array $newInputs): bool
    {
        if (count($oldInputs) !== count($newInputs)) {
            return true;
        }

        $oldIndexed = [];
        foreach ($oldInputs as $input) {
            $oldIndexed[$input['key']] = $input;
        }

        foreach ($newInputs as $newInput) {
            $key = $newInput['key'] ?? '';
            $old = $oldIndexed[$key] ?? null;

            if (!$old) {
                return true;
            }

            if (($newInput['sifat'] ?? 'benefit') !== ($old['sifat'] ?? 'benefit')) {
                return true;
            }

            if (($newInput['type'] ?? 'number') !== ($old['type'] ?? 'number')) {
                return true;
            }

            if (($newInput['weight'] ?? 0) !== ($old['weight'] ?? 0)) {
                return true;
            }

            if (($newInput['ideal_value'] ?? 0) !== ($old['ideal_value'] ?? 0)) {
                return true;
            }
        }

        return false;
    }
}