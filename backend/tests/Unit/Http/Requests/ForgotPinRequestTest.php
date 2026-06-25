<?php

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ForgotPinRequest;
use Illuminate\Support\Facades\Validator;

class ForgotPinRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new ForgotPinRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
        ];
    }

    // =============================================
    // NIK — 5 TEST
    // =============================================

    public function test_nik_is_required()
    {
        $data = $this->validData();
        unset($data['nik']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('NIK wajib diisi.', $errors['nik']);
    }

    public function test_nik_must_be_16_digits()
    {
        $data = $this->validData();
        $data['nik'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('Format NIK harus tepat 16 digit.', $errors['nik']);
    }

    public function test_nik_15_digits_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = '630123456789012';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_17_digits_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = '63012345678901234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_as_array_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = ['6301234567890123'];
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    // =============================================
    // WHATSAPP — 5 TEST
    // =============================================

    public function test_whatsapp_number_is_required()
    {
        $data = $this->validData();
        unset($data['whatsapp_number']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp wajib diisi.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_min_10_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '62812';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_max_15_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '628123456789012345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_only_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '62812abcdef';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_9_digits_is_rejected()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '628123456';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
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