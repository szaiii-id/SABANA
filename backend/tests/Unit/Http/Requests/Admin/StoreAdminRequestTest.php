<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Http\Requests\Admin\StoreAdminRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class StoreAdminRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $request = new StoreAdminRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
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

        return [
            'nip'        => '199001012020011001',
            'name'       => 'Admin Test',
            'password'   => 'password123',
            'role'       => 'village_officer',
            'village_id' => '6301020001',
        ];
    }

    // ===== HAPPY PATH =====

    /** @test */
    public function test_all_valid_data_passes(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }

    // ===== SAD PATH =====

    /** @test */
    public function test_nip_is_required(): void
    {
        $data = $this->validData();
        unset($data['nip']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
        $this->assertContains('NIP wajib diisi.', $errors['nip']);
    }

    /** @test */
    public function test_name_is_required(): void
    {
        $data = $this->validData();
        unset($data['name']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('name', $errors);
        $this->assertContains('Nama lengkap wajib diisi.', $errors['name']);
    }

    /** @test */
    public function test_password_is_required(): void
    {
        $data = $this->validData();
        unset($data['password']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Kata sandi wajib diisi.', $errors['password']);
    }

    /** @test */
    public function test_role_is_required(): void
    {
        $data = $this->validData();
        unset($data['role']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('role', $errors);
    }

    /** @test */
    public function test_role_invalid_value(): void
    {
        $data = $this->validData();
        $data['role'] = 'invalid_role';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('role', $errors);
    }

    /** @test */
    public function test_nip_unique(): void
    {
        $admin = new Admin();
        $admin->nip = '199001012020011001';
        $admin->name = 'Existing';
        $admin->password = bcrypt('password');
        $admin->role = 'village_officer';
        $admin->save();

        $errors = $this->validate($this->validData());
        $this->assertArrayHasKey('nip', $errors);
        $this->assertContains('NIP ini sudah digunakan.', $errors['nip']);
    }

    // ===== BOUNDARY =====

    /** @test */
    public function test_nip_17_digits_is_rejected(): void
    {
        $data = $this->validData();
        $data['nip'] = '19900101202001100';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    /** @test */
    public function test_nip_19_digits_is_rejected(): void
    {
        $data = $this->validData();
        $data['nip'] = '1990010120200110019';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    /** @test */
    public function test_nip_exactly_18_digits_passes(): void
    {
        $data = $this->validData();
        $data['nip'] = '199001012020011002';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('nip', $errors);
    }

    /** @test */
    public function test_password_min_8(): void
    {
        $data = $this->validData();
        $data['password'] = '1234567';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Kata sandi minimal 8 karakter.', $errors['password']);
    }

    /** @test */
    public function test_password_exactly_8_chars_passes(): void
    {
        $data = $this->validData();
        $data['password'] = '12345678';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('password', $errors);
    }

    /** @test */
    public function test_name_max_255(): void
    {
        $data = $this->validData();
        $data['name'] = str_repeat('A', 256);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('name', $errors);
    }

    /** @test */
    public function test_name_exactly_255_chars_passes(): void
    {
        $data = $this->validData();
        $data['name'] = str_repeat('A', 255);
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('name', $errors);
    }

    // ===== EDGE CASE =====

    /** @test */
    public function test_nip_with_spaces_fails_size_validation(): void
    {
        $data = $this->validData();
        $data['nip'] = '1990 0101 2020 0110 01';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    // ===== NULL / EMPTY =====

    /** @test */
    public function test_empty_nip_is_rejected(): void
    {
        $data = $this->validData();
        $data['nip'] = '';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    /** @test */
    public function test_empty_name_is_rejected(): void
    {
        $data = $this->validData();
        $data['name'] = '';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('name', $errors);
    }

    // ===== DATA TYPE =====

    /** @test */
    public function test_nip_as_array_is_rejected(): void
    {
        $data = $this->validData();
        $data['nip'] = ['199001012020011001'];
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    // ===== EQUIVALENCE PARTITION =====

    /** @test */
    public function test_role_regency_admin_requires_regency_id(): void
    {
        $data = $this->validData();
        $data['role'] = 'regency_admin';
        unset($data['village_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('regency_id', $errors);
    }

    /** @test */
    public function test_role_district_admin_requires_district_id(): void
    {
        $data = $this->validData();
        $data['role'] = 'district_admin';
        $data['regency_id'] = '6301';
        unset($data['village_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('district_id', $errors);
    }

    // ===== SECURITY =====

    /** @test */
    public function test_nip_xss_fails_size_validation(): void
    {
        $data = $this->validData();
        $data['nip'] = '<script>alert(1)</script>';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }
}