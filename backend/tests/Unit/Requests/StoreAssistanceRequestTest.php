<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Models\Citizen;
use App\Http\Requests\Citizen\StoreAssistanceRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('request')]
final class StoreAssistanceRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new StoreAssistanceRequest();
        $request->setUserResolver(fn() => new Citizen(['id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890']));

        $validator = Validator::make($data, $request->rules(), $request->messages());

        // Skip database validation rules (exists, unique) untuk unit test
        $validator->after(function ($validator) {
            // Tidak perlu query database
        });

        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'program_id'          => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'regency_id'          => '6301',
            'district_id'         => '6301001',
            'village_id'          => '6301001001',
            'disbursement_method' => 'village_cash',
        ];
    }

    public function test_program_id_required(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['program_id' => '']));
        $this->assertArrayHasKey('program_id', $errors);
    }

    public function test_regency_id_required(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['regency_id' => '']));
        $this->assertArrayHasKey('regency_id', $errors);
    }

    public function test_regency_id_size_4(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['regency_id' => '63']));
        $this->assertArrayHasKey('regency_id', $errors);
    }

    public function test_district_id_size_7(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['district_id' => '6301']));
        $this->assertArrayHasKey('district_id', $errors);
    }

    public function test_village_id_size_10(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['village_id' => '6301']));
        $this->assertArrayHasKey('village_id', $errors);
    }

    public function test_disbursement_method_in_enum(): void
    {
        $errors = $this->validate(array_merge($this->validData(), ['disbursement_method' => 'paypal']));
        $this->assertArrayHasKey('disbursement_method', $errors);
    }

    public function test_bank_account_required_for_bpd_transfer(): void
    {
        $errors = $this->validate(array_merge($this->validData(), [
            'disbursement_method' => 'bpd_transfer',
        ]));
        $this->assertArrayHasKey('bank_account_number', $errors);
    }

    public function test_bank_account_not_required_for_village_cash(): void
    {
        $errors = $this->validate(array_merge($this->validData(), [
            'disbursement_method' => 'village_cash',
        ]));
        $this->assertArrayNotHasKey('bank_account_number', $errors);
    }
}