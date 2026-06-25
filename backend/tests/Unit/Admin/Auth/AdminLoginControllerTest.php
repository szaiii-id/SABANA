<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class AdminLoginControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nip' => '123456789012345678',
            'password' => 'password123',
        ], $overrides);
    }

    private function createAdmin(array $overrides = []): Admin
    {
        return Admin::query()->create(array_merge([
            'nip' => '123456789012345678',
            'name' => 'Test Admin',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    private function loginPath(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/gate/verify-nip";
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Reset rate limiter sebelum setiap test
        RateLimiter::clear('123456789012345678|127.0.0.1');
    }

    // ===== HAPPY PATH (4 test) =====

    public function test_login_successful_returns_200_with_admin_and_token(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginPath(), $this->validData());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('message', 'Login berhasil. Selamat bertugas.');
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'admin' => ['id', 'nip', 'name', 'role', 'is_active'],
                'token',
            ],
        ]);
    }

    public function test_login_returns_admin_resource_with_correct_fields(): void
    {
        $admin = $this->createAdmin();

        $response = $this->postJson($this->loginPath(), $this->validData());

        $response->assertStatus(200);
        $response->assertJsonPath('data.admin.id', $admin->id);
        $response->assertJsonPath('data.admin.nip', '123456789012345678');
        $response->assertJsonPath('data.admin.name', 'Test Admin');
        $response->assertJsonPath('data.admin.role', 'super_admin');
    }

    public function test_login_token_is_string_with_pipe_format(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginPath(), $this->validData());

        $response->assertStatus(200);
        $token = $response->json('data.token');
        $this->assertIsString($token);
        $this->assertStringContainsString('|', $token);
    }

    public function test_login_updates_last_login_timestamp(): void
    {
        $admin = $this->createAdmin();

        $this->postJson($this->loginPath(), $this->validData());

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
    }

    // ===== SAD PATH (4 test) =====

    public function test_login_with_wrong_nip_returns_422(): void
    {
        $response = $this->postJson($this->loginPath(), $this->validData([
            'nip' => '000000000000000000',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Kredensial tidak cocok.');
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginPath(), $this->validData([
            'password' => 'wrongpassword123',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Kredensial tidak cocok.');
    }

    public function test_login_with_inactive_admin_returns_422(): void
    {
        $this->createAdmin(['is_active' => false]);

        $response = $this->postJson($this->loginPath(), $this->validData());

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Akun Anda dinonaktifkan.');
    }

    public function test_login_with_trashed_admin_returns_422(): void
    {
        $admin = $this->createAdmin();
        $admin->delete();

        $response = $this->postJson($this->loginPath(), $this->validData());

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Kredensial tidak cocok.');
    }

    // ===== BOUNDARY (2 test) =====

    public function test_login_with_nip_exactly_18_digits(): void
    {
        $this->createAdmin(['nip' => str_repeat('1', 18)]);

        $response = $this->postJson($this->loginPath(), [
            'nip' => str_repeat('1', 18),
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    public function test_login_with_password_exactly_8_chars(): void
    {
        $this->createAdmin(['password' => bcrypt('abcd1234')]);

        $response = $this->postJson($this->loginPath(), [
            'nip' => '123456789012345678',
            'password' => 'abcd1234',
        ]);

        $response->assertStatus(200);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_login_with_nip_containing_special_characters(): void
    {
        $this->createAdmin(['nip' => '123456789012345678']);

        $response = $this->postJson($this->loginPath(), [
            'nip' => '1234-5678-9012-345-678',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_login_with_empty_nip_returns_422(): void
    {
        $response = $this->postJson($this->loginPath(), [
            'nip' => '',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_empty_password_returns_422(): void
    {
        $response = $this->postJson($this->loginPath(), [
            'nip' => '123456789012345678',
            'password' => '',
        ]);

        $response->assertStatus(422);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_login_works_for_all_roles(): void
    {
        $roles = [
            '1' => 'super_admin',
            '2' => 'regency_admin',
            '3' => 'district_admin',
            '4' => 'village_officer',
        ];

        foreach ($roles as $index => $role) {
            $nip = str_pad((string) $index, 18, '0', STR_PAD_LEFT);
            $this->createAdmin(['nip' => $nip, 'role' => $role]);

            $response = $this->postJson($this->loginPath(), [
                'nip' => $nip,
                'password' => 'password123',
            ]);

            $response->assertStatus(200);
        }
    }

    // ===== SECURITY (3 test) =====

    public function test_login_same_error_for_wrong_nip_and_wrong_password(): void
    {
        // Clear rate limiter untuk NIP yang akan dipakai
        RateLimiter::clear('000000000000000000|127.0.0.1');
        RateLimiter::clear('000000000000000001|127.0.0.1');

        $res1 = $this->postJson($this->loginPath(), [
            'nip' => '000000000000000000',
            'password' => 'password123',
        ]);

        $res2 = $this->postJson($this->loginPath(), [
            'nip' => '000000000000000001',
            'password' => 'wrongpassword',
        ]);

        $this->assertEquals(
            'Kredensial tidak cocok.',
            $res1->json('message')
        );
        $this->assertEquals(
            $res1->json('message'),
            $res2->json('message')
        );
    }

    public function test_login_response_never_exposes_password(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginPath(), $this->validData());

        $response->assertStatus(200);
        $responseData = $response->json('data.admin');
        $this->assertIsArray($responseData);
        $this->assertArrayNotHasKey('password', $responseData);
    }

    public function test_rate_limiter_blocks_after_max_attempts(): void
    {
        $maxAttempts = config('sabana.rate_limit.max_attempts', 3);

        // Pakai NIP berbeda agar tidak conflict dengan test lain
        $nip = '999999999999999999';

        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->postJson($this->loginPath(), [
                'nip' => $nip,
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson($this->loginPath(), [
            'nip' => $nip,
            'password' => 'wrongpassword',
        ]);

        $this->assertEquals(429, $response->status());
    }
}