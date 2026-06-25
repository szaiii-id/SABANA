<?php

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use App\Models\Citizen;
use App\Http\Requests\VerifyRegistrationRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VerifyRegistrationRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $request = new VerifyRegistrationRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik'             => '6301234567890123',
            'whatsapp_number' => '6281234567890',
            'otp'             => '123456',
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

    public function test_valid_nik_passes()
    {
        $errors = $this->validate($this->validData());
        $this->assertArrayNotHasKey('nik', $errors);
    }

    // =============================================
    // WHATSAPP NUMBER — 4 TEST
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
    // OTP — 2 TEST
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
        $this->assertContains('Kode OTP harus tepat 6 digit.', $errors['otp']);
    }

    // =============================================
    // OTP BOUNDARY — 2 TEST
    // =============================================

    public function test_otp_5_digits_is_rejected()
    {
        $data = $this->validData();
        $data['otp'] = '12345';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
        $this->assertContains('Kode OTP harus tepat 6 digit.', $errors['otp']);
    }

    public function test_otp_7_digits_is_rejected()
    {
        $data = $this->validData();
        $data['otp'] = '1234567';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
        $this->assertContains('Kode OTP harus tepat 6 digit.', $errors['otp']);
    }

    // =============================================
    // OTP DATA TYPE — 2 TEST
    // =============================================

    public function test_otp_as_array_is_rejected()
    {
        $data = $this->validData();
        $data['otp'] = ['123456'];

        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
    }

    public function test_otp_with_letters_is_rejected()
    {
        $data = $this->validData();
        $data['otp'] = '12ab56';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('otp', $errors);
        $this->assertContains('Kode OTP hanya boleh berisi angka.', $errors['otp']);
    }

    // =============================================
    // SANITASI — 1 TEST (VIA ENDPOINT)
    // =============================================

    public function test_sanitized_input_accepted_by_endpoint()
    {
        $otp = '654321';
        Citizen::factory()->create([
            'nik'                      => '6301234567890456',
            'whatsapp_number'          => '6281234567890',
            'is_verified'              => false,
            'temporary_pin'            => Hash::make($otp),
            'temporary_pin_expired_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-registration', [
            'nik'             => '6301 2345 6789 0456',
            'whatsapp_number' => '+62812-3456-7890',
            'otp'             => '65 43 21',
        ]);

        $response->assertStatus(200);
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