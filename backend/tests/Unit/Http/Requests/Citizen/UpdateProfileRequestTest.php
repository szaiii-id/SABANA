<?php

namespace Tests\Unit\Http\Requests\Citizen;

use Tests\TestCase;
use App\Models\Citizen;
use App\Http\Requests\Citizen\UpdateProfileRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $request = new UpdateProfileRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    // =============================================
    // FULL NAME — 3 TEST
    // =============================================

    public function test_full_name_rejects_special_characters()
    {
        $errors = $this->validate(['full_name' => 'Muhammad @Noor']);
        $this->assertArrayHasKey('full_name', $errors);
        $this->assertContains('Nama lengkap hanya boleh berisi huruf, spasi, titik, koma, dan strip.', $errors['full_name']);
    }

    public function test_full_name_accepts_valid_name()
    {
        $errors = $this->validate(['full_name' => "Muhammad Noor, S.H."]);
        $this->assertArrayNotHasKey('full_name', $errors);
    }

    public function test_full_name_exceeds_255_characters()
    {
        $errors = $this->validate(['full_name' => str_repeat('A', 256)]);
        $this->assertArrayHasKey('full_name', $errors);
    }

    // =============================================
    // WHATSAPP NUMBER — 5 TEST
    // =============================================

    public function test_whatsapp_number_rejects_non_digit()
    {
        $errors = $this->validate(['whatsapp_number' => '62812abcdef']);
        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp hanya boleh berisi angka.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_min_10_digits()
    {
        $errors = $this->validate(['whatsapp_number' => '62812']);
        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp minimal 10 digit.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_max_15_digits()
    {
        $errors = $this->validate(['whatsapp_number' => '628123456789012345']);
        $this->assertArrayHasKey('whatsapp_number', $errors);
        $this->assertContains('Nomor WhatsApp maksimal 15 digit.', $errors['whatsapp_number']);
    }

    public function test_whatsapp_number_valid_format_passes()
    {
        $errors = $this->validate(['whatsapp_number' => '6281234567890']);
        $this->assertArrayNotHasKey('whatsapp_number', $errors);
    }

    public function test_whatsapp_number_unique_ignores_current_user()
    {
        $citizen = Citizen::factory()->create(['whatsapp_number' => '6281234567890']);


        /** @var \Illuminate\Contracts\Auth\Authenticatable $citizen */
        $this->actingAs($citizen);

        $request = new UpdateProfileRequest();
        $request->setUserResolver(fn() => $citizen);

        $validator = Validator::make(
            ['whatsapp_number' => '6281234567890'],
            $request->rules(),
            $request->messages()
        );

        $this->assertFalse($validator->fails());
    }

    // =============================================
    // EMPTY — 2 TEST
    // =============================================

    public function test_empty_full_name_is_valid()
    {
        $errors = $this->validate([]);
        $this->assertArrayNotHasKey('full_name', $errors);
    }

    public function test_empty_whatsapp_is_valid()
    {
        $errors = $this->validate([]);
        $this->assertArrayNotHasKey('whatsapp_number', $errors);
    }
}