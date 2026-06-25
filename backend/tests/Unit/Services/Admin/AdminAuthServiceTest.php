<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Models\Admin;
use App\Services\Admin\AdminAuthService;
use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Tests\TestCase;

final class AdminAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminRepositoryInterface $adminRepository;
    private AdminAuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRepository = Mockery::mock(AdminRepositoryInterface::class);
        $this->authService = new AdminAuthService($this->adminRepository);
    }

    // ===== HELPER =====

    private function validCredentials(): array
    {
        return [
            'nip' => '123456789012345678',
            'password' => 'password123',
        ];
    }

    private function createAdmin(array $attributes = []): Admin
    {
        return Admin::query()->create(array_merge([
            'nip' => '123456789012345678',
            'name' => 'Test Admin',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ], $attributes));
    }

    // ===== HAPPY PATH (3 test) =====

    public function test_login_successful_returns_admin_and_token(): void
    {
        $admin = $this->createAdmin();
        $credentials = $this->validCredentials();

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->with($credentials['nip'])
            ->once()
            ->andReturn($admin);

        $this->adminRepository
            ->shouldReceive('updateLastLogin')
            ->with($admin->id)
            ->once();

        $result = $this->authService->login($credentials);

        $this->assertArrayHasKey('admin', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertInstanceOf(Admin::class, $result['admin']);
        $this->assertEquals($admin->id, $result['admin']->id);
        $this->assertIsString($result['token']);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_updates_last_login_timestamp(): void
    {
        $admin = $this->createAdmin();
        $credentials = $this->validCredentials();

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        $this->adminRepository
            ->shouldReceive('updateLastLogin')
            ->with($admin->id)
            ->once();

        $this->authService->login($credentials);

        // updateLastLogin dipanggil — verified oleh Mockery shouldReceive
        $this->assertTrue(true);
    }

    public function test_logout_deletes_current_token(): void
    {
        $admin = $this->createAdmin();
        $admin->createToken('AdminToken', ['admin'], now()->addHours(12));

        // Ambil token dari database, bukan dari currentAccessToken()
        $tokenId = $admin->tokens()->first()->id;
        $this->assertNotNull($tokenId, 'Token harus terbuat di database');

        $this->authService->logout($admin);

        // Token harus terhapus dari database
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    // ===== SAD PATH (3 test) =====

    public function test_login_throws_exception_for_invalid_credentials(): void
    {
        $this->adminRepository
            ->shouldReceive('findByNip')
            ->with('123456789012345678')
            ->once()
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kredensial tidak cocok.');

        $this->authService->login($this->validCredentials());
    }

    public function test_login_throws_exception_for_trashed_admin(): void
    {
        $admin = $this->createAdmin();
        $admin->delete();

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun Anda telah dinonaktifkan.');

        $this->authService->login($this->validCredentials());
    }

    public function test_login_throws_exception_for_inactive_admin(): void
    {
        $admin = $this->createAdmin(['is_active' => false]);

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Akun Anda dinonaktifkan.');

        $this->authService->login($this->validCredentials());
    }

    // ===== BOUNDARY (2 test) =====

    public function test_login_with_18_digit_nip(): void
    {
        $nip = str_repeat('1', 18);
        $admin = $this->createAdmin(['nip' => $nip]);

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->with($nip)
            ->once()
            ->andReturn($admin);

        $this->adminRepository
            ->shouldReceive('updateLastLogin')
            ->with($admin->id)
            ->once();

        $result = $this->authService->login(['nip' => $nip, 'password' => 'password123']);

        $this->assertArrayHasKey('token', $result);
    }

    public function test_login_with_short_nip(): void
    {
        $admin = $this->createAdmin(['nip' => '12']);

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->with('12')
            ->once()
            ->andReturn($admin);

        $this->adminRepository
            ->shouldReceive('updateLastLogin')
            ->with($admin->id)
            ->once();

        $result = $this->authService->login(['nip' => '12', 'password' => 'password123']);

        $this->assertArrayHasKey('token', $result);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_logout_when_token_already_expired(): void
    {
        $admin = $this->createAdmin();
        // Tidak buat token — currentAccessToken() akan null

        // Tidak boleh throw exception
        $this->authService->logout($admin);

        $this->assertTrue(true);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_login_with_empty_nip(): void
    {
        $this->adminRepository
            ->shouldReceive('findByNip')
            ->with('')
            ->once()
            ->andReturn(null);

        $this->expectException(ValidationException::class);

        $this->authService->login(['nip' => '', 'password' => 'password123']);
    }

    public function test_login_with_wrong_password(): void
    {
        $admin = $this->createAdmin();

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Kredensial tidak cocok.');

        $this->authService->login(['nip' => '123456789012345678', 'password' => 'wrongpassword']);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_login_returns_string_token(): void
    {
        $admin = $this->createAdmin();

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        $this->adminRepository
            ->shouldReceive('updateLastLogin')
            ->with($admin->id)
            ->once();

        $result = $this->authService->login($this->validCredentials());

        $this->assertIsString($result['token']);
        $this->assertStringContainsString('|', $result['token']); // Sanctum format: id|token
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_login_works_for_all_admin_roles(): void
    {
        $roles = ['super_admin', 'regency_admin', 'district_admin', 'village_officer'];

        foreach ($roles as $index => $role) {
            // NIP harus ≤ 18 karakter
            $nip = str_pad((string) ($index + 1), 18, '0', STR_PAD_LEFT);
            $admin = $this->createAdmin(['role' => $role, 'nip' => $nip]);

            $this->adminRepository
                ->shouldReceive('findByNip')
                ->with($nip)
                ->once()
                ->andReturn($admin);

            $this->adminRepository
                ->shouldReceive('updateLastLogin')
                ->once();

            $result = $this->authService->login(['nip' => $nip, 'password' => 'password123']);

            $this->assertEquals($admin->id, $result['admin']->id);
        }
    }

    public function test_login_fails_for_deleted_admin(): void
    {
        $admin = $this->createAdmin();
        $admin->delete();

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        $this->expectException(ValidationException::class);

        $this->authService->login($this->validCredentials());
    }

    // ===== STATE TRANSITION — Tidak berlaku =====

    // ===== CONCURRENCY — Tidak berlaku =====

    // ===== SECURITY (3 test) =====

    public function test_login_does_not_reveal_valid_nip_on_failure(): void
    {
        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn(null);

        try {
            $this->authService->login($this->validCredentials());
        } catch (ValidationException $e) {
            $message = $e->errors()['nip'][0];
            $this->assertStringNotContainsString('tidak ditemukan', strtolower($message));
            $this->assertStringNotContainsString('tidak terdaftar', strtolower($message));
        }
    }

    public function test_login_same_message_for_wrong_nip_and_wrong_password(): void
    {
        // Wrong NIP
        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn(null);

        try {
            $this->authService->login($this->validCredentials());
        } catch (ValidationException $e) {
            $msgNip = $e->errors()['nip'][0];
        }

        // Wrong password (admin exists)
        $admin = $this->createAdmin();
        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        try {
            $this->authService->login(['nip' => '123456789012345678', 'password' => 'wrong']);
        } catch (ValidationException $e) {
            $msgPw = $e->errors()['nip'][0];
        }

        $this->assertEquals($msgNip, $msgPw, 'Pesan error harus sama');
    }

    public function test_token_expires_after_12_hours(): void
    {
        $admin = $this->createAdmin();

        $this->adminRepository
            ->shouldReceive('findByNip')
            ->once()
            ->andReturn($admin);

        $this->adminRepository
            ->shouldReceive('updateLastLogin')
            ->once();

        $result = $this->authService->login($this->validCredentials());

        $tokenParts = explode('|', $result['token']);
        $tokenId = $tokenParts[0];

        $tokenModel = PersonalAccessToken::find($tokenId);
        $this->assertNotNull($tokenModel);
        $this->assertNotNull($tokenModel->expires_at);

        $expectedExpiry = now()->addHours(12);
        $diffSeconds = $tokenModel->expires_at->diffInSeconds($expectedExpiry);

        $this->assertLessThan(5, $diffSeconds, 'Expiry should be ~12 hours from now');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}