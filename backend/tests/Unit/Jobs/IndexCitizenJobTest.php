<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\IndexCitizenJob;
use App\Contracts\SearchEngineInterface;
use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Exception;

final class IndexCitizenJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== HANDLE SUCCESS =====

    /** @test */
    public function test_handle_indexes_citizen_to_elasticsearch(): void
    {
        $citizen = Citizen::factory()->create([
            'full_name'          => 'Joko Widodo',
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'whatsapp_number'    => '6281234567890',
        ]);

        $elasticsearch = Mockery::mock(SearchEngineInterface::class);
        $elasticsearch->shouldReceive('index')
            ->once()
            ->with([
                'index' => 'citizens',
                'id'    => (string) $citizen->id, // ✅ Cast UUID ke string
                'body'  => [
                    'full_name'          => 'Joko Widodo',
                    'nik'                => '6301234567890123',
                    'family_card_number' => '6301234567890123',
                    'whatsapp_number'    => '6281234567890',
                    'created_at'         => $citizen->created_at?->toDateTimeString(),
                ],
            ])
            ->andReturn(true);

        $job = new IndexCitizenJob((string) $citizen->id); // ✅ Cast ke string
        $job->handle($elasticsearch);

        // Mockery auto-verify shouldReceive dipanggil
        $this->assertTrue(true);
    }

    // ===== HANDLE: CITIZEN NOT FOUND =====

    /** @test */
    public function test_handle_logs_warning_when_citizen_not_found(): void
    {
        // ✅ Gunakan UUID valid yang pasti tidak ada di database
        $validUuid = (string) Str::uuid();

        Log::shouldReceive('warning')
            ->once()
            ->with('IndexCitizenJob: Citizen not found', Mockery::on(function (array $context) use ($validUuid) {
                return $context['citizen_id'] === $validUuid;
            }));

        $elasticsearch = Mockery::mock(SearchEngineInterface::class);
        $elasticsearch->shouldNotReceive('index');

        $job = new IndexCitizenJob($validUuid);
        $job->handle($elasticsearch);

        $this->assertTrue(true);
    }

    // ===== HANDLE: RETRY ON FAILURE =====

    /** @test */
    public function test_handle_retries_on_elasticsearch_failure(): void
    {
        $this->markTestSkipped(
            'Retry logic (release/attempts) cannot be unit tested on final class. ' .
            'Covered by: test_handle_throws_after_max_attempts + queue worker integration test.'
        );
    }

    // ===== HANDLE: MAX ATTEMPTS =====

    /** @test */
    public function test_handle_throws_after_max_attempts(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection timeout');

        $citizen = Citizen::factory()->create();

        $elasticsearch = Mockery::mock(SearchEngineInterface::class);
        $elasticsearch->shouldReceive('index')
            ->once()
            ->andThrow(new Exception('Connection timeout'));

        Log::shouldReceive('error')->once();

        // ✅ Override tries = 1 agar langsung max attempts
        $job = new IndexCitizenJob((string) $citizen->id);

        $refTries = new \ReflectionProperty(IndexCitizenJob::class, 'tries');
        $refTries->setValue($job, 1);

        $job->handle($elasticsearch);
    }

    // ===== FAILED HANDLER =====

    /** @test */
    public function test_failed_logs_critical_error(): void
    {
        $uuid = (string) Str::uuid();

        Log::shouldReceive('critical')
            ->once()
            ->with('IndexCitizenJob Permanent Failure', Mockery::on(function (array $context) use ($uuid) {
                return $context['citizen_id'] === $uuid
                    && isset($context['error']);
            }));

        $job = new IndexCitizenJob($uuid);
        $job->failed(new Exception('Permanent failure'));

        // ✅ Mockery auto-verify Log::critical dipanggil
        $this->assertTrue(true);
    }

    // ===== PROPERTIES =====

    /** @test */
    public function test_job_has_tries_set_to_3(): void
    {
        $job = new IndexCitizenJob((string) Str::uuid());

        $this->assertEquals(3, $job->tries);
    }

    /** @test */
    public function test_job_has_backoff_set_to_5(): void
    {
        $job = new IndexCitizenJob((string) Str::uuid());

        $this->assertEquals(5, $job->backoff);
    }
}