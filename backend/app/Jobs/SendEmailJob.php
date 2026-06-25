<?php

namespace App\Jobs;

use App\Services\AspirasiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Payload data untuk email.
     * Format: ['nama' => '', 'email' => '', 'subjek' => '', 'pesan' => '']
     */
    public array $payload;

    /**
     * Nama penerima balasan otomatis (opsional).
     * Hanya diisi jika email berasal dari Lapor Warga (terautentikasi).
     */
    public ?string $targetName;

    /**
     * Email penerima balasan otomatis (opsional).
     * Hanya diisi jika email berasal dari Lapor Warga (terautentikasi).
     */
    public ?string $targetEmail;

    /**
     * Jumlah maksimal retry jika gagal.
     */
    public int $tries = 3;

    /**
     * Waktu tunggu antar retry (detik).
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param array       $payload     Data email [nama, email, subjek, pesan]
     * @param string|null $targetName  Nama penerima auto-reply (opsional)
     * @param string|null $targetEmail Email penerima auto-reply (opsional)
     */
    public function __construct(
        array $payload,
        ?string $targetName = null,
        ?string $targetEmail = null
    ) {
        $this->payload     = $payload;
        $this->targetName  = $targetName;
        $this->targetEmail = $targetEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(AspirasiService $aspirasiService): void
    {
        try {
            // 1. Kirim email ke admin SABANA
            $aspirasiService->prosesDanKirimAspirasi($this->payload);

            // 2. Kirim auto-reply ke warga (jika ada data penerima)
            if ($this->targetName && $this->targetEmail) {
                $aspirasiService->kirimBalasanOtomatis(
                    $this->payload,
                    $this->targetEmail,
                    $this->targetName
                );
            }

        } catch (\Exception $e) {
            Log::error('SendEmailJob Failed', [
                'error'   => $e->getMessage(),
                'payload' => $this->payload,
                'attempt' => $this->attempts(),
            ]);

            // Lepaskan ke queue agar bisa di-retry
            $this->release($this->backoff);

            // Jika sudah 3x gagal, throw agar masuk failed_jobs
            if ($this->attempts() >= $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('SendEmailJob Permanent Failure', [
            'error'   => $exception->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}   