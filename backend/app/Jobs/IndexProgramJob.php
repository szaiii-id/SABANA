<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AssistanceProgram;
use App\Services\Admin\ProgramSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

final class IndexProgramJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    
    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly string $programId,
        private readonly string $action = 'index',
    ) {}

    /**
     * Hindari indexing program yang sama secara bersamaan.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("program-index:{$this->programId}"))
                ->releaseAfter(10)
                ->expireAfter(30),
        ];
    }

    public function handle(ProgramSearchService $searchService): void
    {
        try {
            match ($this->action) {
                'delete' => $searchService->delete($this->programId),
                default  => $this->indexProgram($searchService),
            };

            Log::info("SABANA: Elasticsearch index program [{$this->programId}] action [{$this->action}] berhasil.");

        } catch (\Throwable $e) {
            Log::error("SABANA: Gagal indexing program [{$this->programId}].", [
                'action' => $this->action,
                'error'  => $e->getMessage(),
            ]);

            throw $e; // Trigger retry
        }
    }

    private function indexProgram(ProgramSearchService $searchService): void
    {
        $program = AssistanceProgram::find($this->programId);

        if (!$program) {
            // Program sudah dihapus sebelum job dijalankan
            $searchService->delete($this->programId);
            return;
        }

        $searchService->index($program);
    }
}