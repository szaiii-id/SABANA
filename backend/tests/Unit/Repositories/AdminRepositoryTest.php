<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Admin;
use App\Repositories\AdminRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AdminRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AdminRepository();
    }

    // ===== HELPER =====

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nip' => '123456789012345678',
            'name' => 'Test Admin',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ], $overrides);
    }

    private function createAdmin(array $overrides = []): Admin
    {
        return Admin::query()->create($this->validData($overrides));
    }

    // ===== HAPPY PATH (7 test) =====

    public function test_find_by_nip_returns_admin(): void
    {
        $created = $this->createAdmin(['nip' => '111111111111111111']);

        $found = $this->repository->findByNip('111111111111111111');

        $this->assertInstanceOf(Admin::class, $found);
        $this->assertEquals($created->id, $found->id);
    }

    public function test_find_by_nip_returns_null_for_unknown(): void
    {
        $found = $this->repository->findByNip('000000000000000000');

        $this->assertNull($found);
    }

    public function test_update_last_login_sets_timestamp(): void
    {
        $admin = $this->createAdmin();
        $before = now()->subMinute();

        $this->repository->updateLastLogin($admin->id);

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
        $this->assertTrue($admin->last_login_at->gt($before));
    }

    public function test_create_returns_admin(): void
    {
        $data = $this->validData(['nip' => '222222222222222222']);

        $admin = $this->repository->create($data);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertDatabaseHas('admins', ['nip' => '222222222222222222']);
    }

    public function test_find_by_id_returns_admin(): void
    {
        $created = $this->createAdmin();

        $found = $this->repository->findById($created->id);

        $this->assertInstanceOf(Admin::class, $found);
        $this->assertEquals($created->id, $found->id);
    }

    public function test_update_returns_true(): void
    {
        $admin = $this->createAdmin();

        $result = $this->repository->update($admin, ['name' => 'Updated Name']);

        $this->assertTrue($result);
        $this->assertEquals('Updated Name', $admin->fresh()->name);
    }

    public function test_delete_soft_deletes_admin(): void
    {
        $admin = $this->createAdmin();

        $result = $this->repository->delete($admin);

        $this->assertTrue($result);
        $this->assertSoftDeleted('admins', ['id' => $admin->id]);
    }

    // ===== SAD PATH (3 test) =====

    public function test_find_by_id_returns_null_for_unknown(): void
    {
        $found = $this->repository->findById('00000000-0000-0000-0000-000000000000');

        $this->assertNull($found);
    }

    public function test_find_by_id_returns_trashed_admin(): void
    {
        $admin = $this->createAdmin();
        $admin->delete();

        $found = $this->repository->findById($admin->id);

        $this->assertInstanceOf(Admin::class, $found);
        $this->assertTrue($found->trashed());
    }

    public function test_find_by_nip_does_not_find_trashed_admin(): void
    {
        $admin = $this->createAdmin(['nip' => '333333333333333333']);
        $admin->delete();

        $found = $this->repository->findByNip('333333333333333333');

        // ⚠️ Konfirmasi bug: findByNip tidak pakai withTrashed()
        $this->assertNull($found);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_get_hierarchical_admins_with_empty_filters(): void
    {
        $superAdmin = $this->createAdmin(['role' => 'super_admin', 'nip' => '100000000000000001']);
        $this->createAdmin(['role' => 'village_officer', 'nip' => '100000000000000002']);

        $result = $this->repository->getHierarchicalAdmins($superAdmin, [], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
    }

    public function test_get_hierarchical_admins_with_search_filter(): void
    {
        $superAdmin = $this->createAdmin(['role' => 'super_admin', 'nip' => '100000000000000003']);
        $this->createAdmin(['role' => 'village_officer', 'nip' => '100000000000000004', 'name' => 'Budi']);

        $result = $this->repository->getHierarchicalAdmins($superAdmin, ['search' => 'Budi'], 10);

        $this->assertEquals(1, $result->total());
    }

    // ===== EDGE CASE (1 test) =====

    public function test_restore_trashed_admin(): void
    {
        $admin = $this->createAdmin();
        $admin->delete();

        $result = $this->repository->restore($admin);

        $this->assertTrue($result);
        $this->assertDatabaseHas('admins', ['id' => $admin->id, 'deleted_at' => null]);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_find_by_nip_with_empty_string(): void
    {
        $found = $this->repository->findByNip('');

        $this->assertNull($found);
    }

    public function test_get_hierarchical_admins_with_null_region_actor(): void
    {
        $this->createAdmin(['role' => 'village_officer', 'nip' => '100000000000000005']);

        // Actor regency_admin tanpa wilayah
        $actor = new Admin([
            'id' => '00000000-0000-0000-0000-000000000000',
            'role' => 'regency_admin',
            'regency_id' => null,
            'name' => 'Actor',
            'nip' => '000000000000000000',
            'password' => bcrypt('test'),
            'is_active' => true,
        ]);

        $result = $this->repository->getHierarchicalAdmins($actor, [], 10);

        // Actor dengan null regency_id akan filter where('regency_id', null)
        // Tapi query pakai where('role', '!=', 'super_admin') dulu
        // Admin village_officer tetap muncul karena role != super_admin
        // Jadi total = 1, bukan 0
        $this->assertEquals(1, $result->total());
    }

    // ===== DATA TYPE (2 test) =====

    public function test_update_last_login_with_string_id(): void
    {
        $admin = $this->createAdmin();

        $this->repository->updateLastLogin($admin->id);

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
    }

    public function test_get_hierarchical_admins_returns_paginator(): void
    {
        $actor = $this->createAdmin(['role' => 'super_admin']);

        $result = $this->repository->getHierarchicalAdmins($actor, [], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_regency_admin_sees_subordinates_only(): void
    {
        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalsel']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Tanah Laut']);

        $actor = $this->createAdmin([
            'role' => 'regency_admin',
            'regency_id' => '6301',
            'nip' => '100000000000000006',
        ]);

        $this->createAdmin([
            'role' => 'district_admin',
            'regency_id' => '6301',
            'nip' => '100000000000000007',
        ]);

        $this->createAdmin([
            'role' => 'district_admin',
            'regency_id' => null,
            'nip' => '100000000000000008',
        ]);

        $result = $this->repository->getHierarchicalAdmins($actor, [], 10);

        $this->assertEquals(1, $result->total());
    }

    public function test_filter_by_role_returns_only_matching(): void
    {
        $actor = $this->createAdmin(['role' => 'super_admin', 'nip' => '100000000000000009']);
        $this->createAdmin(['role' => 'village_officer', 'nip' => '100000000000000010']);
        $this->createAdmin(['role' => 'district_admin', 'nip' => '100000000000000011']);

        $result = $this->repository->getHierarchicalAdmins($actor, ['role' => 'village_officer'], 10);

        $this->assertEquals(1, $result->total());
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_delete_then_restore_cycle(): void
    {
        $admin = $this->createAdmin();

        $this->repository->delete($admin);
        $this->assertSoftDeleted('admins', ['id' => $admin->id]);

        $this->repository->restore($admin);
        $this->assertDatabaseHas('admins', ['id' => $admin->id, 'deleted_at' => null]);
    }

    // ===== CONCURRENCY — Tidak berlaku =====
    // Repository tidak ada locking. Concurrency di-handle di Service level.

    // ===== SECURITY (2 test) =====

   public function test_search_does_not_expose_password(): void
    {
        $actor = $this->createAdmin(['role' => 'super_admin', 'nip' => '100000000000000012']);
        
        // PASTIKAN ROLE BUKAN super_admin
        $uniqueName = 'targetadmin' . uniqid();
        $this->createAdmin([
            'name' => $uniqueName,
            'nip' => '100000000000000013',
            'role' => 'village_officer', // ← INI YANG SALAH SEBELUMNYA
        ]);

        $result = $this->repository->getHierarchicalAdmins($actor, ['search' => $uniqueName], 10);

        $this->assertGreaterThan(0, $result->total(), 'Search harus menemukan admin');
        
        $adminArray = $result->first()->toArray();
        $this->assertArrayNotHasKey('password', $adminArray);
    }

    public function test_find_by_nip_is_case_sensitive(): void
    {
        $this->createAdmin(['nip' => 'ABC123456789012345']);

        $found = $this->repository->findByNip('abc123456789012345');

        $this->assertNull($found);
    }
}