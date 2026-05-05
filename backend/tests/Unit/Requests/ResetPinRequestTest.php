<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\ResetPinRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('request')]
final class ResetPinRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new ResetPinRequest();
        return Validator::make($data, $request->rules())->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'nik'                  => '6301234567890123',
            'whatsapp_number'      => '081234567890',
            'otp'                  => '123456',
            'new_pin'              => '654321',
            'new_pin_confirmation' => '654321',
        ];
    }

    public function test_valid_data_passes(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }

    public function test_otp_size_6(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['otp' => '12']));
        $this->assertArrayHasKey('otp', $errors);
    }

    public function test_new_pin_size_6(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['new_pin' => '12', 'new_pin_confirmation' => '12']));
        $this->assertArrayHasKey('new_pin', $errors);
    }

    public function test_new_pin_confirmation_required(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['new_pin_confirmation' => '111111']));
        $this->assertArrayHasKey('new_pin', $errors);
    }

    public function test_nik_required(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['nik' => '']));
        $this->assertArrayHasKey('nik', $errors);
    }
}