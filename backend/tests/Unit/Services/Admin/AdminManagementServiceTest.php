<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Models\Admin;
use App\Services\Admin\AdminManagementService;
use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class AdminManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminRepositoryInterface $adminRepository;
    private AdminManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRepository = Mockery::mock(AdminRepositoryInterface::class);
        $this->service = new AdminManagementService($this->adminRepository);
        
        DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalimantan Selatan']);
        DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Tanah Laut']);
        DB::table('districts')->insert(['id' => '6301010', 'regency_id' => '6301', 'name' => 'Pelaihari']);
        DB::table('villages')->insert(['id' => '6301010001', 'district_id' => '6301010', 'name' => 'Angsau']);
    }

    // ===== HELPER =====

    private function makeNip(string $prefix): string
    {
        return substr($prefix . uniqid(), 0, 18);
    }

    private function createActor(string $role = 'super_admin', ?string $regencyId = null, ?string $districtId = null): Admin
    {
        return Admin::query()->create([
            'nip' => $this->makeNip('actor'),
            'name' => 'Actor Admin',
            'password' => bcrypt('password'),
            'role' => $role,
            'regency_id' => $regencyId,
            'district_id' => $districtId,
            'is_active' => true,
        ]);
    }

    private function createTarget(array $overrides = []): Admin
    {
        return Admin::query()->create(array_merge([
            'nip' => $this->makeNip('target'),
            'name' => 'Target Admin',
            'password' => bcrypt('password'),
            'role' => 'village_officer',
            'is_active' => true,
        ], $overrides));
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nip' => $this->makeNip('newadmin'),
            'name' => 'New Admin',
            'password' => 'password123',
            'role' => 'village_officer',
        ], $overrides);
    }

    // ===== HAPPY PATH (7 test) =====

    public function test_create_admin_successfully(): void
    {
        $actor = $this->createActor('super_admin');
        $data = $this->validData();

        $this->adminRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (array $d) {
                return Admin::query()->create($d);
            });

        $admin = $this->service->createAdmin($actor, $data);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertNotEquals('password123', $admin->password);
    }

    public function test_update_admin_successfully(): void
    {
        $actor = $this->createActor('super_admin');
        $target = $this->createTarget();

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($target->id)
            ->once()
            ->andReturn($target);

        $this->adminRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $result = $this->service->updateAdmin($actor, $target->id, ['name' => 'Updated']);

        $this->assertInstanceOf(Admin::class, $result);
    }

    public function test_delete_admin_successfully(): void
    {
        $actor = $this->createActor('super_admin');
        $target = $this->createTarget();

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($target->id)
            ->once()
            ->andReturn($target);

        $this->adminRepository
            ->shouldReceive('update')
            ->with($target, ['is_active' => false])
            ->once();

        $this->adminRepository
            ->shouldReceive('delete')
            ->with($target)
            ->once();

        $this->service->deleteAdmin($actor, $target->id);

        $this->assertTrue(true);
    }

    public function test_activate_admin_successfully(): void
    {
        $actor = $this->createActor('super_admin');
        $target = $this->createTarget(['is_active' => false]);

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($target->id)
            ->once()
            ->andReturn($target);

        $this->adminRepository
            ->shouldReceive('update')
            ->with($target, ['is_active' => true])
            ->once();

        $this->adminRepository
            ->shouldReceive('restore')
            ->never();

        $result = $this->service->activateAdmin($actor, $target->id);

        $this->assertInstanceOf(Admin::class, $result);
    }

    public function test_activate_admin_with_trashed_target_restores(): void
    {
        $actor = $this->createActor('super_admin');
        $target = $this->createTarget(['is_active' => false]);
        $target->delete();

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($target->id)
            ->once()
            ->andReturn($target);

        $this->adminRepository
            ->shouldReceive('update')
            ->with($target, ['is_active' => true])
            ->once();

        $this->adminRepository
            ->shouldReceive('restore')
            ->with($target)
            ->once();

        $result = $this->service->activateAdmin($actor, $target->id);

        $this->assertInstanceOf(Admin::class, $result);
    }

    public function test_reset_password_successfully(): void
    {
        $actor = $this->createActor('super_admin');
        $target = $this->createTarget();

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($target->id)
            ->once()
            ->andReturn($target);

        $this->adminRepository
            ->shouldReceive('update')
            ->once();

        $this->service->resetPassword($actor, $target->id, 'newpassword123');

        $this->assertTrue(true);
    }

    public function test_get_list_delegates_to_repository(): void
    {
        $actor = $this->createActor('super_admin');
        $filters = ['search' => 'test'];

        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->adminRepository
            ->shouldReceive('getHierarchicalAdmins')
            ->with($actor, $filters)
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getList($actor, $filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    // ===== SAD PATH (6 test) =====

    public function test_update_admin_not_found(): void
    {
        $actor = $this->createActor('super_admin');

        $this->adminRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->service->updateAdmin($actor, 'nonexistent', ['name' => 'Test']);
    }

    public function test_delete_admin_not_found(): void
    {
        $actor = $this->createActor('super_admin');

        $this->adminRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->service->deleteAdmin($actor, 'nonexistent');
    }

    public function test_delete_self_throws_error(): void
    {
        $actor = $this->createActor('super_admin');

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($actor->id)
            ->once()
            ->andReturn($actor);

        $this->expectException(BadRequestHttpException::class);

        $this->service->deleteAdmin($actor, $actor->id);
    }

    public function test_activate_admin_not_found(): void
    {
        $actor = $this->createActor('super_admin');

        $this->adminRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->service->activateAdmin($actor, 'nonexistent');
    }

    public function test_create_admin_with_insufficient_permission(): void
    {
        $actor = $this->createActor('village_officer');
        $data = $this->validData(['role' => 'district_admin']);

        $this->expectException(AccessDeniedHttpException::class);

        $this->service->createAdmin($actor, $data);
    }

    public function test_update_admin_role_change_with_insufficient_permission(): void
    {
        $actor = $this->createActor('district_admin', '6301');
        $target = $this->createTarget(['role' => 'village_officer']);

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($target->id)
            ->once()
            ->andReturn($target);

        $this->expectException(AccessDeniedHttpException::class);

        $this->service->updateAdmin($actor, $target->id, ['role' => 'regency_admin']);
    }

    // ===== BOUNDARY (3 test) =====

    public function test_super_admin_can_create_regency_admin(): void
    {
        $actor = $this->createActor('super_admin');
        $data = $this->validData(['role' => 'regency_admin', 'regency_id' => '6301']);

        $this->adminRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (array $d) {
                return Admin::query()->create($d);
            });

        $admin = $this->service->createAdmin($actor, $data);

        $this->assertEquals('regency_admin', $admin->role);
    }

    public function test_regency_admin_auto_sets_regency_id(): void
    {
        $actor = $this->createActor('regency_admin', '6301');
        $data = $this->validData(['role' => 'village_officer']);

        $this->adminRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (array $d) {
                return Admin::query()->create($d);
            });

        $admin = $this->service->createAdmin($actor, $data);

        $this->assertEquals('6301', $admin->regency_id);
    }

    public function test_district_admin_auto_sets_district_id(): void
    {
        $actor = $this->createActor('district_admin', '6301', '6301010');
        $data = $this->validData(['role' => 'village_officer']);

        $this->adminRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (array $d) {
                return Admin::query()->create($d);
            });

        $admin = $this->service->createAdmin($actor, $data);

        $this->assertEquals('6301', $admin->regency_id);
        $this->assertEquals('6301010', $admin->district_id);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_create_admin_password_is_hashed(): void
    {
        $actor = $this->createActor('super_admin');
        $data = $this->validData(['password' => 'plainpassword']);

        $this->adminRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (array $d) {
                return Admin::query()->create($d);
            });

        $admin = $this->service->createAdmin($actor, $data);

        $this->assertNotEquals('plainpassword', $admin->password);
        $this->assertTrue(password_verify('plainpassword', $admin->password));
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_update_with_empty_password_is_unset(): void
    {
        $actor = $this->createActor('super_admin');
        $target = $this->createTarget();

        $this->adminRepository
            ->shouldReceive('findById')
            ->with($target->id)
            ->once()
            ->andReturn($target);

        $this->adminRepository
            ->shouldReceive('update')
            ->with($target, Mockery::on(function (array $data) {
                return !array_key_exists('password', $data);
            }))
            ->once()
            ->andReturn(true);

        $this->service->updateAdmin($actor, $target->id, ['password' => '']);

        $this->assertTrue(true);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_create_admin_returns_admin_instance(): void
    {
        $actor = $this->createActor('super_admin');

        $this->adminRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (array $d) {
                return Admin::query()->create($d);
            });

        $result = $this->service->createAdmin($actor, $this->validData());

        $this->assertInstanceOf(Admin::class, $result);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_permission_hierarchy_allowed_roles(): void
    {
        $allowed = [
            ['actor' => 'super_admin', 'target' => 'regency_admin'],
            ['actor' => 'super_admin', 'target' => 'district_admin'],
            ['actor' => 'super_admin', 'target' => 'village_officer'],
            ['actor' => 'regency_admin', 'target' => 'district_admin'],
            ['actor' => 'regency_admin', 'target' => 'village_officer'],
            ['actor' => 'district_admin', 'target' => 'village_officer'],
        ];

        foreach ($allowed as $test) {
            $actor = $this->createActor($test['actor'], '6301');

            $this->adminRepository
                ->shouldReceive('create')
                ->once()
                ->andReturnUsing(function (array $d) {
                    return Admin::query()->create($d);
                });

            $result = $this->service->createAdmin($actor, $this->validData(['role' => $test['target']]));
            $this->assertInstanceOf(Admin::class, $result);
        }
    }

    // ===== SECURITY (3 test) =====

    public function test_village_officer_cannot_create_any_admin(): void
    {
        $actor = $this->createActor('village_officer');

        $this->expectException(AccessDeniedHttpException::class);

        $this->service->createAdmin($actor, $this->validData(['role' => 'village_officer']));
    }

    public function test_password_not_returned_in_create_response(): void
    {
        $actor = $this->createActor('super_admin');

        $this->adminRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (array $d) {
                return Admin::query()->create($d);
            });

        $admin = $this->service->createAdmin($actor, $this->validData());

        $array = $admin->toArray();
        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_super_admin_cannot_manage_super_admin(): void
    {
        $actor = $this->createActor('super_admin');

        $this->expectException(AccessDeniedHttpException::class);

        $this->service->createAdmin($actor, $this->validData(['role' => 'super_admin']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}