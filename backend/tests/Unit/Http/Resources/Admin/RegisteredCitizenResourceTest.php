<?php

namespace Tests\Unit\Http\Resources\Admin;

use Tests\TestCase;
use App\Http\Resources\Admin\RegisteredCitizenResource;
use App\Models\Citizen;
use Illuminate\Http\Request;

class RegisteredCitizenResourceTest extends TestCase
{
    private function makeCitizen(array $attributes = []): Citizen
    {
        $citizen = new Citizen(array_merge([
            'id'                 => 'uuid-test',
            'nik'                => '6301234567890123',
            'full_name'          => 'Joko Widodo',
            'family_card_number' => '6301234567890123',
            'whatsapp_number'    => '6281234567890',
            'is_verified'        => true,
            'pin'                => '$2y$10$hashedpinvalue',
            'temporary_pin'      => null,
            'temporary_pin_expired_at' => null,
            'last_login_at'      => now(),
            'created_at'         => now(),
        ], $attributes));

        return $citizen;
    }

    public function test_resource_has_correct_structure()
    {
        $citizen = $this->makeCitizen();
        $resource = new RegisteredCitizenResource($citizen);
        $array = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nik', $array);
        $this->assertArrayHasKey('full_name', $array);
        $this->assertArrayHasKey('family_card_number', $array);
        $this->assertArrayHasKey('whatsapp_number', $array);
        $this->assertArrayHasKey('is_verified', $array);
        $this->assertArrayHasKey('access_status', $array);
        $this->assertArrayHasKey('last_login_at', $array);
        $this->assertArrayHasKey('created_at', $array);
    }

    public function test_access_status_activated_when_pin_exists()
    {
        $citizen = $this->makeCitizen(['pin' => '$2y$10$hashedpinvalue']);
        $resource = new RegisteredCitizenResource($citizen);
        $array = $resource->toArray(new Request());

        $this->assertEquals('activated', $array['access_status']);
    }

    public function test_access_status_pin_active_when_temporary_pin_valid()
    {
        $citizen = $this->makeCitizen([
            'pin'                     => null,
            'temporary_pin'           => '$2y$10$hashedotp',
            'temporary_pin_expired_at' => now()->addMinutes(5),
        ]);
        $resource = new RegisteredCitizenResource($citizen);
        $array = $resource->toArray(new Request());

        $this->assertEquals('pin_active', $array['access_status']);
    }

    public function test_access_status_pin_expired_when_temporary_pin_expired()
    {
        $citizen = $this->makeCitizen([
            'pin'                     => null,
            'temporary_pin'           => '$2y$10$hashedotp',
            'temporary_pin_expired_at' => now()->subMinutes(5),
        ]);
        $resource = new RegisteredCitizenResource($citizen);
        $array = $resource->toArray(new Request());

        $this->assertEquals('pin_expired', $array['access_status']);
    }

    public function test_access_status_no_access_when_no_pin()
    {
        $citizen = $this->makeCitizen([
            'pin'                     => null,
            'temporary_pin'           => null,
            'temporary_pin_expired_at' => null,
        ]);
        $resource = new RegisteredCitizenResource($citizen);
        $array = $resource->toArray(new Request());

        $this->assertEquals('no_access', $array['access_status']);
    }

    public function test_last_login_null_returns_null()
    {
        $citizen = $this->makeCitizen(['last_login_at' => null]);
        $resource = new RegisteredCitizenResource($citizen);
        $array = $resource->toArray(new Request());

        $this->assertNull($array['last_login_at']);
    }
}