<?php

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ResetPinRequest;
use Illuminate\Support\Facades\Validator;

class ResetPinRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new ResetPinRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik'                  => '6301234567890123',
            'whatsapp_number'      => '6281234567890',
            'otp'                  => '123456',
            'new_pin'              => '654321',
            'new_pin_confirmation' => '654321',
        ];
    }

    // =============================================
    // NIK — 3 TEST
    // =============================================

    public function test_nik_is_required()
    {
        $data = $this->validData();
        unset($data['nik']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_must_be_16_digits()
    {
        $data = $this->validData();
        $data['nik'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_15_digits_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = '630123456789012';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    // =============================================
    // WHATSAPP — 2 TEST
    // =============================================

    public function test_whatsapp_number_is_required()
    {
        $data = $this->validData();
        unset($data['whatsapp_number']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_min_10_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '62812';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    // =============================================
    // OTP — 4 TEST
    // =============================================

    public function test_otp_is_required()
    {
        $data = $this->validData();
        unset($data['otp']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
        $this->assertContains('Kode OTP wajib diisi.', $errors['otp']);
    }

    public function test_otp_must_be_6_digits()
    {
        $data = $this->validData();
        $data['otp'] = '1234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
    }

    public function test_otp_5_digits_is_rejected()
    {
        $data = $this->validData();
        $data['otp'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
    }

    public function test_otp_7_digits_is_rejected()
    {
        $data = $this->validData();
        $data['otp'] = '1234567';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
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
    }

    public function test_new_pin_must_be_6_digits()
    {
        $data = $this->validData();
        $data['new_pin'] = '1234';
        $data['new_pin_confirmation'] = '1234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
    }

    public function test_new_pin_5_digits_is_rejected()
    {
        $data = $this->validData();
        $data['new_pin'] = '12345';
        $data['new_pin_confirmation'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
    }

    public function test_new_pin_7_digits_is_rejected()
    {
        $data = $this->validData();
        $data['new_pin'] = '1234567';
        $data['new_pin_confirmation'] = '1234567';
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

    public function test_new_pin_confirmation_must_match()
    {
        $data = $this->validData();
        $data['new_pin_confirmation'] = '999999';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('new_pin', $errors);
        $this->assertContains('Konfirmasi PIN baru tidak cocok.', $errors['new_pin']);
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