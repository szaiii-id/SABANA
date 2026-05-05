<?php

namespace Tests\Unit\Services\Assistance;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Services\Assistance\AssistanceExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistanceExportServiceTest extends TestCase
{
    use RefreshDatabase; // WAJIB ADA agar kita bisa insert data dummy ke DB Memory

    private AssistanceExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new AssistanceExportService();
    }

    public function test_generate_receipt_pdf_loads_correct_view_and_paper_size()
    {
        // 1. Arrange: Buat data dummy di DB agar findOrFail() sukses!
        $citizen = Citizen::factory()->create();
        $program = AssistanceProgram::factory()->create();
        
        $submission = AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
        ]);

        // 2. Mock Facade PDF (Agar tidak benar-benar render PDF yang berat)
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.assistance_receipt', \Mockery::type('array'))
            ->andReturnSelf(); 

        Pdf::shouldReceive('setPaper')
            ->once()
            ->with('a4', 'portrait')
            ->andReturnSelf();

        // 3. Act: Panggil fungsi service menggunakan ID asli dari database dummy
        $this->exportService->generateReceiptPdf($submission->id);

        // Assert ditangani otomatis oleh Mockery (memastikan ->once() terpenuhi)
        $this->assertTrue(true); 
    }
}