<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\StoreDisbursementRequest;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class StoreDisbursementRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): array
    {
        $request = new StoreDisbursementRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    private function validData(): array
    {
        $citizen = new Citizen();
        $citizen->nik = '1234567890123456';
        $citizen->family_card_number = '1234567890123456';
        $citizen->full_name = 'Test';
        $citizen->whatsapp_number = '6281234567890';
        $citizen->pin = bcrypt('123456');
        $citizen->save();

        $program = new AssistanceProgram();
        $program->name = 'Test';
        $program->description = 'Test';
        $program->status = 'active';
        $program->save();

        $submission = new AssistanceSubmission();
        $submission->citizen_id = $citizen->id;
        $submission->program_id = $program->id;
        $submission->status = 'validated';
        $submission->registration_number = 'REG-' . uniqid();
        $submission->regency_id = '6301';
        $submission->district_id = '630101';
        $submission->village_id = '6301010001';
        $submission->disbursement_method = 'village_cash';
        $submission->submission_data = json_encode([]);
        $submission->save();

        return ['submission_id' => (string) $submission->id];
    }

    // ===== REQUIRED =====
    public function test_submission_id_is_required(): void
    {
        $errors = $this->validate([]);
        $this->assertArrayHasKey('submission_id', $errors);
    }

    // ===== EXISTS =====
    public function test_submission_id_must_exist(): void
    {
        $errors = $this->validate(['submission_id' => '00000000-0000-0000-0000-000000000000']);
        $this->assertArrayHasKey('submission_id', $errors);
    }

    // ===== BOUNDARY =====
    public function test_notes_max_500_characters(): void
    {
        $data = $this->validData();
        $data['notes'] = str_repeat('A', 501);
        $errors = $this->validate($data);
        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== NULL / EMPTY =====
    public function test_notes_nullable(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertArrayNotHasKey('notes', $errors);
    }

    // ===== HAPPY PATH =====
    public function test_all_valid_data_passes(): void
    {
        $errors = $this->validate($this->validData());
        $this->assertEmpty($errors);
    }
}