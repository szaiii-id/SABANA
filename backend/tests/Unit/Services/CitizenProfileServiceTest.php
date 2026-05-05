<?php

namespace Tests\Unit\Services;

use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Services\CitizenProfileService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CitizenProfileServiceTest extends TestCase
{
    private CitizenRepositoryInterface|MockInterface $citizenRepositoryMock;
    private CitizenProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->citizenRepositoryMock = Mockery::mock(CitizenRepositoryInterface::class);
        $this->profileService = new CitizenProfileService($this->citizenRepositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_update_profile_successfully_using_repository()
    {
        $citizenMock = (object) ['id' => 'uuid-1234'];
        $updatePayload = [
            'full_name' => 'Akhmad Warga Baru',
            'whatsapp_number' => '081234567890'
        ];

        $this->citizenRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with('uuid-1234', $updatePayload)
            ->andReturn(true);

        $result = $this->profileService->updateProfile($citizenMock, $updatePayload);

        $this->assertTrue($result);
    }

    public function test_throws_exception_if_repository_fails_to_update()
    {
        $citizenMock = (object) ['id' => 'uuid-1234'];
        $updatePayload = ['full_name' => 'Test Error'];

        $this->citizenRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->andThrow(new \Exception('Database connection lost'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection lost');

        $this->profileService->updateProfile($citizenMock, $updatePayload);
    }
}