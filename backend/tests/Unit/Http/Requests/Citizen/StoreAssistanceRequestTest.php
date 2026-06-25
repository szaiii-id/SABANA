<?php

namespace Tests\Unit\Http\Requests\Citizen;

use Tests\TestCase;
use App\Models\AssistanceProgram;
use App\Http\Requests\Citizen\StoreAssistanceRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StoreAssistanceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $request = new StoreAssistanceRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        $program = AssistanceProgram::factory()->create();
        return [
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ];
    }

    // =============================================
    // PROGRAM ID — 3 TEST
    // =============================================

    public function test_program_id_is_required()
    {
        $data = $this->validData();
        unset($data['program_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('program_id', $errors);
        $this->assertContains('Program bantuan wajib dipilih.', $errors['program_id']);
    }

    public function test_program_id_must_exist()
    {
        $data = $this->validData();
        $data['program_id'] = \Illuminate\Support\Str::uuid()->toString();
        $errors = $this->validate($data);
        $this->assertArrayHasKey('program_id', $errors);
    }

    public function test_program_id_valid_passes()
    {
        $errors = $this->validate($this->validData());
        $this->assertArrayNotHasKey('program_id', $errors);
    }

    // =============================================
    // REGENCY — 2 TEST
    // =============================================

    public function test_regency_id_is_required()
    {
        $data = $this->validData();
        unset($data['regency_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('regency_id', $errors);
    }

    public function test_regency_id_must_be_4_characters()
    {
        $data = $this->validData();
        $data['regency_id'] = '63';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('regency_id', $errors);
    }

    // =============================================
    // DISTRICT — 2 TEST
    // =============================================

    public function test_district_id_is_required()
    {
        $data = $this->validData();
        unset($data['district_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('district_id', $errors);
    }

    public function test_district_id_must_be_7_characters()
    {
        $data = $this->validData();
        $data['district_id'] = '6301';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('district_id', $errors);
    }

    // =============================================
    // VILLAGE — 2 TEST
    // =============================================

    public function test_village_id_is_required()
    {
        $data = $this->validData();
        unset($data['village_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('village_id', $errors);
    }

    public function test_village_id_must_be_10_characters()
    {
        $data = $this->validData();
        $data['village_id'] = '6301';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('village_id', $errors);
    }

    // =============================================
    // DISBURSEMENT — 2 TEST
    // =============================================

    public function test_disbursement_method_is_required()
    {
        $data = $this->validData();
        unset($data['disbursement_method']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('disbursement_method', $errors);
    }

    public function test_disbursement_method_invalid_value()
    {
        $data = $this->validData();
        $data['disbursement_method'] = 'invalid';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('disbursement_method', $errors);
    }

    // =============================================
    // BANK ACCOUNT — 2 TEST
    // =============================================

    public function test_bank_account_required_if_bpd_transfer()
    {
        $data = $this->validData();
        $data['disbursement_method'] = 'bpd_transfer';
        unset($data['bank_account_number']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('bank_account_number', $errors);
    }

    public function test_bank_account_not_required_for_village_cash()
    {
        $errors = $this->validate($this->validData());
        $this->assertArrayNotHasKey('bank_account_number', $errors);
    }

    // =============================================
    // ALL VALID — 1 TEST
    // =============================================

    public function test_all_valid_data_passes()
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}