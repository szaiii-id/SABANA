<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Admin;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AdminTest extends TestCase
{
    use RefreshDatabase;

    // ===== HAPPY PATH (9 test) =====

    public function test_admin_has_correct_fillable_attributes(): void
    {
        $admin = new Admin();
        $expected = [
            'nip', 'name', 'password', 'role',
            'regency_id', 'district_id', 'village_id',
            'is_active', 'last_login_at'
        ];

        $this->assertEqualsCanonicalizing($expected, $admin->getFillable());
    }

    public function test_admin_has_correct_hidden_attributes(): void
    {
        $admin = new Admin();
        $expected = ['password', 'remember_token'];

        $this->assertEqualsCanonicalizing($expected, $admin->getHidden());
    }

    public function test_admin_has_correct_casts(): void
    {
        $admin = Admin::query()->create([
            'nip' => '123456789012345678',
            'name' => 'Test Admin',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => 1,
            'last_login_at' => '2025-01-01 12:00:00',
        ]);

        $this->assertNotEquals('secret123', $admin->password);
        $this->assertTrue(password_verify('secret123', $admin->password));
        $this->assertTrue($admin->is_active);
        $this->assertIsBool($admin->is_active);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $admin->last_login_at);
        $this->assertEquals('2025-01-01 12:00:00', $admin->last_login_at->format('Y-m-d H:i:s'));
    }

    public function test_is_super_admin_returns_true(): void
    {
        $admin = Admin::query()->create([
            'nip' => '111111111111111111',
            'name' => 'Super',
            'password' => 'test',
            'role' => 'super_admin',
        ]);

        $this->assertTrue($admin->isSuperAdmin());
        $this->assertFalse($admin->isVillageOfficer());
    }

    public function test_is_regency_admin_returns_true(): void
    {
        $admin = Admin::query()->create([
            'nip' => '222222222222222222',
            'name' => 'Regency',
            'password' => 'test',
            'role' => 'regency_admin',
        ]);

        $this->assertTrue($admin->isRegencyAdmin());
    }

    public function test_is_district_admin_returns_true(): void
    {
        $admin = Admin::query()->create([
            'nip' => '333333333333333333',
            'name' => 'District',
            'password' => 'test',
            'role' => 'district_admin',
        ]);

        $this->assertTrue($admin->isDistrictAdmin());
    }

    public function test_is_village_officer_returns_true(): void
    {
        $admin = Admin::query()->create([
            'nip' => '444444444444444444',
            'name' => 'Village',
            'password' => 'test',
            'role' => 'village_officer',
        ]);

        $this->assertTrue($admin->isVillageOfficer());
    }

    public function test_admin_creates_with_uuid_primary_key(): void
    {
        $admin = Admin::query()->create([
            'nip' => '555555555555555555',
            'name' => 'UUID Test',
            'password' => 'test',
            'role' => 'super_admin',
        ]);

        $this->assertNotEmpty($admin->id);
        $this->assertIsString($admin->id);
        $this->assertEquals(36, strlen($admin->id));
    }

    public function test_admin_can_have_verifications_relation(): void
    {
        $admin = Admin::query()->create([
            'nip' => '666666666666666666',
            'name' => 'Verification',
            'password' => 'test',
            'role' => 'super_admin',
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Collection::class,
            $admin->verifications
        );
    }

    // ===== SAD PATH (2 test) =====

    public function test_role_checker_returns_false_for_unknown_role(): void
    {
        $admin = Admin::query()->create([
            'nip' => '777777777777777777',
            'name' => 'Unknown',
            'password' => 'test',
            'role' => 'village_officer',
        ]);

        $admin->setAttribute('role', 'unknown_role');

        $this->assertFalse($admin->isSuperAdmin());
        $this->assertFalse($admin->isRegencyAdmin());
    }

    public function test_admin_duplicate_nip_throws_error(): void
    {
        Admin::query()->create([
            'nip' => '123456789012345678',
            'name' => 'First',
            'password' => 'test',
            'role' => 'super_admin',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Admin::query()->create([
            'nip' => '123456789012345678',
            'name' => 'Second',
            'password' => 'test',
            'role' => 'super_admin',
        ]);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_nip_max_18_chars(): void
    {
        $admin = Admin::query()->create([
            'nip' => str_repeat('1', 18),
            'name' => 'Boundary',
            'password' => 'test',
            'role' => 'super_admin',
        ]);

        $this->assertEquals(18, strlen($admin->nip));
    }

    public function test_role_default_is_village_officer_from_database(): void
    {
        DB::table('admins')->insert([
            'id' => (string) Str::uuid(),
            'nip' => '999999999999999999',
            'name' => 'Default Role',
            'password' => bcrypt('test'),
        ]);

        $admin = Admin::query()->where('nip', '999999999999999999')->first();

        $this->assertNotNull($admin);
        $this->assertEquals('village_officer', $admin->role);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_admin_with_null_region_ids(): void
    {
        $admin = Admin::query()->create([
            'nip' => '101010101010101010',
            'name' => 'No Region',
            'password' => 'test',
            'role' => 'regency_admin',
            'regency_id' => null,
            'district_id' => null,
            'village_id' => null,
        ]);

        $this->assertNull($admin->regency_id);
        $this->assertTrue($admin->isRegencyAdmin());
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_admin_requires_nip_and_name(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Admin::query()->create([
            'nip' => null,
            'name' => null,
            'password' => 'test',
            'role' => 'super_admin',
        ]);
    }

    // ===== DATA TYPE (2 test) =====

    public function test_is_active_is_boolean_after_create(): void
    {
        $admin = Admin::query()->create([
            'nip' => '121212121212121212',
            'name' => 'Boolean',
            'password' => 'test',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->assertTrue($admin->is_active);
        $this->assertIsBool($admin->is_active);
    }

    public function test_last_login_at_is_carbon_instance(): void
    {
        $admin = Admin::query()->create([
            'nip' => '131313131313131313',
            'name' => 'Carbon',
            'password' => 'test',
            'role' => 'super_admin',
            'last_login_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $admin->last_login_at);
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_all_valid_roles_can_be_created(): void
    {
        $roles = ['super_admin', 'regency_admin', 'district_admin', 'village_officer'];

        foreach ($roles as $index => $role) {
            $admin = Admin::query()->create([
                'nip' => '14' . str_pad((string) $index, 16, '1', STR_PAD_LEFT),
                'name' => "Admin $role",
                'password' => 'test',
                'role' => $role,
            ]);

            $this->assertEquals($role, $admin->role);
        }
    }

    public function test_scope_active_filters_correctly(): void
    {
        Admin::query()->create([
            'nip' => '151515151515151515',
            'name' => 'Active',
            'password' => 'test',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Admin::query()->create([
            'nip' => '161616161616161616',
            'name' => 'Inactive',
            'password' => 'test',
            'role' => 'super_admin',
            'is_active' => false,
        ]);

        $activeCount = Admin::query()->active()->count();
        $this->assertEquals(1, $activeCount);
    }

    // ===== STATE TRANSITION (2 test) =====

    public function test_admin_can_be_activated(): void
    {
        $admin = Admin::query()->create([
            'nip' => '171717171717171717',
            'name' => 'Activate',
            'password' => 'test',
            'role' => 'super_admin',
            'is_active' => false,
        ]);

        $admin->update(['is_active' => true]);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_be_deactivated(): void
    {
        $admin = Admin::query()->create([
            'nip' => '181818181818181818',
            'name' => 'Deactivate',
            'password' => 'test',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $admin->update(['is_active' => false]);

        $this->assertFalse($admin->fresh()->is_active);
    }

    // ===== CONCURRENCY — Tidak berlaku =====

    // ===== SECURITY (3 test) =====

    public function test_password_is_hidden_from_to_array(): void
    {
        $admin = Admin::query()->create([
            'nip' => '191919191919191919',
            'name' => 'Hidden',
            'password' => 'secret123',
            'role' => 'super_admin',
        ]);

        $array = $admin->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_password_is_hashed_not_plain_text(): void
    {
        $admin = Admin::query()->create([
            'nip' => '202020202020202020',
            'name' => 'Hashed',
            'password' => 'secret123',
            'role' => 'super_admin',
        ]);

        $this->assertNotEquals('secret123', $admin->password);
        $this->assertTrue(password_verify('secret123', $admin->password));
    }

    public function test_mass_assignment_does_not_override_id(): void
    {
        $admin = Admin::query()->create([
            'nip' => '212121212121212121',
            'name' => 'ID Protect',
            'password' => 'test',
            'role' => 'super_admin',
        ]);

        $originalId = $admin->id;
        $admin->fill(['id' => 'fake-uuid-attack']);

        $this->assertEquals($originalId, $admin->id);
    }

    // ===== ISOLASI LAYER — Relasi BelongsTo (3 test) =====

    public function test_admin_belongs_to_regency(): void
    {
        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalimantan Selatan']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Tanah Laut']);

        $admin = Admin::query()->create([
            'nip' => '222222222222222221',
            'name' => 'Regency Admin',
            'password' => 'test',
            'role' => 'regency_admin',
            'regency_id' => '6301',
        ]);

        $this->assertInstanceOf(Regency::class, $admin->regency);
        $this->assertEquals('6301', $admin->regency->id);
    }

    public function test_admin_belongs_to_district(): void
    {
        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalimantan Selatan']);
        DB::table('regencies')->insert(['id' => '6302', 'province_id' => '63', 'name' => 'Banjar']);
        DB::table('districts')->insert(['id' => '6302010', 'regency_id' => '6302', 'name' => 'Martapura']);

        $admin = Admin::query()->create([
            'nip' => '222222222222222222',
            'name' => 'District Admin',
            'password' => 'test',
            'role' => 'district_admin',
            'regency_id' => '6302',
            'district_id' => '6302010',
        ]);

        $this->assertInstanceOf(District::class, $admin->district);
        $this->assertEquals('6302010', $admin->district->id);
    }

    public function test_admin_belongs_to_village(): void
    {
        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalimantan Selatan']);
        DB::table('regencies')->insert(['id' => '6303', 'province_id' => '63', 'name' => 'Tapin']);
        DB::table('districts')->insert(['id' => '6303010', 'regency_id' => '6303', 'name' => 'Binuang']);
        DB::table('villages')->insert(['id' => '6303010001', 'district_id' => '6303010', 'name' => 'Binuang']);

        $admin = Admin::query()->create([
            'nip' => '222222222222222223',
            'name' => 'Village Officer',
            'password' => 'test',
            'role' => 'village_officer',
            'regency_id' => '6303',
            'district_id' => '6303010',
            'village_id' => '6303010001',
        ]);

        $this->assertInstanceOf(Village::class, $admin->village);
        $this->assertEquals('6303010001', $admin->village->id);
    }
}