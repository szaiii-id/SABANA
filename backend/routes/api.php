<?php

use App\Http\Controllers\Api\AspirasiController;
use App\Http\Controllers\Api\Auth\AccountVerificationController;
use App\Http\Controllers\Api\Auth\ForgotPinController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;

Route::prefix('v1')->as('api.v1.')->group(function () {

    Route::post('/kontak', [AspirasiController::class, 'store']);
    
    Route::prefix('auth')->as('auth.')->group(function () {
        Route::post('/register', RegisterController::class)->name('register');
        Route::post('/login', LoginController::class)->name('login');

        Route::post('/verify-registration', [AccountVerificationController::class, 'verify']);
        Route::post('/resend-registration-otp', [AccountVerificationController::class, 'resend']);
        
        Route::post('/forgot-pin', [ForgotPinController::class, 'sendOtp'])->name('forgot-pin');
        Route::post('/reset-pin', [ForgotPinController::class, 'resetPin'])->name('reset-pin');
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');

        Route::get('/user', function (Request $request) {
            return $request->user();
        })->name('user');
    });
});

// woi bisa kada