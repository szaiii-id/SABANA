<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Account;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdminProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalsel']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Tanah Laut']);

        $this->admin = Admin::query()->create([
            'nip' => str_pad('admin01', 18, '0', STR_PAD_LEFT),
            'name' => 'Profile Admin',
            'password' => bcrypt('password123'),
            'role' => 'regency_admin',
            'regency_id' => '6301',
            'is_active' => true,
        ]);
    }

    private function baseUrl(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/profile";
    }

    private function actingAsAdmin(Admin $admin): void
    {
        Sanctum::actingAs($admin, ['admin'], 'admin-api');
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_show_returns_profile(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.name', 'Profile Admin');
        $response->assertJsonPath('data.role', 'regency_admin');
        $response->assertJsonPath('data.region.regency', 'Tanah Laut');
    }

    public function test_update_changes_name(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->putJson($this->baseUrl(), [
            'name' => 'Updated Profile',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Profil berhasil diperbarui.');
        $response->assertJsonPath('data.name', 'Updated Profile');
    }

    public function test_update_password_successful(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->patchJson("{$this->baseUrl()}/password", [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Password berhasil diubah.');
    }

    // ===== SAD PATH (2 test) =====

    public function test_update_without_name_returns_422(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->putJson($this->baseUrl(), []);

        $response->assertStatus(422);
    }

    public function test_update_password_wrong_current_returns_422(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->patchJson("{$this->baseUrl()}/password", [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_update_name_max_255_chars(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->putJson($this->baseUrl(), [
            'name' => str_repeat('A', 255),
        ]);

        $response->assertStatus(200);
    }

    public function test_update_password_min_8_chars(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->patchJson("{$this->baseUrl()}/password", [
            'current_password' => 'password123',
            'new_password' => '12345678',
        ]);

        $response->assertStatus(200);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_show_without_auth_returns_401(): void
    {
        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    public function test_update_password_without_current_returns_422(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->patchJson("{$this->baseUrl()}/password", [
            'new_password' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    // ===== SECURITY (2 test) =====

    public function test_show_never_exposes_password(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->getJson($this->baseUrl());

        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    public function test_update_never_exposes_password(): void
    {
        $this->actingAsAdmin($this->admin);

        $response = $this->putJson($this->baseUrl(), ['name' => 'Safe']);

        $this->assertArrayNotHasKey('password', $response->json('data'));
    }
}