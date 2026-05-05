<?php

namespace Tests\Feature\Controllers\Api\Citizen;

use App\Models\Citizen;
use App\Services\CitizenReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private CitizenReportService|MockInterface $reportServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportServiceMock = Mockery::mock(CitizenReportService::class);
        $this->app->instance(CitizenReportService::class, $this->reportServiceMock);
    }

    public function test_whatsapp_report_returns_200_on_success()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();
        $payload = ['pesan' => 'Jalan desa rusak berat. Mohon bantuan.'];

        $this->reportServiceMock
            ->shouldReceive('sendViaWhatsapp')
            ->once()
            ->with(Mockery::on(fn($c) => $c->id === $citizen->id), $payload['pesan'])
            ->andReturnNull();

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/citizen/report/whatsapp', $payload); // URL DISESUAIKAN

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Laporan berhasil diteruskan ke WhatsApp Admin.']);
    }

    public function test_email_report_returns_200_on_success()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create();
        
        // PERBAIKAN: Tambahkan 'nama' ke dalam payload agar lolos validasi SendEmailRequest
        $payload = [
            'nama' => $citizen->full_name, // <-- TAMBAHKAN BARIS INI
            'email' => 'warga@example.com',
            'subjek' => 'Bantuan Lambat',
            'pesan' => 'Mohon dicek kembali pengajuan saya.'
        ];

        $this->reportServiceMock
            ->shouldReceive('sendViaEmail')
            ->once()
            ->with(
                Mockery::on(fn($c) => $c->id === $citizen->id), 
                $payload['subjek'], 
                $payload['pesan'], 
                $payload['email']
            )
            ->andReturnNull();

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/citizen/report/email', $payload);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Laporan berhasil dikirim ke Email Instansi.']);
    }
}