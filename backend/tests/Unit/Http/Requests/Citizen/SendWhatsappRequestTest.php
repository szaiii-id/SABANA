<?php

namespace Tests\Unit\Http\Requests\Citizen;

use Tests\TestCase;
use App\Http\Requests\Citizen\SendWhatsappRequest;
use Illuminate\Support\Facades\Validator;

class SendWhatsappRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new SendWhatsappRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    // =============================================
    // PESAN — 6 TEST
    // =============================================

    public function test_pesan_is_required()
    {
        $errors = $this->validate([]);
        $this->assertArrayHasKey('pesan', $errors);
        $this->assertContains('Pesan laporan wajib diisi.', $errors['pesan']);
    }

    public function test_pesan_min_10_characters()
    {
        $errors = $this->validate(['pesan' => 'Pendek']);
        $this->assertArrayHasKey('pesan', $errors);
        $this->assertContains('Pesan laporan minimal 10 karakter.', $errors['pesan']);
    }

    public function test_pesan_max_1000_characters()
    {
        $errors = $this->validate(['pesan' => str_repeat('A', 1001)]);
        $this->assertArrayHasKey('pesan', $errors);
        $this->assertContains('Pesan laporan maksimal 1000 karakter.', $errors['pesan']);
    }

    public function test_pesan_exactly_10_characters_passes()
    {
        $errors = $this->validate(['pesan' => '1234567890']);
        $this->assertArrayNotHasKey('pesan', $errors);
    }

    public function test_pesan_exactly_1000_characters_passes()
    {
        $errors = $this->validate(['pesan' => str_repeat('A', 1000)]);
        $this->assertArrayNotHasKey('pesan', $errors);
    }

    public function test_pesan_9_characters_is_rejected()
    {
        $errors = $this->validate(['pesan' => '123456789']);
        $this->assertArrayHasKey('pesan', $errors);
    }
}