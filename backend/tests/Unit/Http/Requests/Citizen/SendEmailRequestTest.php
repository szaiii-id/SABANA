<?php

namespace Tests\Unit\Http\Requests\Citizen;

use Tests\TestCase;
use App\Http\Requests\Citizen\SendEmailRequest;
use Illuminate\Support\Facades\Validator;

class SendEmailRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new SendEmailRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nama'   => 'Muhammad Noor',
            'email'  => 'test@email.com',
            'subjek' => 'Subjek Laporan',
            'pesan'  => 'Pesan laporan yang cukup panjang',
        ];
    }

    // =============================================
    // NAMA — 2 TEST
    // =============================================

    public function test_nama_is_required()
    {
        $data = $this->validData();
        unset($data['nama']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nama', $errors);
        $this->assertContains('Nama wajib diisi.', $errors['nama']);
    }

    public function test_nama_max_255_characters()
    {
        $data = $this->validData();
        $data['nama'] = str_repeat('A', 256);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('nama', $errors);
    }

    // =============================================
    // EMAIL — 3 TEST
    // =============================================

    public function test_email_is_required()
    {
        $data = $this->validData();
        unset($data['email']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_email_must_be_valid_format()
    {
        $data = $this->validData();
        $data['email'] = 'bukanemail';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('email', $errors);
        $this->assertContains('Format email tidak valid.', $errors['email']);
    }

    public function test_email_valid_format_passes()
    {
        $errors = $this->validate($this->validData());
        $this->assertArrayNotHasKey('email', $errors);
    }

    // =============================================
    // SUBJEK — 3 TEST
    // =============================================

    public function test_subjek_is_required()
    {
        $data = $this->validData();
        unset($data['subjek']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('subjek', $errors);
    }

    public function test_subjek_min_5_characters()
    {
        $data = $this->validData();
        $data['subjek'] = 'abcd';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('subjek', $errors);
        $this->assertContains('Subjek minimal 5 karakter.', $errors['subjek']);
    }

    public function test_subjek_exactly_5_characters_passes()
    {
        $data = $this->validData();
        $data['subjek'] = '12345';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('subjek', $errors);
    }

    // =============================================
    // PESAN — 3 TEST
    // =============================================

    public function test_pesan_email_is_required()
    {
        $data = $this->validData();
        unset($data['pesan']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('pesan', $errors);
    }

    public function test_pesan_email_min_10_characters()
    {
        $data = $this->validData();
        $data['pesan'] = 'Pendek';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('pesan', $errors);
    }

    public function test_pesan_email_max_2000_characters()
    {
        $data = $this->validData();
        $data['pesan'] = str_repeat('A', 2001);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('pesan', $errors);
    }

    // =============================================
    // ALL VALID
    // =============================================

    public function test_all_valid_passes()
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}