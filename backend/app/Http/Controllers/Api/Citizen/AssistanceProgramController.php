<?php

namespace App\Http\Controllers\Api\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssistanceProgramResource;
use App\Models\AssistanceProgram;
use Illuminate\Http\JsonResponse;

class AssistanceProgramController extends Controller
{
    public function index(): JsonResponse 
    {
        $programs = AssistanceProgram::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => AssistanceProgramResource::collection($programs)->resolve()
        ]);
    }
}