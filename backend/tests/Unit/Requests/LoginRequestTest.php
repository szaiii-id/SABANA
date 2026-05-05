<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('request')]
final class LoginRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new LoginRequest();
        $validator = Validator::make($data, $request->rules());

        return $validator->errors()->toArray();
    }

    public function test_valid_data_passes_validation(): void
    {
        $errors = $this->validate([
            'nik' => '6301234567890123',
            'pin' => '123456',
        ]);

        $this->assertEmpty($errors);
    }

    public function test_nik_required(): void
    {
        $errors = $this->validate(['pin' => '123456']);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_pin_required(): void
    {
        $errors = $this->validate(['nik' => '6301234567890123']);
        $this->assertArrayHasKey('pin', $errors);
    }

    public function test_nik_must_be_16_digits(): void
    {
        $errors = $this->validate(['nik' => '123', 'pin' => '123456']);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_pin_must_be_6_digits(): void
    {
        $errors = $this->validate(['nik' => '6301234567890123', 'pin' => '12']);
        $this->assertArrayHasKey('pin', $errors);
    }


}