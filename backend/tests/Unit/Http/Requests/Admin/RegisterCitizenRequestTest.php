<?php

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\RegisterCitizenRequest;
use Illuminate\Support\Facades\Validator;

class RegisterCitizenRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new RegisterCitizenRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Joko Widodo',
            'whatsapp_number'    => '6281234567890',
            'with_pin'           => true,
        ];
    }

    // =============================================
    // NIK
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
        $data['nik'] = '63012345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('NIK harus 16 digit.', $errors['nik']);
    }

    public function test_nik_must_start_with_63()
    {
        $data = $this->validData();
        $data['nik'] = '3201234567890123';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('NIK tidak valid. Gunakan NIK KTP Kalimantan Selatan (diawali 63).', $errors['nik']);
    }

    public function test_nik_only_numbers_accepted()
    {
        $data = $this->validData();
        $data['nik'] = '63ABCDEFGHIJKLMNO';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_15_digits_rejected()
    {
        $data = $this->validData();
        $data['nik'] = '630123456789012';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_17_digits_rejected()
    {
        $data = $this->validData();
        $data['nik'] = '63012345678901234';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    // =============================================
    // FAMILY CARD NUMBER
    // =============================================

    public function test_family_card_number_is_required()
    {
        $data = $this->validData();
        unset($data['family_card_number']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('family_card_number', $errors);
    }

    public function test_family_card_number_must_be_16_digits()
    {
        $data = $this->validData();
        $data['family_card_number'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('family_card_number', $errors);
        $this->assertContains('Nomor KK harus 16 digit.', $errors['family_card_number']);
    }

    public function test_family_card_number_only_numbers()
    {
        $data = $this->validData();
        $data['family_card_number'] = 'ABCDEFGHIJKLMNOP';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('family_card_number', $errors);
        $this->assertContains('Nomor KK hanya boleh berisi angka.', $errors['family_card_number']);
    }

    // =============================================
    // FULL NAME
    // =============================================

    public function test_full_name_is_required()
    {
        $data = $this->validData();
        unset($data['full_name']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('full_name', $errors);
        $this->assertContains('Nama lengkap wajib diisi.', $errors['full_name']);
    }

    public function test_full_name_min_3_characters()
    {
        $data = $this->validData();
        $data['full_name'] = 'AB';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_full_name_max_255_characters()
    {
        $data = $this->validData();
        $data['full_name'] = str_repeat('A', 256);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_full_name_rejects_html_tags()
    {
        $data = $this->validData();
        $data['full_name'] = '<script>alert("xss")</script>';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_full_name_accepts_valid_special_characters()
    {
        $data = $this->validData();
        $data['full_name'] = "O'Brien, Jr. - Test";
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('full_name', $errors);
    }

    // =============================================
    // WHATSAPP NUMBER
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
        $data['whatsapp_number'] = '0812';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_max_15_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '08123456789012345';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_only_numbers()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '0812ABCDEFGH';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp hanya boleh berisi angka.', $errors['whatsapp_number']);
    }

    // =============================================
    // WITH PIN
    // =============================================

    public function test_with_pin_is_required()
    {
        $data = $this->validData();
        unset($data['with_pin']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('with_pin', $errors);
        $this->assertContains('Pilih metode pendaftaran.', $errors['with_pin']);
    }

    public function test_with_pin_must_be_boolean()
    {
        $data = $this->validData();
        $data['with_pin'] = 'yes';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('with_pin', $errors);
    }

    public function test_with_pin_accepts_one_as_true()
    {
        $data = $this->validData();
        $data['with_pin'] = 1;
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('with_pin', $errors);
    }

    public function test_with_pin_accepts_zero_as_false()
    {
        $data = $this->validData();
        $data['with_pin'] = 0;
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('with_pin', $errors);
    }


    // =============================================
    // ALL VALID
    // =============================================

    public function test_all_valid_data_passes()
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }

    public function test_valid_with_pin_false_passes()
    {
        $data = $this->validData();
        $data['with_pin'] = false;
        $errors = $this->validate($data);
        $this->assertEmpty($errors);
    }
}