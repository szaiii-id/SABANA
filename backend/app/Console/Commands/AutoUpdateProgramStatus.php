<?php

namespace App\Console\Commands;

use App\Services\Admin\ProgramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoUpdateProgramStatus extends Command
{
    protected $signature = 'sabana:update-program-status';

    protected $description = 'Auto-toggle status program berdasarkan tanggal & kuota';

    public function __construct(
        private ProgramService $programService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Memeriksa status program...');

        try {
            // 1. Auto-toggle status
            $toggleLogs = $this->programService->autoToggleStatuses();

            // 2. Notifikasi kuota
            $quotaLogs = $this->programService->notifyQuotaThreshold();

            // Gabung semua log
            $logs = array_merge($toggleLogs, $quotaLogs);

            if (empty($logs)) {
                $this->info('Tidak ada perubahan status.');
                Log::info('SABANA: Auto-toggle program - tidak ada perubahan.');
                return self::SUCCESS;
            }

            foreach ($logs as $log) {
                $this->line("  • {$log}");
                Log::info("SABANA: {$log}");
            }

            $this->info('Selesai.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Gagal: ' . $e->getMessage());
            Log::error('SABANA: Auto-toggle program gagal.', [
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }
}