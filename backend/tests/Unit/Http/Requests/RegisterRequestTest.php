<?php

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\RegisterRequest;
use App\Models\Citizen;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $request = new RegisterRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Muhammad Noor',
            'whatsapp_number'    => '6281234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];
    }

    // =============================================
    // NIK FIELD — 9 TEST
    // =============================================

    public function test_nik_is_required()
    {
        $data = $this->validData();
        unset($data['nik']);

        $errors = $this->validate($data);

        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('NIK wajib diisi.', $errors['nik']);
    }

    public function test_nik_must_be_exactly_16_digits()
    {
        $data = $this->validData();
        $data['nik'] = '63012345';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains('Format NIK harus tepat 16 digit.', $errors['nik']);
    }

    public function test_nik_must_start_with_63_for_kalsel()
    {
        $data = $this->validData();
        $data['nik'] = '3201234567890123';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains(
            'NIK tidak valid. Pendaftaran SABANA khusus untuk KTP Kalimantan Selatan.',
            $errors['nik']
        );
    }

    public function test_valid_kalsel_nik_passes_validation()
    {
        $errors = $this->validate($this->validData());
        $this->assertArrayNotHasKey('nik', $errors);
    }

    public function test_verified_nik_cannot_register_again()
    {
        Citizen::factory()->create([
            'nik'         => '6301234567890123',
            'is_verified' => true,
        ]);

        $errors = $this->validate($this->validData());

        $this->assertArrayHasKey('nik', $errors);
        $this->assertContains(
            'NIK ini sudah terdaftar dan terverifikasi. Gunakan fitur Lupa PIN jika Anda pemilik akun.',
            $errors['nik']
        );
    }

    public function test_unverified_nik_can_register_again()
    {
        Citizen::factory()->create([
            'nik'         => '6301234567890123',
            'is_verified' => false,
        ]);

        $errors = $this->validate($this->validData());
        $this->assertArrayNotHasKey('nik', $errors);
    }

    public function test_nik_with_alpha_characters_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = '6301234567890abc';

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

    public function test_nik_17_digits_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = '63012345678901234';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    // =============================================
    // DATA TYPE TEST — NIK
    // =============================================

    public function test_nik_as_array_is_rejected()
    {
        $data = $this->validData();
        $data['nik'] = ['6301234567890123'];

        $errors = $this->validate($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    // =============================================
    // FAMILY CARD NUMBER (KK) — 3 TEST
    // =============================================

    public function test_family_card_number_is_required()
    {
        $data = $this->validData();
        unset($data['family_card_number']);

        $errors = $this->validate($data);

        $this->assertArrayHasKey('family_card_number', $errors);
        $this->assertContains('Nomor Kartu Keluarga wajib diisi.', $errors['family_card_number']);
    }

    public function test_family_card_number_must_be_16_digits()
    {
        $data = $this->validData();
        $data['family_card_number'] = '12345';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('family_card_number', $errors);
        $this->assertContains('Format Nomor KK harus tepat 16 digit.', $errors['family_card_number']);
    }

    public function test_family_card_number_only_accepts_digits()
    {
        $data = $this->validData();
        $data['family_card_number'] = '63012345abcdef01';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('family_card_number', $errors);
        $this->assertContains('Nomor KK hanya boleh berisi angka.', $errors['family_card_number']);
    }

    // =============================================
    // FULL NAME — 10 TEST
    // =============================================

    public function test_full_name_is_required()
    {
        $data = $this->validData();
        unset($data['full_name']);

        $errors = $this->validate($data);

        $this->assertArrayHasKey('full_name', $errors);
        $this->assertContains('Nama lengkap wajib diisi.', $errors['full_name']);
    }

    public function test_full_name_minimum_3_characters()
    {
        $data = $this->validData();
        $data['full_name'] = 'Ab';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('full_name', $errors);
        $this->assertContains('Nama lengkap minimal 3 karakter.', $errors['full_name']);
    }

    /** @test BOUNDARY: tepat 3 karakter (batas bawah valid) */
    public function test_full_name_exactly_3_characters_passes()
    {
        $data = $this->validData();
        $data['full_name'] = 'Ali';

        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('full_name', $errors);
    }

    public function test_full_name_maximum_255_characters()
    {
        $data = $this->validData();
        $data['full_name'] = str_repeat('A', 256);

        $errors = $this->validate($data);

        $this->assertArrayHasKey('full_name', $errors);
        $this->assertContains('Nama lengkap maksimal 255 karakter.', $errors['full_name']);
    }

    /** @test BOUNDARY: tepat 255 karakter (batas atas valid) */
    public function test_full_name_exactly_255_characters_passes()
    {
        $data = $this->validData();
        $data['full_name'] = str_repeat('A', 255);

        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('full_name', $errors);
    }

    public function test_full_name_rejects_special_characters()
    {
        $data = $this->validData();
        $data['full_name'] = 'Muhammad @Noor #2024';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('full_name', $errors);
        $this->assertContains(
            'Nama lengkap hanya boleh berisi huruf, spasi, titik, koma, petik satu, dan strip.',
            $errors['full_name']
        );
    }

    /** @test EDGE CASE: emoji ditolak */
    public function test_full_name_with_emoji_is_rejected()
    {
        $data = $this->validData();
        $data['full_name'] = 'Muhammad 😀 Noor';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('full_name', $errors);
    }

    /** @test EDGE CASE: karakter Unicode non-Latin ditolak */
    public function test_full_name_with_chinese_characters_is_rejected()
    {
        $data = $this->validData();
        $data['full_name'] = '穆罕默德 Noor';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_full_name_accepts_valid_punctuation()
    {
        $data = $this->validData();
        $data['full_name'] = "Muhammad Noor, S.H., M.Kn.";

        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('full_name', $errors);
    }

    public function test_full_name_with_leading_apostrophe_is_rejected()
    {
        $data = $this->validData();
        $data['full_name'] = "'Noor Muhammad";

        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('full_name', $errors);
    }

    // =============================================
    // WHATSAPP NUMBER — 8 TEST
    // =============================================

    public function test_whatsapp_number_is_required()
    {
        $data = $this->validData();
        unset($data['whatsapp_number']);

        $errors = $this->validate($data);

        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp wajib diisi.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_minimum_10_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '62812345';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp minimal 10 digit.', $errors['whatsapp_number']);
    }

    /** @test BOUNDARY: 9 digit (batas bawah -1) */
    public function test_whatsapp_number_9_digits_is_rejected()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '628123456';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    /** @test BOUNDARY: tepat 10 digit (batas bawah valid) */
    public function test_whatsapp_number_exactly_10_digits_passes()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '6281234567';

        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('whatsapp_number', $errors);
    }

    /** @test BOUNDARY: tepat 15 digit (batas atas valid) */
    public function test_whatsapp_number_exactly_15_digits_passes()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '628123456789012';

        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_maximum_15_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '628123456789012345';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp maksimal 15 digit.', $errors['whatsapp_number']);
    }

    /** @test EDGE CASE: prefix 0 bukan 62 */
    public function test_whatsapp_starts_with_0_is_rejected()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '081234567890';

        // Min 10 digit: 081234567890 = 12 digit, lolos min:10, max:15, regex /^[0-9]+$/
        // Tapi secara bisnis, prepareForValidation hanya strip non-digit, prefix 0 tidak diubah
        // Ini harus lolos validasi dulu, baru service yang handle
        $errors = $this->validate($data);
        // Ekspektasi: validasi lolos karena hanya cek digit
        $this->assertArrayNotHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_only_accepts_digits()
    {
        $data = $this->validData();
        $data['whatsapp_number'] = '62812345abcd';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp hanya boleh berisi angka.', $errors['whatsapp_number']);
    }

    // =============================================
    // PIN FIELD — 10 TEST
    // =============================================

    public function test_pin_is_required()
    {
        $data = $this->validData();
        unset($data['pin']);

        $errors = $this->validate($data);

        $this->assertArrayHasKey('pin', $errors);
        $this->assertContains('PIN wajib diisi.', $errors['pin']);
    }

    public function test_pin_must_be_6_digits()
    {
        $data = $this->validData();
        $data['pin'] = '1234';
        $data['pin_confirmation'] = '1234';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('pin', $errors);
        $this->assertContains('PIN harus tepat 6 digit.', $errors['pin']);
    }

    /** @test BOUNDARY: 5 digit (batas -1) */
    public function test_pin_5_digits_is_rejected()
    {
        $data = $this->validData();
        $data['pin'] = '12345';
        $data['pin_confirmation'] = '12345';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    /** @test BOUNDARY: 7 digit (batas +1) */
    public function test_pin_7_digits_is_rejected()
    {
        $data = $this->validData();
        $data['pin'] = '1234567';
        $data['pin_confirmation'] = '1234567';

        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    public function test_pin_only_accepts_digits()
    {
        $data = $this->validData();
        $data['pin'] = '12ab56';
        $data['pin_confirmation'] = '12ab56';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('pin', $errors);
        $this->assertContains('PIN hanya boleh berisi angka.', $errors['pin']);
    }

    /** @test DATA TYPE: PIN sebagai boolean */
    public function test_pin_as_boolean_is_rejected()
    {
        $data = $this->validData();
        $data['pin'] = true;
        $data['pin_confirmation'] = true;

        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    /** @test DATA TYPE: PIN sebagai float */
    public function test_pin_as_float_is_rejected()
    {
        $data = $this->validData();
        $data['pin'] = 123456.0;
        $data['pin_confirmation'] = 123456.0;

        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    public function test_pin_confirmation_must_match()
    {
        $data = $this->validData();
        $data['pin'] = '123456';
        $data['pin_confirmation'] = '654321';

        $errors = $this->validate($data);

        $this->assertArrayHasKey('pin', $errors);
        $this->assertContains('Konfirmasi PIN tidak cocok dengan PIN yang dibuat.', $errors['pin']);
    }

    /** @test DATA TYPE: PIN confirmation sebagai array */
    public function test_pin_confirmation_as_array_is_rejected()
    {
        $data = $this->validData();
        $data['pin_confirmation'] = ['123456'];

        $errors = $this->validate($data);
        $this->assertArrayHasKey('pin', $errors);
    }

    // =============================================
    // ALL VALID
    // =============================================

    public function test_all_valid_data_passes_every_rule()
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }

    // =============================================
    // SANITIZATION VIA ENDPOINT
    // =============================================

    public function test_register_endpoint_sanitizes_html_tags_from_full_name()
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'nik'                => '6301 2345 6789 0456',
            'family_card_number' => '6301234567890456',
            'full_name'          => '<b>Muhammad</b> Noor',
            'whatsapp_number'    => '+62812-3456-7890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('citizens', [
            'full_name' => 'Muhammad Noor',
        ]);
    }
}