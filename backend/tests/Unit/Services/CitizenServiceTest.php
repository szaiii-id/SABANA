<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Citizen;
use App\Services\CitizenService;
use App\Services\FonnteService;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Contracts\SearchEngineInterface;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('service')]
final class CitizenServiceTest extends TestCase
{
    private CitizenService $service;
    private $citizenRepositoryMock;
    private $elasticsearchMock;
    private $fonnteServiceMock;

    private array $validData = [
        'nik'                => '6301234567890123',
        'family_card_number' => '6301234567890123',
        'full_name'          => 'AKHMAD WARGA',
        'whatsapp_number'    => '081234567890',
        'pin'                => '123456',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->citizenRepositoryMock = Mockery::mock(CitizenRepositoryInterface::class);
        $this->elasticsearchMock = Mockery::mock(SearchEngineInterface::class);
        $this->fonnteServiceMock = Mockery::mock(FonnteService::class);

        $this->service = new CitizenService(
            $this->citizenRepositoryMock,
            $this->elasticsearchMock,
            $this->fonnteServiceMock
        );

        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Buat mock Citizen dengan semua field yang diperlukan.
     */
    private function makeCitizen(array $overrides = []): Citizen
    {
        $citizen = new Citizen(array_merge([
            'id'                 => 'uuid-fake-12345',
            'nik'                => '6301234567890123',
            'full_name'          => 'AKHMAD WARGA',
            'whatsapp_number'    => '081234567890',
            'family_card_number' => '6301234567890123',
            'is_verified'        => false,
        ], $overrides));

        $citizen->created_at = $overrides['created_at'] ?? now();
        $citizen->id = $overrides['id'] ?? 'uuid-fake-12345';

        return $citizen;
    }

    // ========================================================================
    // HAPPY PATH
    // ========================================================================

    #[Group('critical')]
    public function test_register_creates_new_citizen_when_nik_not_exists(): void
    {
        $fakeCitizen = $this->makeCitizen();

        $this->citizenRepositoryMock
            ->shouldReceive('findByNik')
            ->with($this->validData['nik'])
            ->once()
            ->andReturnNull();

        $this->citizenRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($fakeCitizen);

        $this->fonnteServiceMock
            ->shouldReceive('sendMessage')
            ->once()
            ->with('081234567890', Mockery::type('string'));

        $this->elasticsearchMock
            ->shouldReceive('index')
            ->once()
            ->andReturnNull();

        $result = $this->service->registerCitizen($this->validData);

        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertEquals('6301234567890123', $result->nik);
    }

    public function test_register_updates_existing_unverified_citizen(): void
    {
        $existingCitizen = $this->makeCitizen([
            'id'         => 'uuid-existing',
            'full_name'  => 'OLD NAME',
        ]);

        $updatedCitizen = $this->makeCitizen([
            'id'        => 'uuid-existing',
            'full_name' => 'AKHMAD WARGA',
        ]);

        $this->citizenRepositoryMock
            ->shouldReceive('findByNik')
            ->with($this->validData['nik'])
            ->once()
            ->andReturn($existingCitizen);

        // Perbaikan: mock update dengan parameter apapun
        $this->citizenRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with('uuid-existing', Mockery::type('array'))
            ->andReturn(true);

        $this->citizenRepositoryMock
            ->shouldReceive('findByNik')
            ->andReturn($updatedCitizen);

        $this->fonnteServiceMock
            ->shouldReceive('sendMessage')
            ->once();

        $this->elasticsearchMock
            ->shouldReceive('index')
            ->once()
            ->andReturnNull();

        $result = $this->service->registerCitizen($this->validData);

        $this->assertEquals('AKHMAD WARGA', $result->full_name);
    }

    #[Group('critical')]
    public function test_register_throws_exception_when_citizen_already_verified(): void
    {
        $verifiedCitizen = $this->makeCitizen([
            'id'          => 'uuid-verified',
            'is_verified' => true,
        ]);

        $this->citizenRepositoryMock
            ->shouldReceive('findByNik')
            ->with($this->validData['nik'])
            ->once()
            ->andReturn($verifiedCitizen);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('NIK ini sudah terdaftar dan aktif.');

        $this->service->registerCitizen($this->validData);
    }

    public function test_register_rolls_back_on_database_error(): void
    {
        $this->citizenRepositoryMock
            ->shouldReceive('findByNik')
            ->once()
            ->andThrow(new \Exception('Database Connection Failed'));

        Log::shouldReceive('error')->once();

        $this->expectException(\Exception::class);

        $this->service->registerCitizen($this->validData);
    }

    public function test_register_still_succeeds_when_elasticsearch_fails(): void
    {
        $fakeCitizen = $this->makeCitizen(['id' => 'uuid-es-fail']);

        $this->citizenRepositoryMock
            ->shouldReceive('findByNik')
            ->once()
            ->andReturnNull();

        $this->citizenRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($fakeCitizen);

        $this->fonnteServiceMock
            ->shouldReceive('sendMessage')
            ->once();

        $this->elasticsearchMock
            ->shouldReceive('index')
            ->once()
            ->andThrow(new \Exception('Elasticsearch down'));

        Log::shouldReceive('error')->once();

        $result = $this->service->registerCitizen($this->validData);

        $this->assertInstanceOf(Citizen::class, $result);
    }

    public function test_register_sends_whatsapp_with_correct_format(): void
    {
        $fakeCitizen = $this->makeCitizen(['id' => 'uuid-wa-format']);

        $this->citizenRepositoryMock->shouldReceive('findByNik')->once()->andReturnNull();
        $this->citizenRepositoryMock->shouldReceive('create')->once()->andReturn($fakeCitizen);
        $this->elasticsearchMock->shouldReceive('index')->once()->andReturnNull();

        $this->fonnteServiceMock
            ->shouldReceive('sendMessage')
            ->once()
            ->with(
                '081234567890',
                Mockery::on(function ($message) {
                    return str_contains($message, '[SABANA KALSEL]')
                        && str_contains($message, 'AKHMAD WARGA')
                        && str_contains($message, 'kode verifikasi');
                })
            );

        $result = $this->service->registerCitizen($this->validData);

        // ✅ Tambahkan assertion
        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertEquals('AKHMAD WARGA', $result->full_name);
    }
}