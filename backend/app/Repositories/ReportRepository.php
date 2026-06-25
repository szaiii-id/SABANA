<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\CitizenRegistrationLog;
use App\Models\Disbursement;
use App\Models\EvaluationLog;
use App\Repositories\Contracts\ReportRepositoryInterface;

final class ReportRepository implements ReportRepositoryInterface
{
    // ===== LAPORAN 1: RINGKASAN ANGGARAN PROGRAM =====

    public function budgetSummary(array $filters): array
    {
        return AssistanceProgram::query()
            ->when(isset($filters['program_id']), fn($q) => $q->where('id', $filters['program_id']))
            ->get()
            ->map(function (AssistanceProgram $program) use ($filters): array {
                $tersalurkan = Disbursement::query()
                    ->where('program_id', $program->id)
                    ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursed_at', '>=', $filters['tgl_mulai']))
                    ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursed_at', '<=', $filters['tgl_akhir']))
                    ->sum('amount');

                return [
                    'program'         => $program->name,
                    'benefit_amount'  => (float) $program->benefit_amount,
                    'quota_total'     => (int) $program->quota_total,
                    'total_anggaran'  => (float) $program->total_anggaran,
                    'tersalurkan'     => (float) $tersalurkan,
                    'sisa'            => (float) ($program->total_anggaran - $tersalurkan),
                ];
            })
            ->toArray();
    }

    // ===== LAPORAN 2: DAFTAR PENERIMA PER PROGRAM =====

    public function programRecipients(array $filters): array
    {
        return Disbursement::query()
            ->with(['citizen', 'program', 'submission.village', 'submission.district'])
            ->when(isset($filters['program_id']), fn($q) => $q->where('program_id', $filters['program_id']))
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursed_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursed_at', '<=', $filters['tgl_akhir']))
            ->orderBy('disbursed_at', 'desc')
            ->get()
            ->map(fn(Disbursement $d): array => [
                'nik'              => $d->citizen?->nik,
                'full_name'        => $d->citizen?->full_name,
                'desa'             => $d->submission?->village?->name,
                'kecamatan'        => $d->submission?->district?->name,
                'program'          => $d->program?->name,
                'amount'           => (float) $d->amount,
                'disbursed_at'     => $d->disbursed_at?->toDateString(),
                'reference_number' => $d->reference_number,
                'method'           => $d->method,
            ])
            ->toArray();
    }

    // ===== LAPORAN 3: PROGRAM PALING BANYAK DIMINATI =====

    public function mostAppliedPrograms(array $filters): array
    {
        return AssistanceSubmission::query()
            ->join('assistance_programs as p', 'assistance_submissions.program_id', '=', 'p.id')
            ->selectRaw('p.name as program, p.quota_total, COUNT(DISTINCT assistance_submissions.citizen_id) as total_pendaftar_unik')
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('assistance_submissions.created_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('assistance_submissions.created_at', '<=', $filters['tgl_akhir']))
            ->whereNull('assistance_submissions.deleted_at')
            ->whereNull('p.deleted_at')
            ->groupBy('assistance_submissions.program_id', 'p.name', 'p.quota_total')
            ->orderByDesc('total_pendaftar_unik')
            ->get()
            ->toArray();
    }

    // ===== LAPORAN 4: WARGA DIDAFTARKAN OLEH ADMIN =====

    public function citizenRegisteredByAdmin(array $filters): array
    {
        return CitizenRegistrationLog::query()
            ->with(['admin', 'citizen'])
            ->whereIn('action', ['register_with_pin', 'register_without_pin'])
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('created_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('created_at', '<=', $filters['tgl_akhir']))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(CitizenRegistrationLog $log): array => [
                'admin_name' => $log->admin?->name,
                'admin_role' => $log->admin?->role,
                'nik'        => $log->citizen?->nik,
                'full_name'  => $log->citizen?->full_name,
                'action'     => $log->action,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])
            ->toArray();
    }

    // ===== LAPORAN 5: DATA WARGA SIAP DISALURKAN =====

    public function readyForDisbursement(array $filters): array
    {
        return AssistanceSubmission::query()
            ->with(['citizen', 'program', 'village', 'verifications' => fn($q) => $q->where('action_type', 'approved')->latest()])
            ->where('status', 'validated')
            ->when(isset($filters['program_id']), fn($q) => $q->where('program_id', $filters['program_id']))
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereHas('verifications', fn($v) => $v->whereDate('created_at', '>=', $filters['tgl_mulai'])->where('action_type', 'approved')))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereHas('verifications', fn($v) => $v->whereDate('created_at', '<=', $filters['tgl_akhir'])->where('action_type', 'approved')))
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (AssistanceSubmission $s): array {
                $approved = $s->verifications->first();
                return [
                    'nik'             => $s->citizen?->nik,
                    'full_name'       => $s->citizen?->full_name,
                    'desa'            => $s->village?->name,
                    'program'         => $s->program?->name,
                    'benefit_amount'  => (float) $s->program?->benefit_amount,
                    'tgl_divalidasi'  => $approved?->created_at?->toDateString(),
                    'validator'       => $approved?->admin_name,
                ];
            })
            ->toArray();
    }

    // ===== LAPORAN 6: DATA WARGA AKAN DIEVALUASI =====

public function pendingEvaluation(array $filters): array
{
    return EvaluationLog::query()
        ->with(['citizen', 'program', 'submission.disbursement', 'submission.village'])
        ->where('status', 'triggered')
        ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('triggered_at', '>=', $filters['tgl_mulai']))
        ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('triggered_at', '<=', $filters['tgl_akhir']))
        ->orderBy('triggered_at', 'asc')
        ->get()
        ->map(fn(EvaluationLog $e): array => [
            'nik'              => $e->citizen?->nik,
            'full_name'        => $e->citizen?->full_name,
            'desa'             => $e->submission?->village?->name,
            'program'          => $e->program?->name,
            'amount'           => (float) ($e->submission?->disbursement?->amount ?? 0),
            'tgl_penyaluran'   => $e->submission?->disbursement?->disbursed_at?->toDateString(),
            'triggered_at'     => $e->triggered_at?->toDateString(),
        ])
        ->toArray();
}

// ===== LAPORAN 7: DATA WARGA BANTUAN DIHENTIKAN =====

public function revokedRecipients(array $filters): array
{
    return EvaluationLog::query()
        ->with(['citizen', 'program', 'submission.village', 'decider', 'submission.disbursement'])
        ->where('status', 'revoked')
        ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('decided_at', '>=', $filters['tgl_mulai']))
        ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('decided_at', '<=', $filters['tgl_akhir']))
        ->orderBy('decided_at', 'desc')
        ->get()
        ->map(fn(EvaluationLog $e): array => [
            'nik'              => $e->citizen?->nik,
            'full_name'        => $e->citizen?->full_name,
            'desa'             => $e->submission?->village?->name,
            'program'          => $e->program?->name,
            'jumlah_sebelumnya' => (float) ($e->submission?->disbursement?->amount ?? 0),
            'alasan'           => $e->decision_notes,
            'tgl_keputusan'    => $e->decided_at?->toDateString(),
            'pemutus'          => $e->decider?->name,
        ])
        ->toArray();
}

// ===== LAPORAN 8: DATA WARGA TETAP MENERIMA BANTUAN =====

public function approvedRecipients(array $filters): array
{
    return EvaluationLog::query()
        ->with(['citizen', 'program', 'submission.village', 'decider', 'newSubmission.disbursement'])
        ->where('status', 'approved')
        ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('decided_at', '>=', $filters['tgl_mulai']))
        ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('decided_at', '<=', $filters['tgl_akhir']))
        ->orderBy('decided_at', 'desc')
        ->get()
        ->map(fn(EvaluationLog $e): array => [
            'nik'            => $e->citizen?->nik,
            'full_name'      => $e->citizen?->full_name,
            'desa'           => $e->submission?->village?->name,
            'program'        => $e->program?->name,
            'amount'         => (float) ($e->newSubmission?->disbursement?->amount ?? 0),
            'tgl_keputusan'  => $e->decided_at?->toDateString(),
            'pemutus'        => $e->decider?->name,
        ])
        ->toArray();
}

    // ===== LAPORAN 9: DATA WARGA SUDAH DISALURKAN =====

    public function disbursedRecipients(array $filters): array
    {
        return Disbursement::query()
            ->with(['citizen', 'program', 'submission.village', 'submission.district'])
            ->when(isset($filters['program_id']), fn($q) => $q->where('program_id', $filters['program_id']))
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursed_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursed_at', '<=', $filters['tgl_akhir']))
            ->orderBy('disbursed_at', 'desc')
            ->get()
            ->map(fn(Disbursement $d): array => [
                'nik'              => $d->citizen?->nik,
                'full_name'        => $d->citizen?->full_name,
                'desa'             => $d->submission?->village?->name,
                'kecamatan'        => $d->submission?->district?->name,
                'program'          => $d->program?->name,
                'amount'           => (float) $d->amount,
                'disbursed_at'     => $d->disbursed_at?->toDateString(),
                'reference_number' => $d->reference_number,
                'method'           => $d->method,
            ])
            ->toArray();
    }
}