<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Models\Disbursement;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class DashboardRepository implements DashboardRepositoryInterface
{
    // ===== REGENCY =====

    public function getRegencyStats(string $regencyId): array
    {
        $totalPenerima = Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->where('s.regency_id', $regencyId)
            ->distinct('s.citizen_id')
            ->count('s.citizen_id');

        $totalDana = Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->where('s.regency_id', $regencyId)
            ->sum('disbursements.amount');

        $programAktif = AssistanceProgram::query()
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        $antreanVerifikasi = AssistanceSubmission::query()
            ->where('regency_id', $regencyId)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();

        return [
            'totalPenerima'       => $totalPenerima,
            'totalDanaTersalurkan' => (float) $totalDana,
            'programAktif'         => $programAktif,
            'antreanVerifikasi'    => $antreanVerifikasi,
        ];
    }

    public function getDistrictDistribution(string $regencyId, array $filters): array
    {
        return Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->join('districts as dis', 's.district_id', '=', 'dis.id')
            ->select('dis.name', DB::raw('COUNT(DISTINCT s.citizen_id) as count'), DB::raw('SUM(disbursements.amount) as amount'))
            ->where('s.regency_id', $regencyId)
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursements.disbursed_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursements.disbursed_at', '<=', $filters['tgl_akhir']))
            ->groupBy('dis.id', 'dis.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    public function getMonthlyTrend(string $regencyId, array $filters): array
    {
        return Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->select(
                DB::raw("TO_CHAR(disbursements.disbursed_at, 'Mon YYYY') as month"),
                DB::raw('SUM(disbursements.amount) as amount')
            )
            ->where('s.regency_id', $regencyId)
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursements.disbursed_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursements.disbursed_at', '<=', $filters['tgl_akhir']))
            ->groupBy(DB::raw("TO_CHAR(disbursements.disbursed_at, 'Mon YYYY')"))
            ->orderBy(DB::raw("MIN(disbursements.disbursed_at)"))
            ->get()
            ->toArray();
    }

    public function getVerificationStatus(string $regencyId): array
    {
        $statuses = AssistanceSubmission::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('regency_id', $regencyId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $colors = [
            'pending'            => '#fef3c7',
            'validated'          => '#dcfce7',
            'rejected'           => '#fee2e2',
            'completed'          => '#bbf7d0',
            'needs_revision'     => '#dbeafe',
            'evaluation_pending' => '#e0e7ff',
            'revoked'            => '#fecaca',
        ];

        $labels = [
            'pending'            => 'Pending',
            'validated'          => 'Disetujui',
            'rejected'           => 'Ditolak',
            'completed'          => 'Selesai',
            'needs_revision'     => 'Revisi',
            'evaluation_pending' => 'Evaluasi',
            'revoked'            => 'Dicabut',
        ];

        return $statuses->map(fn($s) => [
            'label' => $labels[$s->status] ?? $s->status,
            'count' => (int) $s->count,
            'color' => $colors[$s->status] ?? '#e2e8f0',
        ])->toArray();
    }

    public function getTopVillages(string $regencyId, array $filters): array
    {
        return Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->join('villages as v', 's.village_id', '=', 'v.id')
            ->select('v.name', DB::raw('COUNT(DISTINCT s.citizen_id) as count'))
            ->where('s.regency_id', $regencyId)
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursements.disbursed_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursements.disbursed_at', '<=', $filters['tgl_akhir']))
            ->groupBy('v.id', 'v.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    // ===== DISTRICT =====

    public function getDistrictStats(string $districtId): array
    {
        $totalPenerima = Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->where('s.district_id', $districtId)
            ->distinct('s.citizen_id')
            ->count('s.citizen_id');

        $totalDana = Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->where('s.district_id', $districtId)
            ->sum('disbursements.amount');

        $antrean = AssistanceSubmission::query()
            ->where('district_id', $districtId)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();

        $pengajuanBulanIni = AssistanceSubmission::query()
            ->where('district_id', $districtId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNull('deleted_at')
            ->count();

        return [
            'totalPenerima'        => $totalPenerima,
            'totalDanaTersalurkan' => (float) $totalDana,
            'antreanVerifikasi'    => $antrean,
            'pengajuanBulanIni'    => $pengajuanBulanIni,
        ];
    }

    public function getVillageDistribution(string $districtId, array $filters): array
    {
        return Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->join('villages as v', 's.village_id', '=', 'v.id')
            ->select('v.name', DB::raw('COUNT(DISTINCT s.citizen_id) as count'), DB::raw('SUM(disbursements.amount) as amount'))
            ->where('s.district_id', $districtId)
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursements.disbursed_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursements.disbursed_at', '<=', $filters['tgl_akhir']))
            ->groupBy('v.id', 'v.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    public function getDistrictMonthlyTrend(string $districtId, array $filters): array
    {
        return Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->select(
                DB::raw("TO_CHAR(disbursements.disbursed_at, 'Mon YYYY') as month"),
                DB::raw('SUM(disbursements.amount) as amount')
            )
            ->where('s.district_id', $districtId)
            ->when(isset($filters['tgl_mulai']), fn($q) => $q->whereDate('disbursements.disbursed_at', '>=', $filters['tgl_mulai']))
            ->when(isset($filters['tgl_akhir']), fn($q) => $q->whereDate('disbursements.disbursed_at', '<=', $filters['tgl_akhir']))
            ->groupBy(DB::raw("TO_CHAR(disbursements.disbursed_at, 'Mon YYYY')"))
            ->orderBy(DB::raw("MIN(disbursements.disbursed_at)"))
            ->get()
            ->toArray();
    }

    public function getDistrictVerificationStatus(string $districtId): array
    {
        $statuses = AssistanceSubmission::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('district_id', $districtId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $colors = [
            'pending'          => '#fef3c7',
            'validated'        => '#dcfce7',
            'rejected'         => '#fee2e2',
            'completed'        => '#bbf7d0',
            'needs_revision'   => '#dbeafe',
        ];

        $labels = [
            'pending'        => 'Pending',
            'validated'      => 'Disetujui',
            'rejected'       => 'Ditolak',
            'completed'      => 'Selesai',
            'needs_revision' => 'Revisi',
        ];

        return $statuses->map(fn($s) => [
            'label' => $labels[$s->status] ?? $s->status,
            'count' => (int) $s->count,
            'color' => $colors[$s->status] ?? '#e2e8f0',
        ])->toArray();
    }

    // ===== VILLAGE =====

    public function getVillageStats(string $villageId): array
    {
        $totalWarga = Citizen::query()
            ->whereExists(function ($query) use ($villageId) {
                $query->select(DB::raw(1))
                    ->from('assistance_submissions')
                    ->whereColumn('citizens.id', 'assistance_submissions.citizen_id')
                    ->where('assistance_submissions.village_id', $villageId);
            })
            ->count();

        $pengajuanBulanIni = AssistanceSubmission::query()
            ->where('village_id', $villageId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNull('deleted_at')
            ->count();

        $disetujui = AssistanceSubmission::query()
            ->where('village_id', $villageId)
            ->whereIn('status', ['validated', 'completed'])
            ->whereNull('deleted_at')
            ->count();

        $disalurkan = Disbursement::query()
            ->join('assistance_submissions as s', 'disbursements.submission_id', '=', 's.id')
            ->where('s.village_id', $villageId)
            ->count();

        return [
            'totalWarga'        => $totalWarga,
            'pengajuanBulanIni' => $pengajuanBulanIni,
            'disetujui'         => $disetujui,
            'disalurkan'        => $disalurkan,
        ];
    }

    public function getRecentCitizens(string $villageId): array
    {
        return Citizen::query()
            ->join('assistance_submissions as s', 'citizens.id', '=', 's.citizen_id')
            ->select('citizens.nik', 'citizens.full_name', 's.created_at')
            ->where('s.village_id', $villageId)
            ->whereNull('s.deleted_at')
            ->orderByDesc('s.created_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'nik'        => $c->nik,
                'full_name'  => $c->full_name,
                'created_at' => $c->created_at->format('d-m-Y H:i'),
            ])
            ->toArray();
    }
}