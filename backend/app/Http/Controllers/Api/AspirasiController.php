<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AspirasiRequest;
use App\Jobs\SendEmailJob;
use Illuminate\Http\JsonResponse;

class AspirasiController extends Controller
{
    /**
     * Store a new aspiration from landing page (public).
     */
    public function store(AspirasiRequest $request): JsonResponse
    {
        SendEmailJob::dispatch($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Aspirasi berhasil terkirim.',
        ], 200);
    }
}