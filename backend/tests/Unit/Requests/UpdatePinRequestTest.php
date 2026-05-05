<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\Citizen\UpdatePinRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('request')]
final class UpdatePinRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new UpdatePinRequest();
        return Validator::make($data, $request->rules())->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'current_pin'          => '123456',
            'new_pin'              => '654321',
            'new_pin_confirmation' => '654321',
        ];
    }

    public function test_valid_data_passes(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }

    public function test_current_pin_required(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['current_pin' => '']));
        $this->assertArrayHasKey('current_pin', $errors);
    }

    public function test_current_pin_digits_6(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['current_pin' => '123']));
        $this->assertArrayHasKey('current_pin', $errors);
    }

    public function test_new_pin_different_from_current(): void
    {
        $errors = $this->validate(array_merge($this->validData(), [
            'new_pin'              => '123456',
            'new_pin_confirmation' => '123456',
        ]));
        $this->assertArrayHasKey('new_pin', $errors);
    }

    public function test_new_pin_confirmation_required(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['new_pin_confirmation' => '111111']));
        $this->assertArrayHasKey('new_pin', $errors);
    }
}