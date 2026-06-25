<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;

final class LoginRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    /**
     * Validate tanpa prepareForValidation — untuk test karakter khusus
     */
    private function validateRaw(array $data): array
    {
        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik' => '6301234567890123',
            'pin' => '123456',
        ];
    }

    // ===== NIK =====

    public function test_nik_is_required(): void
    {
        $data = $this->validData();
        unset($data['nik']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('NIK wajib diisi.', $errors['nik']);
    }

    public function test_nik_must_be_16_digits(): void
    {
        $data = $this->validData();
        $data['nik'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('Format NIK harus tepat 16 digit.', $errors['nik']);
    }

    public function test_nik_15_digits_is_rejected(): void
    {
        $data = $this->validData();
        $data['nik'] = '630123456789012';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_17_digits_is_rejected(): void
    {
        $data = $this->validData();
        $data['nik'] = '63012345678901234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_as_array_is_rejected(): void
    {
        $data = $this->validData();
        $data['nik'] = ['6301234567890123'];
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    // ===== PIN =====

    public function test_pin_is_required(): void
    {
        $data = $this->validData();
        unset($data['pin']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
        $this->assertContains('PIN wajib diisi.', $errors['pin']);
    }

    public function test_pin_must_be_6_digits(): void
    {
        $data = $this->validData();
        $data['pin'] = '1234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
        $this->assertContains('PIN harus tepat 6 digit.', $errors['pin']);
    }

    public function test_pin_5_digits_is_rejected(): void
    {
        $data = $this->validData();
        $data['pin'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    public function test_pin_7_digits_is_rejected(): void
    {
        $data = $this->validData();
        $data['pin'] = '1234567';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    /** @test */
    public function test_pin_as_array_is_rejected(): void
    {
        $data = $this->validData();
        $data['pin'] = ['123456']; // ✅ Array — pasti gagal

        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    /** @test */
    public function test_pin_with_letters_is_rejected(): void
    {
        // ✅ Bypass prepareForValidation — test rule regex langsung
        $validator = Validator::make(
            ['nik' => '6301234567890123', 'pin' => '12ab56'],
            (new LoginRequest())->rules(),
            (new LoginRequest())->messages()
        );

        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('pin', $errors);
        $this->assertContains('PIN hanya boleh berisi angka.', $errors['pin']);
    }

    // ===== ALL VALID =====

    public function test_all_valid_data_passes_every_rule(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}