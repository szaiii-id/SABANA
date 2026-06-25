<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\SearchEngineInterface;
use App\Models\Citizen;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

final class IndexCitizenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const INDEX = 'citizens';

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private readonly string $citizenId,
    ) {}

    public function handle(SearchEngineInterface $elasticsearch): void
    {
        $citizen = Citizen::find($this->citizenId);

        if (!$citizen) {
            Log::warning('IndexCitizenJob: Citizen not found', [
                'citizen_id' => $this->citizenId,
            ]);
            return;
        }

        try {
            // ✅ Auto-create index jika belum ada
            $this->ensureIndexExists($elasticsearch);

            $elasticsearch->index([
                'index' => self::INDEX,
                'id'    => $citizen->id,
                'body'  => [
                    'id'                 => $citizen->id,
                    'full_name'          => $citizen->full_name,
                    'nik'                => $citizen->nik,
                    'family_card_number' => $citizen->family_card_number,
                    'whatsapp_number'    => $citizen->whatsapp_number,
                    'is_verified'        => (bool) $citizen->is_verified,
                    'created_at'         => $citizen->created_at?->toDateTimeString(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Elasticsearch Indexing failed', [
                'citizen_id' => $this->citizenId,
                'error'      => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
                return;
            }

            throw $e;
        }
    }

    /**
     * Buat index citizens jika belum ada.
     */
    private function ensureIndexExists(SearchEngineInterface $elasticsearch): void
    {
        try {
            $elasticsearch->indices()->get(['index' => self::INDEX]);
        } catch (Exception) {
            // Index belum ada — buat sekarang
            $elasticsearch->indices()->create([
                'index' => self::INDEX,
                'body'  => [
                    'mappings' => [
                        'properties' => [
                            'id'                 => ['type' => 'keyword'],
                            'nik'                => ['type' => 'text', 'analyzer' => 'standard'],
                            'full_name'          => ['type' => 'text', 'analyzer' => 'standard'],
                            'family_card_number' => ['type' => 'text'],
                            'whatsapp_number'    => ['type' => 'keyword'],
                            'is_verified'        => ['type' => 'boolean'],
                            'created_at'         => ['type' => 'date'],
                        ],
                    ],
                ],
            ]);

            Log::info('IndexCitizenJob: Auto-created index citizens');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('IndexCitizenJob Permanent Failure', [
            'citizen_id' => $this->citizenId,
            'error'      => $exception->getMessage(),
        ]);
    }
}