<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\AutoUpdateProgramStatus;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sabana:update-program-status')->everyFiveMinutes();

Schedule::command('sabana:cleanup-soft-deletes')
    ->dailyAt('03:00')
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cleanup.log'));

Schedule::command('evaluation:trigger')
    ->dailyAt('06:00')
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/evaluation.log'));