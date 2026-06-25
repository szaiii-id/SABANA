<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Email;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Services\AspirasiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;

final class SendEmailJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== HAPPY PATH =====

    /** @test */
    public function test_job_dispatches_to_queue(): void
    {
        Queue::fake();

        $payload = [
            'nama'   => 'Muhammad Noor',
            'email'  => 'test@email.com',
            'subjek' => 'Subjek Laporan',
            'pesan'  => 'Pesan laporan test',
        ];

        SendEmailJob::dispatch($payload, 'Muhammad Noor', 'test@email.com');

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($payload) {
            return $job->payload === $payload
                && $job->targetName === 'Muhammad Noor'
                && $job->targetEmail === 'test@email.com';
        });
    }

    /** @test */
    public function test_job_handle_calls_aspirasi_service(): void
    {
        $payload = [
            'nama'   => 'Muhammad Noor',
            'email'  => 'test@email.com',
            'subjek' => 'Subjek',
            'pesan'  => 'Pesan laporan',
        ];

        $aspirasiService = Mockery::mock(AspirasiService::class);
        $aspirasiService->shouldReceive('prosesDanKirimAspirasi')
            ->once()
            ->with($payload);

        $aspirasiService->shouldReceive('kirimBalasanOtomatis')
            ->once()
            ->with($payload, 'test@email.com', 'Muhammad Noor');

        $job = new SendEmailJob($payload, 'Muhammad Noor', 'test@email.com');
        $job->handle($aspirasiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function test_job_handle_without_auto_reply(): void
    {
        $payload = [
            'nama'   => 'Anonim',
            'email'  => 'anonim@email.com',
            'subjek' => 'Subjek',
            'pesan'  => 'Pesan',
        ];

        $aspirasiService = Mockery::mock(AspirasiService::class);
        $aspirasiService->shouldReceive('prosesDanKirimAspirasi')
            ->once()
            ->with($payload);

        $aspirasiService->shouldNotReceive('kirimBalasanOtomatis');

        $job = new SendEmailJob($payload, null, null);
        $job->handle($aspirasiService);

        $this->assertTrue(true);
    }

    // ===== SAD PATH =====

    /** @test */
    public function test_job_retries_on_failure(): void
    {
        $payload = [
            'nama'   => 'Test',
            'email'  => 'test@email.com',
            'subjek' => 'Subjek',
            'pesan'  => 'Pesan',
        ];

        $aspirasiService = Mockery::mock(AspirasiService::class);
        $aspirasiService->shouldReceive('prosesDanKirimAspirasi')
            ->once()
            ->andThrow(new \Exception('SMTP Connection timeout'));

        Log::shouldReceive('error')->once();

        $job = $this->getMockBuilder(SendEmailJob::class)
            ->setConstructorArgs([$payload, null, null])
            ->onlyMethods(['release', 'attempts'])
            ->getMock();

        $job->expects($this->exactly(2))
            ->method('attempts')
            ->willReturn(1);

        $job->expects($this->once())
            ->method('release')
            ->with(60);

        $job->handle($aspirasiService);

        $this->assertTrue(true);
    }

    /** @test */
    public function test_job_throws_after_max_attempts(): void
    {
        $this->expectException(\Exception::class);

        $payload = [
            'nama'   => 'Test',
            'email'  => 'test@email.com',
            'subjek' => 'Subjek',
            'pesan'  => 'Pesan',
        ];

        $aspirasiService = Mockery::mock(AspirasiService::class);
        $aspirasiService->shouldReceive('prosesDanKirimAspirasi')
            ->once()
            ->andThrow(new \Exception('SMTP Error'));

        Log::shouldReceive('error')->once();

        $job = $this->getMockBuilder(SendEmailJob::class)
            ->setConstructorArgs([$payload, null, null])
            ->onlyMethods(['attempts'])
            ->getMock();

        $job->expects($this->exactly(2))
            ->method('attempts')
            ->willReturn(3);

        $job->handle($aspirasiService);
    }

    // ===== FAILED HANDLER =====

    /** @test */
    public function test_failed_logs_critical(): void
    {
        $payload = ['nama' => 'Test', 'email' => 'test@email.com', 'subjek' => 'S', 'pesan' => 'P'];

        Log::shouldReceive('critical')
            ->once()
            ->with('SendEmailJob Permanent Failure', Mockery::on(function ($context) {
                return isset($context['error'], $context['payload']);
            }));

        $job = new SendEmailJob($payload);
        $job->failed(new \Exception('Permanent failure'));

        $this->assertTrue(true);
    }

    // ===== PROPERTIES =====

    /** @test */
    public function test_job_has_correct_properties(): void
    {
        $payload = ['nama' => 'Test', 'email' => 'a@b.com', 'subjek' => 'S', 'pesan' => 'P'];

        $job = new SendEmailJob($payload, 'Muhammad', 'test@email.com');

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
        $this->assertEquals('Muhammad', $job->targetName);
        $this->assertEquals('test@email.com', $job->targetEmail);
    }
}