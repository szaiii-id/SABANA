<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssistanceProgramResource;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class AssistanceProgramController extends Controller
{
    public function index(Request $request): JsonResponse 
    {
        $citizenId = $request->user()->id;

        // Cache hanya daftar ID program aktif (ringan, tidak error unserialize)
        $activeProgramIds = Cache::tags(['programs', 'citizen'])->remember(
            'citizen_active_program_ids',
            now()->addMinutes(30),
            function () {
                return AssistanceProgram::active()
                    ->where('start_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    })
                    ->pluck('id')
                    ->toArray();
            }
        );

        // Query ulang dari ID (fresh dari DB, tidak kena serialize issue)
        $programs = AssistanceProgram::whereIn('id', $activeProgramIds)
            ->get();

        // Cek has_submitted
        $submittedProgramIds = AssistanceSubmission::where('citizen_id', $citizenId)
            ->whereIn('program_id', $activeProgramIds)
            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
            ->pluck('program_id')
            ->unique()
            ->toArray();

        $programs->each(function ($program) use ($submittedProgramIds) {
            $program->has_submitted = in_array($program->id, $submittedProgramIds, true);
        });

        return response()->json([
            'success' => true,
            'data'    => AssistanceProgramResource::collection($programs),
        ]);
    }
}