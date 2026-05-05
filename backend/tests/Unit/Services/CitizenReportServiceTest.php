<?php

namespace Tests\Unit\Services;

use App\Services\CitizenReportService;
use App\Services\FonnteService;
use App\Services\AspirasiService;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CitizenReportServiceTest extends TestCase
{
    private FonnteService|MockInterface $fonnteMock;
    private AspirasiService|MockInterface $emailMock;
    private CitizenReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fonnteMock = Mockery::mock(FonnteService::class);
        $this->emailMock = Mockery::mock(AspirasiService::class);
        
        $this->reportService = new CitizenReportService($this->fonnteMock, $this->emailMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sends_whatsapp_report_to_admin_and_auto_reply_to_citizen()
    {
        config(['services.fonnte.admin_number' => '08999999999']);
        
        $citizen = (object) [
            'full_name' => 'Akhmad',
            'nik' => '6301234567890123',
            'whatsapp_number' => '081122334455'
        ];
        $pesan = 'Jalan di desa saya rusak.';

        $this->fonnteMock
            ->shouldReceive('sendMessage')
            ->once()
            ->with('08999999999', Mockery::on(fn($msg) => str_contains($msg, '[LAPORAN WARGA - WA]') && str_contains($msg, $pesan)))
            ->andReturn(true);

        $this->fonnteMock
            ->shouldReceive('sendMessage')
            ->once()
            ->with('081122334455', Mockery::on(fn($msg) => str_contains($msg, 'Laporan Anda telah diterima')))
            ->andReturn(true);

        $this->reportService->sendViaWhatsapp($citizen, $pesan);
    }

    public function test_stops_execution_and_throws_exception_if_fonnte_fails()
    {
        config(['services.fonnte.admin_number' => '08999999999']);
        $citizen = (object) ['full_name' => 'Test', 'nik' => '123', 'whatsapp_number' => '081'];

        $this->fonnteMock
            ->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new Exception('Fonnte API Timeout'));

        // Pastikan balasan ke warga tidak terkirim jika gagal kirim ke admin
        $this->fonnteMock->shouldNotReceive('sendMessage')->with('081', Mockery::any());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Fonnte API Timeout');

        $this->reportService->sendViaWhatsapp($citizen, 'Test error');
    }
}