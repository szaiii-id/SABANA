<?php

use App\Http\Controllers\Api\Admin\Account\AdminManagementController;
use App\Http\Controllers\Api\Admin\Account\AdminProfileController;
use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Api\Admin\Auth\AdminLogoutController;
use App\Http\Controllers\Api\Admin\CitizenAssistanceController;
use App\Http\Controllers\Api\Admin\CitizenRegistrationController;
use App\Http\Controllers\Api\Admin\DisbursementController;
use App\Http\Controllers\Api\Admin\EvaluationController;
use App\Http\Controllers\Api\Admin\ProgramController;
use App\Http\Controllers\Api\Admin\VerificationController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\SpkController;
use App\Http\Controllers\Api\AspirasiController;
use App\Http\Controllers\Api\Auth\AccountVerificationController;
use App\Http\Controllers\Api\Auth\ForgotPinController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Citizen\AssistanceController;
use App\Http\Controllers\Api\Citizen\AssistanceProgramController;
use App\Http\Controllers\Api\Citizen\DisbursementReceiptController;
use App\Http\Controllers\Api\Citizen\ProfileController;
use App\Http\Controllers\Api\Citizen\ReportController;
use App\Http\Controllers\Api\Citizen\SecurityController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Middleware\CheckAdminStatus;

if (!defined('UUID_PATTERN')) {
    define('UUID_PATTERN', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
}

Route::prefix('v1')->as('api.v1.')->group(function () {

    Route::post('/kontak', [AspirasiController::class, 'store']);
    
    Route::prefix('auth')->as('auth.')->group(function () {
        Route::post('/register', RegisterController::class)->name('register');
        Route::post('/login', LoginController::class)->name('login');
        Route::get('/prefill-registration', [RegisterController::class, 'prefill'])->name('prefill.registration');

        Route::post('/verify-registration', [AccountVerificationController::class, 'verify']);
        Route::post('/resend-registration-otp', [AccountVerificationController::class, 'resend']);
        
        Route::post('/forgot-pin', [ForgotPinController::class, 'sendOtp'])->name('forgot-pin');
        Route::post('/reset-pin', [ForgotPinController::class, 'resetPin'])->name('reset-pin');
    });


    Route::middleware(['auth:api'])->prefix('citizen')->group(function () {
        
        Route::prefix('report')->group(function () {
            Route::post('/whatsapp', [ReportController::class, 'whatsapp']);
            Route::post('/email', [ReportController::class, 'email']);
        });
        Route::prefix('regions')->group(function () {
            Route::get('/regencies', [RegionController::class, 'regencies']);
            Route::get('/districts', [RegionController::class, 'districts']);
            Route::get('/villages', [RegionController::class, 'villages']);
        });
        Route::get('/assistance-categories', [AssistanceProgramController::class, 'index']);
        Route::post('/assistance/submit', [AssistanceController::class, 'store'])->middleware('throttle:submissions'); 
        Route::get('/assistance/submissions', [AssistanceController::class, 'history']);
        Route::get('/assistance/submissions/{id}/receipt', [DisbursementReceiptController::class, 'show']);
        Route::get('/assistance/submissions/{id}/receipt/pdf', [DisbursementReceiptController::class, 'pdf']);
        Route::get('/assistance/submissions/{id}/download', [AssistanceController::class, 'downloadReceipt']);
        Route::get('/assistance/submissions/{id}', [AssistanceController::class, 'showById']);
        Route::put('/assistance/submissions/{id}', [AssistanceController::class, 'update']);
        Route::get('/assistance/{registration_number}', [AssistanceController::class, 'show']);
        Route::delete('/assistance/{registration_number}', [AssistanceController::class, 'destroy']); 

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::put('/security/pin', [SecurityController::class, 'updatePin']);

    });

    Route::prefix(config('sabana.portal_prefix'))->as('internal.')->group(function () {
        
        Route::prefix('gate')->as('auth.')->group(function () {
            Route::post('/verify-nip', AdminLoginController::class)->name('login');
        });

        Route::middleware(['auth:admin-api', 'abilities:admin', CheckAdminStatus::class])->group(function () {
            Route::post('/auth/logout', AdminLogoutController::class)->name('auth.logout');

            Route::prefix('dashboard')->as('dashboard.')->group(function () {
                Route::get('/regency/{regencyId}/stats', [DashboardController::class, 'regencyStats'])->name('regency.stats');
                Route::get('/regency/{regencyId}/districts', [DashboardController::class, 'districtDistribution'])->name('regency.districts');
                Route::get('/regency/{regencyId}/trend', [DashboardController::class, 'monthlyTrend'])->name('regency.trend');
                Route::get('/regency/{regencyId}/verification-status', [DashboardController::class, 'verificationStatus'])->name('regency.verification');
                Route::get('/regency/{regencyId}/top-villages', [DashboardController::class, 'topVillages'])->name('regency.villages');

                Route::get('/district/{districtId}/stats', [DashboardController::class, 'districtStats'])->name('district.stats');
                Route::get('/district/{districtId}/villages', [DashboardController::class, 'villageDistribution'])->name('district.villages');
                Route::get('/district/{districtId}/trend', [DashboardController::class, 'districtMonthlyTrend'])->name('district.trend');
                Route::get('/district/{districtId}/verification-status', [DashboardController::class, 'districtVerificationStatus'])->name('district.verification');

                Route::get('/village/{villageId}/stats', [DashboardController::class, 'villageStats'])->name('village.stats');
                Route::get('/village/{villageId}/recent-citizens', [DashboardController::class, 'recentCitizens'])->name('village.citizens');
            });

            Route::prefix('regions')->group(function () {
                Route::get('/regencies', [RegionController::class, 'regencies']);
                Route::get('/districts', [RegionController::class, 'districts']);
                Route::get('/villages', [RegionController::class, 'villages']);
            });

            Route::prefix('accounts')->as('accounts.')->group(function () {
                Route::get('/', [AdminManagementController::class, 'index'])->name('index');
                Route::post('/', [AdminManagementController::class, 'store'])->name('store');
                Route::put('/{id}', [AdminManagementController::class, 'update'])
                    ->where('id', UUID_PATTERN)->name('update');
                Route::delete('/{id}', [AdminManagementController::class, 'destroy'])
                    ->where('id', UUID_PATTERN)->name('destroy');
                Route::patch('/{id}/reset-password', [AdminManagementController::class, 'resetPassword'])
                    ->where('id', UUID_PATTERN)->name('reset_password');
                Route::patch('/{id}/activate', [AdminManagementController::class, 'activate'])
                    ->where('id', UUID_PATTERN)->name('activate');
            });

            Route::prefix('profile')->as('profile.')->group(function () {
                Route::get('/', [AdminProfileController::class, 'show'])->name('show');
                Route::put('/', [AdminProfileController::class, 'update'])->name('update');
                Route::patch('/password', [AdminProfileController::class, 'updatePassword'])->name('update_password');
            });

            Route::prefix('programs')->as('programs.')->group(function () {
                Route::get('/', [ProgramController::class, 'index'])->name('index');
                Route::get('/active', [ProgramController::class, 'activePrograms'])->name('active');
                
                // Rate-limited: 10 request per menit
                Route::post('/', [ProgramController::class, 'store'])
                    ->middleware('throttle:10,1')
                    ->name('store');
                    
                Route::put('/{id}', [ProgramController::class, 'update'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:10,1')
                    ->name('update');
                    
                Route::delete('/{id}', [ProgramController::class, 'destroy'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:10,1')
                    ->name('destroy');
                    
                Route::post('/{id}/banner', [ProgramController::class, 'uploadBanner'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:5,1')
                    ->name('upload_banner');
                    
                Route::post('/{id}/close', [ProgramController::class, 'close'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:10,1')
                    ->name('close');
                    
                Route::post('/{id}/reopen', [ProgramController::class, 'reopen'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:10,1')
                    ->name('reopen');
                    
                Route::post('/{id}/duplicate', [ProgramController::class, 'duplicate'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:10,1')
                    ->name('duplicate');
            });

            Route::prefix('verifications')->as('verifications.')->group(function () {
                Route::get('/', [VerificationController::class, 'index'])->name('index');
                Route::get('/{id}', [VerificationController::class, 'show'])
                    ->where('id', UUID_PATTERN)->name('show');
                Route::post('/{id}/approve', [VerificationController::class, 'approve'])
                    ->where('id', UUID_PATTERN)->name('approve');
                Route::post('/{id}/reject', [VerificationController::class, 'reject'])
                    ->where('id', UUID_PATTERN)->name('reject');
                Route::post('/{id}/request-revision', [VerificationController::class, 'requestRevision'])
                    ->where('id', UUID_PATTERN)->name('request_revision');
                Route::post('/{id}/complete', [VerificationController::class, 'complete'])
                    ->where('id', UUID_PATTERN)->name('complete');
                Route::post('/{id}/unvalidate', [VerificationController::class, 'unvalidate'])
                    ->where('id', UUID_PATTERN)->name('unvalidate');
                Route::post('/bulk-complete', [VerificationController::class, 'bulkComplete'])->name('bulk_complete');
            });

            Route::prefix('citizen-registration')->as('citizen_registration.')->group(function () {
                Route::get('/citizens', [CitizenRegistrationController::class, 'index'])->name('citizens');
                Route::get('/citizens/search', [CitizenRegistrationController::class, 'search'])->name('citizens.search');
                Route::get('/citizens/search-paginated', [CitizenRegistrationController::class, 'searchPaginated'])->name('citizens.search_paginated');
                Route::get('/citizens/{id}', [CitizenRegistrationController::class, 'show'])
                    ->where('id', UUID_PATTERN)->name('citizens.show');
                Route::put('/citizens/{id}', [CitizenRegistrationController::class, 'update'])
                    ->where('id', UUID_PATTERN)->name('citizens.update');
                Route::get('/citizens/{id}/logs', [CitizenRegistrationController::class, 'logs'])
                    ->where('id', UUID_PATTERN)->name('citizens.logs');
                Route::get('/citizens/{id}/submissions', [CitizenRegistrationController::class, 'submissions'])
                    ->where('id', UUID_PATTERN)->name('citizens.submissions');
                Route::post('/create', [CitizenRegistrationController::class, 'store'])->name('store');
                Route::post('/create/resend-pin/{id}', [CitizenRegistrationController::class, 'resendPin'])
                    ->where('id', UUID_PATTERN)->middleware('throttle:3,1')->name('resend_pin');
                Route::post('/citizens/{id}/reset-pin-card', [CitizenRegistrationController::class, 'resetAndPreviewCard'])
                    ->where('id', UUID_PATTERN)->name('citizens.reset_pin_card');
            });
            
            Route::prefix('citizen-assistance')->as('citizen_assistance.')->group(function () {
                Route::post('/submit', [CitizenAssistanceController::class, 'store'])->name('store');
            });

            Route::prefix('evaluations')->as('evaluations.')->group(function () {
                Route::get('/', [EvaluationController::class, 'index'])->name('index');
                Route::post('/{id}/approve', [EvaluationController::class, 'approve'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:10,1')
                    ->name('approve');
                Route::post('/{id}/revoke', [EvaluationController::class, 'revoke'])
                    ->where('id', UUID_PATTERN)
                    ->middleware('throttle:10,1')
                    ->name('revoke');
            });

            Route::prefix('disbursements')->as('disbursements.')->group(function () {
                Route::get('/', [DisbursementController::class, 'index'])->name('index');
                Route::post('/', [DisbursementController::class, 'store'])->name('store');
                Route::post('/bulk', [DisbursementController::class, 'bulkStore'])->name('bulk_store');
            });

            Route::prefix('activity-logs')->as('activity_logs.')->group(function () {
                Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            });

            Route::prefix('reports')->as('reports.')->group(function () {
                Route::get('/budget-summary', [AdminReportController::class, 'budgetSummary'])->name('budget_summary');
                Route::get('/program-recipients', [AdminReportController::class, 'programRecipients'])->name('program_recipients');
                Route::get('/most-applied-programs', [AdminReportController::class, 'mostAppliedPrograms'])->name('most_applied_programs');
                Route::get('/citizen-registered-by-admin', [AdminReportController::class, 'citizenRegisteredByAdmin'])->name('citizen_registered_by_admin');
                Route::get('/ready-for-disbursement', [AdminReportController::class, 'readyForDisbursement'])->name('ready_for_disbursement');
                Route::get('/pending-evaluation', [AdminReportController::class, 'pendingEvaluation'])->name('pending_evaluation');
                Route::get('/revoked-recipients', [AdminReportController::class, 'revokedRecipients'])->name('revoked_recipients');
                Route::get('/approved-recipients', [AdminReportController::class, 'approvedRecipients'])->name('approved_recipients');
                Route::get('/disbursed-recipients', [AdminReportController::class, 'disbursedRecipients'])->name('disbursed_recipients');
            });

            Route::prefix('spk')->as('spk.')->group(function () {
                Route::get('/programs', [SpkController::class, 'programs'])->name('programs');
                Route::get('/{programId}', [SpkController::class, 'show'])
                    ->where('programId', UUID_PATTERN)->name('show');
            });
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');
    });
});