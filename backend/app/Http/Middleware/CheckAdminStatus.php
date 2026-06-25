<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user saat ini adalah Admin dan sedang login
        if ($request->user() && $request->user()->currentAccessToken()) {
            
            // Cek apakah status is_active nya telah dicabut
            if (!$request->user()->is_active) {
                
                // 1. Hancurkan tiket (Logout Paksa)
                $request->user()->currentAccessToken()->delete();

                // 2. Beri tahu Frontend untuk melempar pegawai keluar
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesi dihentikan. Akun Anda telah dinonaktifkan oleh Administrator.'
                ], 403); // 403 Forbidden adalah status code yang tepat untuk ini
            }
        }

        // Lanjutkan perjalanan request jika aman
        return $next($request);
    }
}