<?php

namespace App\Providers;

use App\Repositories\AdminRepository;
use App\Repositories\CitizenRegistrationRepository;
use App\Repositories\CitizenRepository;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Repositories\Contracts\CitizenRegistrationRepositoryInterface;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\DisbursementReceiptRepositoryInterface;
use App\Repositories\Contracts\DisbursementRepositoryInterface;
use App\Repositories\Contracts\EvaluationRepositoryInterface;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\VerificationRepositoryInterface;
use App\Repositories\DashboardRepository;
use App\Repositories\DisbursementReceiptRepository;
use App\Repositories\DisbursementRepository;
use App\Repositories\EvaluationRepository;
use App\Repositories\ProgramRepository;
use App\Repositories\ReportRepository;
use App\Repositories\VerificationRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            CitizenRepositoryInterface::class,
            CitizenRepository::class
        );
        $this->app->bind(
            AdminRepositoryInterface::class,
            AdminRepository::class
        );
        $this->app->bind(
            CitizenRegistrationRepositoryInterface::class,
            CitizenRegistrationRepository::class
        );

        $this->app->bind(
            VerificationRepositoryInterface::class,
            VerificationRepository::class
        );
        $this->app->bind(
            EvaluationRepositoryInterface::class,
            EvaluationRepository::class
        );

        $this->app->bind(
            DisbursementRepositoryInterface::class,
            DisbursementRepository::class
        );

        $this->app->bind(
            DisbursementReceiptRepositoryInterface::class,
            DisbursementReceiptRepository::class
        );

        $this->app->bind(
            ReportRepositoryInterface::class,
            ReportRepository::class
        );

        $this->app->bind(
            DashboardRepositoryInterface::class, 
            DashboardRepository::class
        );


        $this->app->bind(
            ProgramRepositoryInterface::class, 
            ProgramRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
