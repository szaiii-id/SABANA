<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('request')]
final class RegisterRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new RegisterRequest();
        $validator = Validator::make($data, $request->rules());

        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'Akhmad Warga',
            'whatsapp_number'    => '081234567890',
            'pin'                => '123456',
            'pin_confirmation'   => '123456',
        ];
    }

    public function test_valid_data_passes(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }

    public function test_nik_must_start_with_63(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['nik' => '3301234567890123']));
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_size_16(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['nik' => '6301']));
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_family_card_size_16(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['family_card_number' => '123']));
        $this->assertArrayHasKey('family_card_number', $errors);
    }

    public function test_full_name_min_3(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['full_name' => 'AB']));
        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_full_name_regex_no_numbers(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['full_name' => 'Test123']));
        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_whatsapp_min_10(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['whatsapp_number' => '12345']));
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }

    public function test_pin_size_6(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['pin' => '12', 'pin_confirmation' => '12']));
        $this->assertArrayHasKey('pin', $errors);
    }

    public function test_pin_confirmation_mismatch(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['pin_confirmation' => '654321']));
        $this->assertArrayHasKey('pin', $errors);
    }
}