<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CitizenTest extends TestCase
{
    use RefreshDatabase;

    // =============================================
    // UUID
    // =============================================

    public function test_citizen_uses_uuid_not_incrementing_id()
    {
        $citizen = Citizen::factory()->create();

        $this->assertNotNull($citizen->id);
        $this->assertFalse(is_numeric($citizen->id));
        $this->assertEquals(36, strlen($citizen->id)); // UUID format
    }

    // =============================================
    // HIDDEN ATTRIBUTES
    // =============================================

    public function test_pin_is_hidden_from_json()
    {
        $citizen = Citizen::factory()->create([
            'pin' => bcrypt('123456'),
        ]);

        $json = $citizen->toJson();

        $this->assertStringNotContainsString('pin', $json);
    }

    public function test_temporary_pin_is_hidden_from_json()
    {
        $citizen = Citizen::factory()->create([
            'temporary_pin' => bcrypt('654321'),
        ]);

        $json = $citizen->toJson();

        $this->assertStringNotContainsString('temporary_pin', $json);
    }

    // =============================================
    // CASTS
    // =============================================

    public function test_temporary_pin_expired_at_is_carbon_instance()
    {
        $citizen = Citizen::factory()->create([
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $this->assertInstanceOf(Carbon::class, $citizen->temporary_pin_expired_at);
    }

    public function test_last_login_at_is_carbon_instance_when_not_null()
    {
        $citizen = Citizen::factory()->create([
            'last_login_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $citizen->last_login_at);
    }

    public function test_last_login_at_is_null_by_default()
    {
        $citizen = Citizen::factory()->create();

        $this->assertNull($citizen->last_login_at);
    }

    // =============================================
    // RELATIONS
    // =============================================

    public function test_citizen_has_assistance_submissions_relation()
    {
        $citizen = Citizen::factory()->create();

        $this->assertIsArray($citizen->assistanceSubmissions->toArray());
    }
}