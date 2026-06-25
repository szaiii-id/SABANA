<?php

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ResendOtpRequest;
use App\Models\Citizen;
use Illuminate\Support\Facades\Validator;

class ResendOtpRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new ResendOtpRequest();
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
    // NIK — 4 TEST
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

    public function test_nik_with_spaces_is_sanitized_by_endpoint()
    {
        $uniqueNik = '6309999999999999';

        Citizen::factory()->create([
            'nik'             => $uniqueNik,
            'whatsapp_number' => '6281234567890',
            'is_verified'     => false,
        ]);

        $response = $this->postJson('/api/v1/auth/resend-registration-otp', [
            'nik'             => '6309 9999 9999 9999',
            'whatsapp_number' => '6281234567890',
        ]);

        $response->assertStatus(200);
    }

    public function test_nik_as_boolean_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = true;

        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    // =============================================
    // WHATSAPP NUMBER — 5 TEST
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
        $this->assertContains('Nomor WhatsApp minimal 10 digit.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_9_digits_is_rejected()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '628123456';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp minimal 10 digit.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_max_15_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '628123456789012345';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp maksimal 15 digit.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_only_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '62812abcdef';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp hanya boleh berisi angka.', $errors['whatsapp_number']);
    }

    // =============================================
    // ALL VALID — 1 TEST
    // =============================================

    public function test_all_valid_data_passes_every_rule()
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}