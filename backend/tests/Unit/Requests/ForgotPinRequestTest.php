<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\ForgotPinRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('request')]
final class ForgotPinRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new ForgotPinRequest();
        return Validator::make($data, $request->rules())->errors()->toArray();
    }

    public function test_valid_data_passes(): void
    {
        $errors = $this->validate([
            'nik'             => '6301234567890123',
            'whatsapp_number' => '081234567890',
        ]);
        $this->assertEmpty($errors);
    }

    public function test_nik_required(): void
    {
        $errors = $this->validate(['whatsapp_number' => '081234567890']);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_nik_size_16(): void
    {
        $errors = $this->validate(['nik' => '123', 'whatsapp_number' => '081234567890']);
        $this->assertArrayHasKey('nik', $errors);
    }

    public function test_whatsapp_required(): void
    {
        $errors = $this->validate(['nik' => '6301234567890123']);
        $this->assertArrayHasKey('whatsapp_number', $errors);
    }
}