<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Contracts\Storage\FileStorageInterface;
use App\Models\AssistanceProgram;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use App\Services\Admin\ProgramService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class ProgramServiceTest extends TestCase
{
    private ProgramRepositoryInterface $programRepository;
    private FileStorageInterface $storage;
    private ProgramService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->programRepository = Mockery::mock(ProgramRepositoryInterface::class);
        $this->storage = Mockery::mock(FileStorageInterface::class);
        $this->service = new ProgramService($this->programRepository, $this->storage);
    }

    private function makeProgram(array $attributes = []): AssistanceProgram
    {
        $program = new AssistanceProgram();
        $program->setAttribute('id', $attributes['id'] ?? '550e8400-e29b-41d4-a716-446655440000');
        $program->setAttribute('name', $attributes['name'] ?? 'Program Test');
        $program->setAttribute('slug', $attributes['slug'] ?? 'program-test');
        $program->setAttribute('description', $attributes['description'] ?? 'Deskripsi');
        $program->setAttribute('status', $attributes['status'] ?? 'draft');
        $program->setAttribute('is_active', $attributes['is_active'] ?? true);
        $program->setAttribute('criteria', $attributes['criteria'] ?? null);
        $program->setAttribute('ai_config', $attributes['ai_config'] ?? null);
        $program->setAttribute('banner_url', $attributes['banner_url'] ?? null);
        $program->setAttribute('banner_public_id', $attributes['banner_public_id'] ?? null);
        $program->setAttribute('quota_total', $attributes['quota_total'] ?? null);
        $program->setAttribute('benefit_amount', $attributes['benefit_amount'] ?? null);
        $program->setAttribute('start_date', $attributes['start_date'] ?? null);
        $program->setAttribute('end_date', $attributes['end_date'] ?? null);
        $program->syncOriginal();
        return $program;
    }

    // ===== HAPPY PATH (8 test) =====

    public function test_get_list_delegates_to_repository(): void
    {
        $paginator = new LengthAwarePaginator(collect(), 0, 15);

        $this->programRepository
            ->shouldReceive('getAll')
            ->with(['status' => 'active'])
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList(['status' => 'active']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_create_program_returns_program(): void
    {
        $program = $this->makeProgram(['name' => 'Bantuan Baru', 'slug' => 'bantuan-baru']);

        $this->programRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($program);

        $result = $this->service->createProgram([
            'name' => 'Bantuan Baru',
            'description' => 'Deskripsi',
        ]);

        $this->assertInstanceOf(AssistanceProgram::class, $result);
        $this->assertEquals('Bantuan Baru', $result->name);
    }

    public function test_create_program_generates_slug(): void
    {
        $this->programRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return !empty($data['slug']);
            }))
            ->andReturn($this->makeProgram(['slug' => 'bantuan-beras']));

        $result = $this->service->createProgram([
            'name' => 'Bantuan Beras',
            'description' => 'Deskripsi',
        ]);

        $this->assertEquals('bantuan-beras', $result->slug);
    }

    public function test_create_program_with_banner(): void
    {
        $file = UploadedFile::fake()->image('banner.jpg');

        $this->storage
            ->shouldReceive('upload')
            ->with($file, 'programs')
            ->once()
            ->andReturn(['url' => 'https://example.com/banner.jpg', 'public_id' => 'banner_123']);

        $this->programRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->makeProgram(['banner_url' => 'https://example.com/banner.jpg']));

        $result = $this->service->createProgram([
            'name' => 'Program Banner',
            'description' => 'Deskripsi',
        ], $file);

        $this->assertEquals('https://example.com/banner.jpg', $result->banner_url);
    }

    public function test_close_program_changes_status(): void
    {
        $program = $this->makeProgram([
            'id' => '550e8400-e29b-41d4-a716-446655440001',
            'status' => 'active',
        ]);

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440001')
            ->once()
            ->andReturn($program);

        $this->programRepository
            ->shouldReceive('update')
            ->with($program, ['status' => 'closed'])
            ->once()
            ->andReturn(true);

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440001')
            ->once()
            ->andReturn($this->makeProgram(['id' => '550e8400-e29b-41d4-a716-446655440001', 'status' => 'closed']));

        $result = $this->service->closeProgram('550e8400-e29b-41d4-a716-446655440001');

        $this->assertInstanceOf(AssistanceProgram::class, $result);
    }

    public function test_reopen_program_changes_status(): void
    {
        $program = $this->makeProgram([
            'id' => '550e8400-e29b-41d4-a716-446655440002',
            'status' => 'closed',
        ]);

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440002')
            ->times(2)
            ->andReturn($program, $this->makeProgram(['id' => '550e8400-e29b-41d4-a716-446655440002', 'status' => 'active']));

        $this->programRepository
            ->shouldReceive('update')
            ->with($program, ['status' => 'active'])
            ->once()
            ->andReturn(true);

        $result = $this->service->reopenProgram('550e8400-e29b-41d4-a716-446655440002');

        $this->assertInstanceOf(AssistanceProgram::class, $result);
    }

    public function test_update_program_changes_name(): void
    {
        $program = $this->makeProgram([
            'id' => '550e8400-e29b-41d4-a716-446655440003',
            'name' => 'Nama Lama',
        ]);

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440003')
            ->times(2)
            ->andReturn($program, $this->makeProgram(['id' => '550e8400-e29b-41d4-a716-446655440003', 'name' => 'Nama Baru']));

        $this->programRepository
            ->shouldReceive('existsByNameExcludingId')
            ->with('Nama Baru', '550e8400-e29b-41d4-a716-446655440003')
            ->once()
            ->andReturn(false);

        $this->programRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $result = $this->service->updateProgram('550e8400-e29b-41d4-a716-446655440003', ['name' => 'Nama Baru']);

        $this->assertInstanceOf(AssistanceProgram::class, $result);
    }

    public function test_upload_banner_returns_program(): void
    {
        $program = $this->makeProgram([
            'id' => '550e8400-e29b-41d4-a716-446655440007',
        ]);

        $file = UploadedFile::fake()->image('banner.jpg');

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440007')
            ->times(2)
            ->andReturn($program, $this->makeProgram(['id' => '550e8400-e29b-41d4-a716-446655440007', 'banner_url' => 'https://example.com/new.jpg']));

        $this->storage
            ->shouldReceive('upload')
            ->once()
            ->andReturn(['url' => 'https://example.com/new.jpg', 'public_id' => 'new_123']);

        $this->programRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $result = $this->service->uploadBanner('550e8400-e29b-41d4-a716-446655440007', $file);

        $this->assertInstanceOf(AssistanceProgram::class, $result);
    }

    // ===== SAD PATH (4 test) =====

    public function test_close_non_active_program_throws(): void
    {
        $program = $this->makeProgram([
            'id' => '550e8400-e29b-41d4-a716-446655440004',
            'status' => 'draft',
        ]);

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440004')
            ->once()
            ->andReturn($program);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Hanya program aktif yang dapat ditutup.');

        $this->service->closeProgram('550e8400-e29b-41d4-a716-446655440004');
    }

    public function test_reopen_non_closed_program_throws(): void
    {
        $program = $this->makeProgram([
            'id' => '550e8400-e29b-41d4-a716-446655440005',
            'status' => 'active',
        ]);

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440005')
            ->once()
            ->andReturn($program);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Hanya program tertutup yang dapat dibuka kembali.');

        $this->service->reopenProgram('550e8400-e29b-41d4-a716-446655440005');
    }

    public function test_close_program_not_found_throws(): void
    {
        $this->programRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->closeProgram('nonexistent');
    }

    public function test_delete_program_not_found_throws(): void
    {
        $this->programRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->deleteProgram('nonexistent');
    }

    // ===== BOUNDARY (1 test) =====

    public function test_duplicate_program_creates_copy(): void
    {
        $program = $this->makeProgram([
            'id' => '550e8400-e29b-41d4-a716-446655440006',
            'name' => 'Original',
            'status' => 'active',
        ]);

        $this->programRepository
            ->shouldReceive('findById')
            ->with('550e8400-e29b-41d4-a716-446655440006')
            ->once()
            ->andReturn($program);

        $this->programRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->makeProgram(['name' => 'Original 2', 'status' => 'draft']));

        $result = $this->service->duplicateProgram('550e8400-e29b-41d4-a716-446655440006');

        $this->assertEquals('draft', $result->status);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_create_program_with_empty_criteria(): void
    {
        $this->programRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->makeProgram(['criteria' => null]));

        $result = $this->service->createProgram([
            'name' => 'No Criteria',
            'description' => 'Deskripsi',
        ]);

        $this->assertNull($result->criteria);
    }

    // ===== SECURITY (1 test) =====

    public function test_create_program_flushes_cache(): void
    {
        Cache::shouldReceive('tags')->with(['programs'])->andReturn(new class {
            public function flush() {}
        });
        Cache::shouldReceive('tags')->with(['spk'])->andReturn(new class {
            public function flush() {}
        });
        Cache::shouldReceive('forget')->with('citizen_active_programs')->andReturn(true);

        $this->programRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->makeProgram(['name' => 'Cache Test']));

        $result = $this->service->createProgram([
            'name' => 'Cache Test',
            'description' => 'Deskripsi',
        ]);

        $this->assertInstanceOf(AssistanceProgram::class, $result);
    }

    // ===== GAP COVERAGE (1 test) =====

    public function test_update_program_not_found_throws(): void
    {
        $this->programRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->updateProgram('nonexistent', ['name' => 'Test']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}