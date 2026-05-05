<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\CitizenResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $results = $this->authService->login($request->validated());
        if (isset($results['citizen'])) {
            $results['citizen'] = new CitizenResource($results['citizen']);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => $results,
        ]);
    }
}
