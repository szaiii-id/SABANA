<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\StoreProgramRequest;
use Illuminate\Support\Facades\Validator;

final class StoreProgramRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new StoreProgramRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        return [
            'name'           => 'BLT Dana Desa',
            'description'    => 'Bantuan Langsung Tunai',
            'start_date'     => '2026-01-01',
            'end_date'       => '2026-12-31',
            'status'         => 'draft',
        ];
    }

    // ===== NAME =====

    public function test_name_is_required(): void
    {
        $data = $this->validData();
        unset($data['name']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_name_min_3(): void
    {
        $data = $this->validData();
        $data['name'] = 'AB';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_name_max_255(): void
    {
        $data = $this->validData();
        $data['name'] = str_repeat('A', 256);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_name_exact_3_passes(): void
    {
        $data = $this->validData();
        $data['name'] = 'ABC';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('name', $errors);
    }

    // ===== DESCRIPTION =====

    public function test_description_is_required(): void
    {
        $data = $this->validData();
        unset($data['description']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('description', $errors);
    }

    public function test_description_min_10(): void
    {
        $data = $this->validData();
        $data['description'] = 'Pendek';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('description', $errors);
    }

    // ===== START DATE =====

    public function test_start_date_is_required(): void
    {
        $data = $this->validData();
        unset($data['start_date']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('start_date', $errors);
    }

    public function test_start_date_invalid_format(): void
    {
        $data = $this->validData();
        $data['start_date'] = 'invalid-date';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('start_date', $errors);
    }

    // ===== END DATE =====

    public function test_end_date_is_required(): void
    {
        $data = $this->validData();
        unset($data['end_date']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('end_date', $errors);
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $data = $this->validData();
        $data['start_date'] = '2026-12-31';
        $data['end_date'] = '2026-01-01';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('end_date', $errors);
    }

    public function test_end_date_same_as_start_date_fails(): void
    {
        $data = $this->validData();
        $data['start_date'] = '2026-06-15';
        $data['end_date'] = '2026-06-15';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('end_date', $errors);
    }

    // ===== STATUS =====

    public function test_status_is_required(): void
    {
        $data = $this->validData();
        unset($data['status']);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('status', $errors);
    }

    public function test_status_invalid_value(): void
    {
        $data = $this->validData();
        $data['status'] = 'invalid';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('status', $errors);
    }

    public function test_status_draft_passes(): void
    {
        $data = $this->validData();
        $data['status'] = 'draft';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('status', $errors);
    }

    public function test_status_active_passes(): void
    {
        $data = $this->validData();
        $data['status'] = 'active';
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('status', $errors);
    }

    public function test_status_closed_rejected(): void
    {
        $data = $this->validData();
        $data['status'] = 'closed';
        $errors = $this->validate($data);
        $this->assertArrayHasKey('status', $errors);
    }

    // ===== QUOTA =====

    public function test_quota_total_min_1(): void
    {
        $data = $this->validData();
        $data['quota_total'] = 0;
        $errors = $this->validate($data);
        $this->assertArrayHasKey('quota_total', $errors);
    }

    public function test_quota_total_nullable(): void
    {
        $data = $this->validData();
        $data['quota_total'] = null;
        $errors = $this->validate($data);
        $this->assertArrayNotHasKey('quota_total', $errors);
    }

    // ===== BENEFIT AMOUNT =====

    public function test_benefit_amount_min_0(): void
    {
        $data = $this->validData();
        $data['benefit_amount'] = -1000;
        $errors = $this->validate($data);
        $this->assertArrayHasKey('benefit_amount', $errors);
    }

    // ===== ALL VALID =====

    public function test_all_valid_data_passes(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}