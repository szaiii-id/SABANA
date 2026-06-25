<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Account;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdminManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $regencyAdmin;
    private Admin $villageOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalsel']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Tanah Laut']);
        DB::table('districts')->insert(['id' => '6301010', 'regency_id' => '6301', 'name' => 'Pelaihari']);
        DB::table('villages')->insert(['id' => '6301010001', 'district_id' => '6301010', 'name' => 'Angsau']);

        $this->superAdmin = $this->createAdmin('super_admin', 'sup001');
        $this->regencyAdmin = $this->createAdmin('regency_admin', 'reg001', '6301');
        $this->villageOfficer = $this->createAdmin('village_officer', 'vil001', '6301', '6301010', '6301010001');
    }

    private function createAdmin(string $role, string $nip, ?string $regencyId = null, ?string $districtId = null, ?string $villageId = null): Admin
    {
        return Admin::query()->create([
            'nip' => str_pad($nip, 18, '0', STR_PAD_LEFT),
            'name' => "Admin {$role}",
            'password' => bcrypt('password'),
            'role' => $role,
            'regency_id' => $regencyId,
            'district_id' => $districtId,
            'village_id' => $villageId,
            'is_active' => true,
        ]);
    }

    private function baseUrl(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/accounts";
    }

    private function actingAsAdmin(Admin $admin): void
    {
        Sanctum::actingAs($admin, ['admin'], 'admin-api');
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_index_returns_paginated_admins(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'data',
            'current_page',
            'total',
            'last_page',
            'per_page',
        ]);
    }

    public function test_store_creates_new_admin(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => '999999999999999999',
            'name' => 'New Village Officer',
            'password' => 'password123',
            'role' => 'village_officer',
            'village_id' => '6301010001',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('message', 'Akun admin berhasil dibuat.');
        $response->assertJsonPath('data.role', 'village_officer');
    }

    public function test_update_modifies_admin(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->putJson("{$this->baseUrl()}/{$this->villageOfficer->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Data admin berhasil diperbarui.');
    }

    public function test_destroy_deactivates_admin(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->deleteJson("{$this->baseUrl()}/{$this->villageOfficer->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Akun admin berhasil dinonaktifkan.');
    }

    public function test_activate_reactivates_admin(): void
    {
        $this->actingAsAdmin($this->superAdmin);
        $this->villageOfficer->update(['is_active' => false]);

        $response = $this->patchJson("{$this->baseUrl()}/{$this->villageOfficer->id}/activate");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Akun berhasil diaktifkan.');
    }

    // ===== SAD PATH (4 test) =====

    public function test_store_without_nip_returns_422(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'name' => 'No NIP',
            'password' => 'password123',
            'role' => 'village_officer',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_with_duplicate_nip_returns_422(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => str_pad('sup001', 18, '0', STR_PAD_LEFT),
            'name' => 'Duplicate',
            'password' => 'password123',
            'role' => 'village_officer',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_nonexistent_admin_returns_404(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->putJson("{$this->baseUrl()}/00000000-0000-0000-0000-000000000000", [
            'name' => 'Ghost',
        ]);

        $response->assertStatus(404);
    }

    public function test_village_officer_cannot_create_admin(): void
    {
        $this->actingAsAdmin($this->villageOfficer);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => '888888888888888888',
            'name' => 'Should Fail',
            'password' => 'password123',
            'role' => 'village_officer',
            'village_id' => '6301010001',
        ]);

        $response->assertStatus(403);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_store_with_nip_exactly_18_digits(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => str_repeat('1', 18),
            'name' => 'Boundary NIP',
            'password' => 'password123',
            'role' => 'village_officer',
            'village_id' => '6301010001',
        ]);

        $response->assertStatus(201);
    }

    public function test_store_with_password_exactly_8_chars(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => str_repeat('2', 18),
            'name' => 'Boundary Pass',
            'password' => '12345678',
            'role' => 'village_officer',
            'village_id' => '6301010001',
        ]);

        $response->assertStatus(201);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_store_with_empty_name_returns_422(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => str_repeat('3', 18),
            'name' => '',
            'password' => 'password123',
            'role' => 'village_officer',
        ]);

        $response->assertStatus(422);
    }

    public function test_index_without_auth_returns_401(): void
    {
        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    // ===== SECURITY (3 test) =====

    public function test_reset_password_requires_password_field(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->patchJson("{$this->baseUrl()}/{$this->villageOfficer->id}/reset-password", []);

        $response->assertStatus(422);
    }

    public function test_store_sanitizes_nip_from_special_chars(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => '1234-5678-9012-345-678',
            'name' => 'Sanitized NIP',
            'password' => 'password123',
            'role' => 'village_officer',
            'village_id' => '6301010001',
        ]);

        $response->assertStatus(201);
    }

    public function test_response_never_exposes_password(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->postJson($this->baseUrl(), [
            'nip' => str_repeat('4', 18),
            'name' => 'Hidden Pass',
            'password' => 'secret123',
            'role' => 'village_officer',
            'village_id' => '6301010001',
        ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    // ===== GAP COVERAGE (3 test) =====

    public function test_index_with_role_filter(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->getJson($this->baseUrl() . '?role=village_officer');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_index_with_is_active_filter(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->getJson($this->baseUrl() . '?is_active=1');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_reset_password_successful(): void
    {
        $this->actingAsAdmin($this->superAdmin);

        $response = $this->patchJson(
            "{$this->baseUrl()}/{$this->villageOfficer->id}/reset-password",
            ['password' => 'newpassword123']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Password berhasil direset.');
    }
}