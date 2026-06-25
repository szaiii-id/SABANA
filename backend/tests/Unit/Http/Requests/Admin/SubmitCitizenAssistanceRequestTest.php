<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SubmitCitizenAssistanceRequest;
use App\Models\Admin;
use App\Models\Citizen;
use App\Models\AssistanceProgram;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('request')]
final class SubmitCitizenAssistanceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $admin = new Admin();
        $admin->role = 'super_admin';

        $request = new SubmitCitizenAssistanceRequest();
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        $citizen = Citizen::create([
            'id'                       => Str::uuid()->toString(),
            'nik'                      => '6301234567890123',
            'family_card_number'       => '6301234567890123',
            'full_name'                => 'Test Citizen',
            'whatsapp_number'          => '6281234567890',
            'is_verified'              => true,
            'pin'                      => bcrypt('123456'),
        ]);

        $program = AssistanceProgram::create([
            'id'          => Str::uuid()->toString(),
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test description',
            'status'      => 'active',
        ]);

        return [
            'citizen_id'          => $citizen->id,
            'program_id'          => $program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ];
    }

    // ===== CITIZEN ID =====

    public function test_citizen_id_is_required(): void
    {
        $data = $this->validData();
        unset($data['citizen_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('citizen_id', $errors);
    }

    public function test_citizen_id_must_be_uuid(): void
    {
        $data = $this->validData();
        $data['citizen_id'] = 'not-uuid';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('citizen_id', $errors);
    }

    // ===== PROGRAM ID =====

    public function test_program_id_is_required(): void
    {
        $data = $this->validData();
        unset($data['program_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('program_id', $errors);
    }

    // ===== REGENCY =====

    public function test_regency_id_is_required(): void
    {
        $data = $this->validData();
        unset($data['regency_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('regency_id', $errors);
    }

    public function test_regency_id_must_be_4_digits(): void
    {
        $data = $this->validData();
        $data['regency_id'] = '63';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('regency_id', $errors);
    }

    // ===== DISTRICT =====

    public function test_district_id_is_required(): void
    {
        $data = $this->validData();
        unset($data['district_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('district_id', $errors);
    }

    // ===== VILLAGE =====

    public function test_village_id_is_required(): void
    {
        $data = $this->validData();
        unset($data['village_id']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('village_id', $errors);
    }

    // ===== DISBURSEMENT METHOD =====

    public function test_disbursement_method_is_required(): void
    {
        $data = $this->validData();
        unset($data['disbursement_method']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('disbursement_method', $errors);
    }

    public function test_disbursement_method_invalid_value(): void
    {
        $data = $this->validData();
        $data['disbursement_method'] = 'invalid';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('disbursement_method', $errors);
    }

    // ===== BANK ACCOUNT =====

    public function test_bank_account_required_for_bpd_transfer(): void
    {
        $data = $this->validData();
        $data['disbursement_method'] = 'bpd_transfer';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('bank_account_number', $errors);
    }

    // ===== PREPARE FOR VALIDATION =====

    public function test_prepare_for_validation_cleans_bank_account(): void
    {
        $admin = new Admin();
        $admin->role = 'super_admin';

        $request = new SubmitCitizenAssistanceRequest();
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });
        $request->replace([
            'bank_account_number' => '123-456-789',
            'citizen_id'          => '550e8400-e29b-41d4-a716-446655440000',
            'program_id'          => '660e8400-e29b-41d4-a716-446655440001',
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ]);

        $reflection = new \ReflectionMethod(SubmitCitizenAssistanceRequest::class, 'prepareForValidation');
        $reflection->invoke($request);

        $this->assertEquals('123456789', $request->bank_account_number);
    }

    // ===== ALL VALID =====

    public function test_all_valid_data_passes(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}