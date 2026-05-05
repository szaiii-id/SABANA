<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    public function regencies(): JsonResponse 
    {
        $data = Cache::remember('regencies_63', 86400, function () {
            return Regency::where('province_id', '63')->get(['id', 'name'])->toArray();
        });
        
        return response()->json($data);
    }

    public function districts(Request $request): JsonResponse
    {
        $request->validate(['regency_id' => 'required|exists:regencies,id']);
        
        $regencyId = $request->regency_id;
        
        $data = Cache::remember('districts_' . $regencyId, 86400, function () use ($regencyId) {
            return District::where('regency_id', $regencyId)->get(['id', 'name'])->toArray();
        });

        return response()->json($data);
    }

    public function villages(Request $request): JsonResponse
    {
        $request->validate(['district_id' => 'required|exists:districts,id']);
        
        $districtId = $request->district_id;
        
        $data = Cache::remember('villages_' . $districtId, 86400, function () use ($districtId) {
            return Village::where('district_id', $districtId)->get(['id', 'name'])->toArray();
        });

        return response()->json($data);
    }
}