<?php

// app/Jobs/SendWhatsAppJob.php
namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $target, 
        public string $message
    ) {}

    public function handle(FonnteService $fonnteService): void
    {
        try {
            $fonnteService->sendMessage($this->target, $this->message);
        } catch (\Exception $e) {
            Log::error("Gagal kirim WA via Job: " . $e->getMessage());
        }
    }
}
