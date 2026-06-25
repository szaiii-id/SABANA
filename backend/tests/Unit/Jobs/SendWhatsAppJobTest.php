<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SendWhatsAppJob;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Exception;

class SendWhatsAppJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =============================================
    // PROPERTIES
    // =============================================

    public function test_job_has_tries_set_to_3()
    {
        $job = new SendWhatsAppJob('6281234567890', 'Test message');

        $this->assertEquals(3, $job->tries);
    }

    public function test_job_has_backoff_set_to_10()
    {
        $job = new SendWhatsAppJob('6281234567890', 'Test message');

        $this->assertEquals(10, $job->backoff);
    }

    // =============================================
    // HANDLE SUCCESS
    // =============================================

    public function test_handle_calls_fonnte_service_send_message()
    {
        $target = '6281234567890';
        $message = 'Test WhatsApp message';

        $fonnteService = Mockery::mock(FonnteService::class);
        $fonnteService->shouldReceive('sendMessage')
            ->once()
            ->with($target, $message)
            ->andReturn(true);

        $job = new SendWhatsAppJob($target, $message);
        $job->handle($fonnteService);

        $this->assertTrue(true);
    }

    // =============================================
    // HANDLE RETRY — FIRST ATTEMPT (attempts < tries)
    // =============================================

    public function test_handle_retries_when_fonnte_fails_on_first_attempt()
    {
        $target = '6281234567890';
        $message = 'Test WhatsApp message';

        $fonnteService = Mockery::mock(FonnteService::class);
        $fonnteService->shouldReceive('sendMessage')
            ->once()
            ->with($target, $message)
            ->andThrow(new Exception('Fonnte API error'));

        Log::shouldReceive('error')->once();

        $job = $this->getMockBuilder(SendWhatsAppJob::class)
            ->setConstructorArgs([$target, $message])
            ->onlyMethods(['release', 'attempts'])
            ->getMock();

        // attempts() dipanggil 2x dalam handle() — untuk log dan untuk if
        $job->expects($this->exactly(2))
            ->method('attempts')
            ->willReturn(1);

        $job->expects($this->once())
            ->method('release')
            ->with(10)
            ->willReturn(true);

        $job->handle($fonnteService);

        $this->assertTrue(true);
    }

    // =============================================
    // HANDLE THROWS — MAX ATTEMPTS (attempts >= tries)
    // =============================================

    public function test_handle_throws_exception_after_max_attempts()
    {
        $this->expectException(Exception::class);

        $target = '6281234567890';
        $message = 'Test WhatsApp message';

        $fonnteService = Mockery::mock(FonnteService::class);
        $fonnteService->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new Exception('Fonnte API error'));

        Log::shouldReceive('error')->once();

        $job = $this->getMockBuilder(SendWhatsAppJob::class)
            ->setConstructorArgs([$target, $message])
            ->onlyMethods(['attempts'])
            ->getMock();

        // attempts() dipanggil 2x — lalu throw exception
        $job->expects($this->exactly(2))
            ->method('attempts')
            ->willReturn(3);

        $job->handle($fonnteService);
    }

    // =============================================
    // FAILED
    // =============================================

    public function test_failed_logs_critical_error()
    {
        $target = '6281234567890';
        $message = 'Test WhatsApp message';

        Log::shouldReceive('critical')
            ->once()
            ->with('WhatsApp Job Permanent Failure', Mockery::any());

        $job = new SendWhatsAppJob($target, $message);
        $job->failed(new Exception('Permanent failure'));

        // Assertion: Log::critical() dipanggil — diverifikasi oleh Mockery
        $this->assertTrue(true);
    }
}