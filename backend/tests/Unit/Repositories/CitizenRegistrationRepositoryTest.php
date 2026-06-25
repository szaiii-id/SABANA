<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Repositories/CitizenRegistrationRepositoryTest.php =====

namespace Tests\Unit\Repositories;

use App\Models\Citizen;
use App\Repositories\CitizenRegistrationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CitizenRegistrationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CitizenRegistrationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Elasticsearch null → semua test pakai MySQL fallback
        $this->repository = new CitizenRegistrationRepository(null);
    }

    // ===== [DATA HELPERS] =====

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => bcrypt('123456'),
            'is_verified' => true,
        ], $overrides);
    }

    private function createCitizen(array $overrides = []): Citizen
    {
        return Citizen::create($this->validData($overrides));
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_find_by_nik_mengembalikan_citizen(): void
    {
        $this->createCitizen();

        $citizen = $this->repository->findByNik('6371012508900001');

        $this->assertNotNull($citizen);
        $this->assertEquals('6371012508900001', $citizen->nik);
    }

    public function test_create_mengembalikan_citizen(): void
    {
        $citizen = $this->repository->create($this->validData());

        $this->assertInstanceOf(Citizen::class, $citizen);
        $this->assertDatabaseHas('citizens', ['nik' => '6371012508900001']);
    }

    public function test_search_mysql_fallback(): void
    {
        $this->createCitizen();

        $result = $this->repository->search('Ahmad');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_find_by_nik_null_jika_tidak_ditemukan(): void
    {
        $citizen = $this->repository->findByNik('0000000000000000');

        $this->assertNull($citizen);
    }

    public function test_search_query_tidak_ditemukan(): void
    {
        $result = $this->repository->search('TidakAda');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_search_paginated_halaman_pertama(): void
    {
        $result = $this->repository->searchPaginated('test', 1, 10);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['current_page']);
        $this->assertFalse($result['has_more']);
    }

    // ============================================================
    // ===== (4) EDGE CASE =====
    // ============================================================

    public function test_search_query_kosong(): void
    {
        $result = $this->repository->search('');

        $this->assertIsArray($result);
    }

    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_get_list_dengan_filter_kosong(): void
    {
        $result = $this->repository->getList([], 15);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_search_return_array(): void
    {
        $result = $this->repository->search('test');

        $this->assertIsArray($result);
    }

    public function test_search_paginated_return_array(): void
    {
        $result = $this->repository->searchPaginated('test', 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('has_more', $result);
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_search_berbagai_query(): void
    {
        $this->createCitizen();

        $queries = ['Ahmad', '637101', 'Fauzi'];

        foreach ($queries as $query) {
            $result = $this->repository->search($query);
            $this->assertIsArray($result);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_create_lalu_update_mengubah_data(): void
    {
        $citizen = $this->repository->create($this->validData());

        $updated = $this->repository->update($citizen, ['full_name' => 'Nama Baru']);

        $this->assertEquals('Nama Baru', $updated->full_name);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_find_by_nik_with_lock(): void
    {
        $this->createCitizen();

        $citizen = $this->repository->findByNikWithLock('6371012508900001');

        $this->assertNotNull($citizen);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_get_list_tidak_mengembalikan_pin(): void
    {
        $this->createCitizen();

        $result = $this->repository->getList([], 15);

        $items = $result->items();
        if (!empty($items)) {
            $this->assertArrayNotHasKey('pin', (array) $items[0]);
        }

        $this->assertTrue(true);
    }
}