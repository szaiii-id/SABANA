<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\IndexProgramJob;
use App\Models\AssistanceProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;

final class IndexProgramJobTest extends TestCase
{
    use RefreshDatabase;

    private function getPrivateProperty(object $object, string $property): mixed
    {
        $ref = new ReflectionProperty($object, $property);
        return $ref->getValue($object);
    }

    // ===== CONSTRUCTOR =====

    public function test_job_stores_program_id_and_action(): void
    {
        $job = new IndexProgramJob('uuid-test', 'update');

        $this->assertEquals('uuid-test', $this->getPrivateProperty($job, 'programId'));
        $this->assertEquals('update', $this->getPrivateProperty($job, 'action'));
    }

    public function test_job_without_explicit_action_defaults_to_index(): void
    {
        $job = new IndexProgramJob('test-id');

        $this->assertEquals('index', $this->getPrivateProperty($job, 'action'));
    }

    // ===== RETRY CONFIGURATION =====

    public function test_job_has_retry_configuration(): void
    {
        $job = new IndexProgramJob('test-id', 'index');

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([5, 15, 30], $job->backoff);
    }

    // ===== MIDDLEWARE =====

    public function test_job_has_without_overlapping_middleware(): void
    {
        $job = new IndexProgramJob('test-id', 'index');
        $middleware = $job->middleware();

        $this->assertCount(1, $middleware);
    }

    // ===== JOB STRUCTURE =====

    public function test_job_implements_should_queue_interface(): void
    {
        $job = new IndexProgramJob('test-id');

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    public function test_job_uses_queueable_trait(): void
    {
        $job = new IndexProgramJob('test-id');

        $this->assertContains(\Illuminate\Foundation\Queue\Queueable::class, class_uses($job));
    }
}