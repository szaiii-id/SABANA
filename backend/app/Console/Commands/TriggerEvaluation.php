<?php

namespace App\Console\Commands;

use App\Models\AssistanceSubmission;
use App\Models\EvaluationLog;
use App\Models\SubmissionVerification;
use App\Jobs\SendWhatsAppJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TriggerEvaluation extends Command
{
    protected $signature = 'evaluation:trigger';
    protected $description = 'Auto-trigger evaluasi 6 bulan untuk submission completed';

    public function handle(): int
    {
        $this->info('Memulai trigger evaluasi...');
        $cutoffDate = now()->subMonths(6);
        $this->info("Batas tanggal: {$cutoffDate->toDateTimeString()}");

        $submissions = AssistanceSubmission::where('status', 'completed')
            ->where('last_submission_date', '<=', $cutoffDate)
            ->whereDoesntHave('evaluationLog', function ($q) {
                $q->whereIn('status', ['triggered', 'updated']);
            })
            ->with(['citizen', 'program', 'village', 'district', 'regency'])
            ->get();

        // ✅ Debug info
        $totalCompleted = AssistanceSubmission::where('status', 'completed')->count();
        $this->info("Total completed: {$totalCompleted}");
        $this->info("Yang memenuhi kriteria: {$submissions->count()}");

        if ($submissions->isEmpty()) {
            $this->info('Tidak ada submission yang perlu dievaluasi.');
            $this->warn('Pastikan: status = completed DAN last_submission_date <= 6 bulan lalu');
            Log::info('EVALUATION: Tidak ada yang perlu dievaluasi.');
            return self::SUCCESS;
        }

        $count = 0;

        foreach ($submissions as $submission) {
            try {
                $this->info("Processing: {$submission->citizen->full_name} ({$submission->registration_number})");
                $this->info("  last_submission_date: {$submission->last_submission_date}");
                $this->info("  WA: " . ($submission->citizen->whatsapp_number ?: 'TIDAK ADA'));

                $submission->update(['status' => 'evaluation_pending']);

                EvaluationLog::create([
                    'submission_id' => $submission->id,
                    'program_id'    => $submission->program_id,
                    'citizen_id'    => $submission->citizen_id,
                    'village_id'    => $submission->village_id,
                    'district_id'   => $submission->district_id,
                    'regency_id'    => $submission->regency_id,
                    'status'        => 'triggered',
                    'old_data'      => $this->snapshotData($submission),
                    'triggered_by'  => 'system',
                    'triggered_at'  => now(),
                ]);

                SubmissionVerification::create([
                    'submission_id' => $submission->id,
                    'admin_id'      => null,
                    'admin_name'    => 'Sistem',
                    'action_type'   => 'evaluation_triggered',
                    'notes'         => 'Evaluasi 6 bulan otomatis',
                ]);

                if ($submission->citizen->whatsapp_number) {
                    $this->info("  → Mengirim WA ke {$submission->citizen->whatsapp_number}");
                    $message = "*[SABANA KALSEL - EVALUASI]*\n\n"
                        . "Halo {$submission->citizen->full_name},\n\n"
                        . "Program bantuan *{$submission->program->name}* Anda telah memasuki masa evaluasi 6 bulan.\n\n"
                        . "Silakan login ke aplikasi SABANA untuk memperbarui data Anda, "
                        . "atau hubungi Petugas Desa untuk bantuan.\n\n"
                        . "Terima kasih.";

                    SendWhatsAppJob::dispatch($submission->citizen->whatsapp_number, $message);
                } else {
                    $this->warn("  → TIDAK ADA nomor WA, skip notifikasi");
                }

                $count++;

            } catch (\Exception $e) {
                $this->error("Gagal: {$e->getMessage()}");
                Log::error('EVALUATION: Gagal trigger', [
                    'submission_id' => $submission->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $this->info("Selesai. {$count} evaluasi ditrigger.");
        Log::info("EVALUATION: {$count} evaluasi ditrigger.");

        return self::SUCCESS;
    }

    private function snapshotData(AssistanceSubmission $submission): array
    {
        return [
            'submission_id'          => $submission->id,
            'registration_number'    => $submission->registration_number,
            'program_name'           => $submission->program->name ?? null,
            'citizen_name'           => $submission->citizen->full_name ?? null,
            'citizen_nik'            => $submission->citizen->nik ?? null,
            'family_card_number'     => $submission->submission_data['family_card_number'] ?? null,
            'submission_data'        => $submission->submission_data ?? [],
            'smart_score'              => $submission->smart_score,
            'disbursement_method'    => $submission->disbursement_method,
            'bank_account_number'    => $submission->bank_account_number,
            'last_submission_date'   => $submission->last_submission_date?->toDateTimeString(),
            'snapshot_at'            => now()->toDateTimeString(),
        ];
    }
}