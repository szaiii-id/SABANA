<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\UpdateAdminRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class UpdateAdminRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $request = new UpdateAdminRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\DB::table('provinces')->insertOrIgnore([
            'id' => '63', 'name' => 'Kalimantan Selatan',
        ]);
        \Illuminate\Support\Facades\DB::table('regencies')->insertOrIgnore([
            'id' => '6301', 'province_id' => '63', 'name' => 'Tanah Laut',
        ]);
        \Illuminate\Support\Facades\DB::table('districts')->insertOrIgnore([
            'id' => '6301020', 'regency_id' => '6301', 'name' => 'Pelaihari',
        ]);
        \Illuminate\Support\Facades\DB::table('villages')->insertOrIgnore([
            'id' => '6301020001', 'district_id' => '6301020', 'name' => 'Desa Test',
        ]);
    }

    // ===== HAPPY PATH =====

    /** @test */
    public function test_empty_data_passes(): void
    {
        $errors = $this->validate([]);
        $this->assertEmpty($errors);
    }

    /** @test */
    public function test_name_valid_passes(): void
    {
        $errors = $this->validate(['name' => 'Admin Updated']);
        $this->assertArrayNotHasKey('name', $errors);
    }

    /** @test */
    public function test_password_valid_passes(): void
    {
        $errors = $this->validate(['password' => 'password123']);
        $this->assertArrayNotHasKey('password', $errors);
    }

    // ===== SAD PATH =====

    /** @test */
    public function test_name_max_255(): void
    {
        $errors = $this->validate(['name' => str_repeat('A', 256)]);
        $this->assertArrayHasKey('name', $errors);
    }

    /** @test */
    public function test_password_min_8(): void
    {
        $errors = $this->validate(['password' => '1234567']);
        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Kata sandi minimal 8 karakter.', $errors['password']);
    }

    // ===== BOUNDARY =====

    /** @test */
    public function test_name_exactly_255_chars_passes(): void
    {
        $errors = $this->validate(['name' => str_repeat('A', 255)]);
        $this->assertArrayNotHasKey('name', $errors);
    }

    /** @test */
    public function test_password_exactly_8_chars_passes(): void
    {
        $errors = $this->validate(['password' => '12345678']);
        $this->assertArrayNotHasKey('password', $errors);
    }

    // ===== EDGE CASE =====

    /** @test */
    public function test_password_kosong_diabaikan(): void
    {
        $errors = $this->validate(['password' => '']);
        $this->assertArrayNotHasKey('password', $errors);
    }

    /** @test */
    public function test_name_trimmed_accepted(): void
    {
        $errors = $this->validate(['name' => '   Admin Name   ']);
        $this->assertArrayNotHasKey('name', $errors);
    }

    // ===== NULL / EMPTY =====

    /** @test */
    public function test_nip_tidak_bisa_diubah(): void
    {
        $errors = $this->validate(['nip' => '199001012020011099']);
        $this->assertArrayNotHasKey('nip', $errors);
    }

    // ===== DATA TYPE =====

    /** @test */
    public function test_name_as_array_is_rejected(): void
    {
        $errors = $this->validate(['name' => ['array value']]);
        $this->assertArrayHasKey('name', $errors);
    }

    /** @test */
    public function test_is_active_boolean(): void
    {
        $errors = $this->validate(['is_active' => 'not-a-boolean']);
        $this->assertArrayHasKey('is_active', $errors);
    }

    // ===== EQUIVALENCE PARTITION =====

    /** @test */
    public function test_regency_id_valid_format(): void
    {
        $errors = $this->validate(['regency_id' => '6301']);
        $this->assertArrayNotHasKey('regency_id', $errors);
    }

    /** @test */
    public function test_regency_id_invalid_length(): void
    {
        $errors = $this->validate(['regency_id' => '63']);
        $this->assertArrayHasKey('regency_id', $errors);
    }

    /** @test */
    public function test_district_id_valid_format(): void
    {
        $errors = $this->validate(['district_id' => '6301020']);
        $this->assertArrayNotHasKey('district_id', $errors);
    }

    /** @test */
    public function test_district_id_invalid_length(): void
    {
        $errors = $this->validate(['district_id' => '6301']);
        $this->assertArrayHasKey('district_id', $errors);
    }

    /** @test */
    public function test_village_id_valid_format(): void
    {
        $errors = $this->validate(['village_id' => '6301020001']);
        $this->assertArrayNotHasKey('village_id', $errors);
    }

    /** @test */
    public function test_village_id_invalid_length(): void
    {
        $errors = $this->validate(['village_id' => '6301']);
        $this->assertArrayHasKey('village_id', $errors);
    }

    // ===== SECURITY =====

    /** @test */
    public function test_name_xss_is_accepted_by_validator(): void
    {
        $errors = $this->validate(['name' => '<script>alert(1)</script>']);
        $this->assertArrayNotHasKey('name', $errors);
    }

    /** @test */
    public function test_role_tidak_bisa_diubah(): void
    {
        $errors = $this->validate(['role' => 'super_admin']);
        $this->assertArrayNotHasKey('role', $errors);
    }
}