<?php

namespace Tests\Unit\Http\Resources;

use Tests\TestCase;
use App\Models\Citizen;
use App\Http\Resources\CitizenResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class CitizenResourceTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // KEYS
    // =============================================

    public function test_resource_has_required_keys()
    {
        $citizen = Citizen::factory()->create([
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Muhammad Noor',
            'whatsapp_number'    => '6281234567890',
            'is_verified'        => false,
            'last_login_at'      => null,
        ]);

        $resource = new CitizenResource($citizen);
        $response = $resource->toArray(new Request());

        $this->assertArrayHasKey('nik', $response);
        $this->assertArrayHasKey('family_card_number', $response);
        $this->assertArrayHasKey('full_name', $response);
        $this->assertArrayHasKey('whatsapp_number', $response);
        $this->assertArrayHasKey('is_verified', $response);
        $this->assertArrayHasKey('last_login', $response);
    }

    // =============================================
    // DATA TYPES
    // =============================================

    public function test_resource_returns_correct_values()
    {
        $citizen = Citizen::factory()->create([
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Muhammad Noor',
            'whatsapp_number'    => '6281234567890',
            'is_verified'        => false,
        ]);

        $resource = new CitizenResource($citizen);
        $response = $resource->toArray(new Request());

        $this->assertEquals('6301234567890123', $response['nik']);
        $this->assertEquals('Muhammad Noor', $response['full_name']);
        $this->assertEquals('6281234567890', $response['whatsapp_number']);
        $this->assertFalse($response['is_verified']);
    }

    // =============================================
    // LAST LOGIN
    // =============================================

    public function test_resource_returns_null_when_last_login_at_is_null()
    {
        $citizen = Citizen::factory()->create([
            'last_login_at' => null,
        ]);

        $resource = new CitizenResource($citizen);
        $response = $resource->toArray(new Request());

        $this->assertNull($response['last_login']);
    }

    public function test_resource_returns_formatted_date_when_last_login_at_is_set()
    {
        $citizen = Citizen::factory()->create([
            'last_login_at' => '2024-01-15 14:30:00',
        ]);

        $resource = new CitizenResource($citizen);
        $response = $resource->toArray(new Request());

        $this->assertNotNull($response['last_login']);
        $this->assertStringContainsString('2024', $response['last_login']);
    }

    // =============================================
    // HIDDEN FIELDS TIDAK MUNCUL
    // =============================================

    public function test_resource_does_not_expose_pin()
    {
        $citizen = Citizen::factory()->create([
            'pin' => bcrypt('123456'),
        ]);

        $resource = new CitizenResource($citizen);
        $response = $resource->toArray(new Request());

        $this->assertArrayNotHasKey('pin', $response);
        $this->assertArrayNotHasKey('temporary_pin', $response);
    }
}