<?php

namespace Tests\Unit\Http\Requests\Citizen;

use Tests\TestCase;
use App\Http\Requests\Citizen\UpdatePinRequest;
use Illuminate\Support\Facades\Validator;

class UpdatePinRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new UpdatePinRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'current_pin'          => '123456',
            'new_pin'              => '654321',
            'new_pin_confirmation' => '654321',
        ];
    }

    // =============================================
    // CURRENT PIN — 5 TEST
    // =============================================

    public function test_current_pin_is_required()
    {
        $data = $this->validData();
        unset($data['current_pin']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('current_pin', $errors);
        $this->assertContains('PIN saat ini wajib diisi.', $errors['current_pin']);
    }

    public function test_current_pin_must_be_6_digits()
    {
        $data = $this->validData();
        $data['current_pin'] = '1234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('current_pin', $errors);
        $this->assertContains('PIN saat ini harus tepat 6 digit.', $errors['current_pin']);
    }

    public function test_current_pin_5_digits_is_rejected()
    {
        $data = $this->validData();
        $data['current_pin'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('current_pin', $errors);
    }

    public function test_current_pin_7_digits_is_rejected()
    {
        $data = $this->validData();
        $data['current_pin'] = '1234567';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('current_pin', $errors);
    }

    public function test_current_pin_only_digits()
    {
        $data = $this->validData();
        $data['current_pin'] = '12ab56';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('current_pin', $errors);
        $this->assertContains('PIN saat ini hanya boleh berisi angka.', $errors['current_pin']);
    }

    // =============================================
    // NEW PIN — 6 TEST
    // =============================================

    public function test_new_pin_is_required()
    {
        $data = $this->validData();
        unset($data['new_pin']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
        $this->assertContains('PIN baru wajib diisi.', $errors['new_pin']);
    }

    public function test_new_pin_must_be_6_digits()
    {
        $data = $this->validData();
        $data['new_pin'] = '1234';
        $data['new_pin_confirmation'] = '1234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
    }

    public function test_new_pin_must_differ_from_current()
    {
        $data = $this->validData();
        $data['new_pin'] = '123456';
        $data['new_pin_confirmation'] = '123456';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
        $this->assertContains('PIN baru tidak boleh sama dengan PIN saat ini.', $errors['new_pin']);
    }

    public function test_new_pin_confirmation_must_match()
    {
        $data = $this->validData();
        $data['new_pin_confirmation'] = '999999';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
        $this->assertContains('Konfirmasi PIN baru tidak cocok.', $errors['new_pin']);
    }

    public function test_new_pin_5_digits_is_rejected()
    {
        $data = $this->validData();
        $data['new_pin'] = '12345';
        $data['new_pin_confirmation'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
    }

    public function test_new_pin_only_digits()
    {
        $data = $this->validData();
        $data['new_pin'] = '12ab56';
        $data['new_pin_confirmation'] = '12ab56';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
        $this->assertContains('PIN baru hanya boleh berisi angka.', $errors['new_pin']);
    }

    // =============================================
    // ALL VALID
    // =============================================

    public function test_all_valid_data_passes()
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}