<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Support\Facades\Validator;

final class AdminLoginRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new AdminLoginRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nip'      => '199001012020011001',
            'password' => 'password123',
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
    public function test_password_is_required(): void
    {
        $data = $this->validData();
        unset($data['password']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Kata sandi wajib diisi.', $errors['password']);
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
        $data['nip'] = '199001012020011001';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('nip', $errors);
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
    public function test_password_7_chars_is_rejected(): void
    {
        $data = $this->validData();
        $data['password'] = '1234567';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Kata sandi minimal 8 karakter.', $errors['password']);
    }

    // ===== EDGE CASE =====

    /** @test */
    public function test_nip_with_spaces_is_rejected_by_validator(): void
    {
        $data = $this->validData();
        $data['nip'] = '1990 0101 2020 0110 01';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    /** @test */
    public function test_nip_with_dashes_is_rejected_by_validator(): void
    {
        $data = $this->validData();
        $data['nip'] = '1990-0101-2020-0110-01';
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
    public function test_empty_password_is_rejected(): void
    {
        $data = $this->validData();
        $data['password'] = '';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('password', $errors);
    }

    /** @test */
    public function test_null_nip_is_rejected(): void
    {
        $data = $this->validData();
        $data['nip'] = null;
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
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

    /** @test */
    public function test_nip_as_integer_is_rejected(): void
    {
        $data = $this->validData();
        $data['nip'] = 199001012020011001;
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    /** @test */
    public function test_password_as_array_is_rejected(): void
    {
        $data = $this->validData();
        $data['password'] = ['password123'];
        $errors = $this->validate($data);
        $this->assertArrayHasKey('password', $errors);
    }

    // ===== EQUIVALENCE PARTITION =====

    /** @test */
    public function test_nip_only_letters_is_rejected_by_size(): void
    {
        $data = $this->validData();
        $data['nip'] = 'ABCDEFGHIJKLMNOPQR';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('nip', $errors);
    }

    /** @test */
    public function test_nip_alphanumeric_is_rejected_by_size(): void
    {
        $data = $this->validData();
        $data['nip'] = '19900101202001100A';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('nip', $errors);
    }

    // ===== SECURITY =====

    /** @test */
    public function test_nip_xss_attempt_is_rejected(): void
    {
        $data = $this->validData();
        $data['nip'] = '<script>alert(1)</script>';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nip', $errors);
    }

    /** @test */
    public function test_password_sql_injection_passes_min_length(): void
    {
        $data = $this->validData();
        $data['password'] = "' OR '1'='1";
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('password', $errors);
    }
}