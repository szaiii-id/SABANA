<?php

namespace App\Providers;

use App\Repositories\CitizenRepository;
use App\Repositories\Contracts\CitizenRepositoryInterface;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
