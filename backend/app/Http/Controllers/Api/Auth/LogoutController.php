<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function __invoke(Request $request)
    {
        $this->authService->logout($request->user());
        return response()->json(['status' => 'success', 'message' => 'Sesi telah berakhir karena tidak ada aktivitas.']);
    }
}