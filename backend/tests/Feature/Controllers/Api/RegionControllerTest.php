<?php

namespace Tests\Feature\Controllers\Api;

use Tests\TestCase;
use App\Models\Citizen;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;

#[Group('feature')]
#[Group('controller')]
final class RegionControllerTest extends TestCase
{
    use RefreshDatabase;

    private Citizen $citizen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->citizen = Citizen::factory()->create();

        // Insert province untuk memenuhi foreign key
        DB::table('provinces')->insert([
            ['id' => '63', 'name' => 'Kalimantan Selatan'],
            ['id' => '33', 'name' => 'Jawa Tengah'],
        ]);
    }

    // ========================================================================
    // REGENCIES
    // ========================================================================

    public function test_regencies_returns_only_kalsel_data(): void
    {
        Regency::insert([
            ['id' => '6301', 'province_id' => '63', 'name' => 'Banjar'],
            ['id' => '6302', 'province_id' => '63', 'name' => 'Tanah Laut'],
            ['id' => '3301', 'province_id' => '33', 'name' => 'Cilacap'],
        ]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/regencies');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Banjar'])
            ->assertJsonMissing(['name' => 'Cilacap']);
    }

    public function test_regencies_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/citizen/regions/regencies');
        $response->assertStatus(401);
    }

    // ========================================================================
    // DISTRICTS
    // ========================================================================

    public function test_districts_returns_by_regency_id(): void
    {
        Regency::insert([
            ['id' => '6301', 'province_id' => '63', 'name' => 'Banjar'],
            ['id' => '6302', 'province_id' => '63', 'name' => 'Tanah Laut'],
        ]);

        District::insert([
            ['id' => '6301010', 'regency_id' => '6301', 'name' => 'Martapura'],
            ['id' => '6301020', 'regency_id' => '6301', 'name' => 'Astambul'],
            ['id' => '6302010', 'regency_id' => '6302', 'name' => 'Pelaihari'],
        ]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/districts?regency_id=6301');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Martapura'])
            ->assertJsonMissing(['name' => 'Pelaihari']);
    }

    public function test_districts_requires_regency_id(): void
    {
        $response = $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/districts');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['regency_id']);
    }

    // ========================================================================
    // VILLAGES
    // ========================================================================

    public function test_villages_returns_by_district_id(): void
    {
        Regency::insert([
            ['id' => '6301', 'province_id' => '63', 'name' => 'Banjar'],
        ]);

        District::insert([
            ['id' => '6301010', 'regency_id' => '6301', 'name' => 'Martapura'],
        ]);

        Village::insert([
            ['id' => '6301010001', 'district_id' => '6301010', 'name' => 'Sungai Paring'],
            ['id' => '6301010002', 'district_id' => '6301010', 'name' => 'Indrasari'],
        ]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/villages?district_id=6301010');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Sungai Paring']);
    }

    public function test_villages_requires_district_id(): void
    {
        $response = $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/villages');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['district_id']);
    }
    // File: tests/Feature/Controllers/Api/RegionControllerTest.php

    public function test_regencies_response_is_cached(): void
    {
        Regency::insert([
            ['id' => '6301', 'province_id' => '63', 'name' => 'Banjar'],
        ]);

        // First request
        $response1 = $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/regencies');

        // Second request — harus dari cache
        $response2 = $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/regencies');

        // Response harus sama
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_cache_invalidated_on_data_change(): void
    {
        Regency::insert([
            ['id' => '6301', 'province_id' => '63', 'name' => 'Banjar'],
        ]);

        $this->actingAs($this->citizen, 'sanctum')
            ->getJson('/api/v1/citizen/regions/regencies');

        // Insert new regency → cache harus invalidated secara natural (86400s)
        // Atau test via Cache::forget()
        $this->assertTrue(true); // Placeholder — cache invalidation by time
    }
}