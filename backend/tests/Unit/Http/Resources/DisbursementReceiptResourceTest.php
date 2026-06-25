<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\Citizen;
use App\Models\Disbursement;
use App\Http\Resources\DisbursementReceiptResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class DisbursementReceiptResourceTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = new Admin();
        $this->admin->nip = '1990010120200110' . random_int(10, 99);
        $this->admin->name = 'Admin Test';
        $this->admin->password = bcrypt('password');
        $this->admin->role = 'super_admin';
        $this->admin->is_active = true;
        $this->admin->save();
    }

    private function submissionWithDisbursement(): AssistanceSubmission
    {
        $citizen = new Citizen();
        $citizen->nik = '6301234567890123';
        $citizen->family_card_number = '6301234567890123';
        $citizen->whatsapp_number = '6281234567890';
        $citizen->full_name = 'Muhammad Noor';
        $citizen->pin = bcrypt('123456');
        $citizen->save();

        $program = new AssistanceProgram();
        $program->name = 'BLT Dana Desa';
        $program->description = 'Program test';
        $program->status = 'active';
        $program->save();

        $submission = new AssistanceSubmission();
        $submission->citizen_id = $citizen->id;
        $submission->program_id = $program->id;
        $submission->registration_number = 'SBN-RCPT001';
        $submission->status = 'completed';
        $submission->regency_id = '6301';
        $submission->district_id = '630101';
        $submission->village_id = '6301010001';
        $submission->disbursement_method = 'village_cash';
        $submission->submission_data = json_encode([]);
        $submission->save();

        $d = new Disbursement();
        $d->submission_id = $submission->id;
        $d->program_id = $program->id;
        $d->citizen_id = $citizen->id;
        $d->amount = 500000;
        $d->method = 'village_cash';
        $d->reference_number = 'REF-' . uniqid();
        $d->disbursed_at = now();
        $d->disbursed_by = $this->admin->id;
        $d->save();

        return $submission->fresh(['disbursement', 'program', 'citizen']);
    }

    // ===== HAPPY PATH =====
    public function test_resource_has_all_required_keys(): void
    {
        $submission = $this->submissionWithDisbursement();
        $resource = new DisbursementReceiptResource($submission);
        $response = $resource->toArray(request());

        $this->assertArrayHasKey('registration_number', $response);
        $this->assertArrayHasKey('program_name', $response);
        $this->assertArrayHasKey('citizen_name', $response);
        $this->assertArrayHasKey('citizen_nik', $response);
        $this->assertArrayHasKey('family_card_number', $response);
        $this->assertArrayHasKey('amount', $response);
        $this->assertArrayHasKey('method', $response);
        $this->assertArrayHasKey('reference_number', $response);
        $this->assertArrayHasKey('disbursed_at', $response);
        $this->assertArrayHasKey('officer_name', $response);
    }

    public function test_resource_returns_correct_values(): void
    {
        $submission = $this->submissionWithDisbursement();
        $resource = new DisbursementReceiptResource($submission);
        $response = $resource->toArray(request());

        $this->assertEquals('SBN-RCPT001', $response['registration_number']);
        $this->assertEquals('Muhammad Noor', $response['citizen_name']);
        $this->assertEquals(500000, $response['amount']);
    }

    public function test_resource_method_label_for_village_cash(): void
    {
        $submission = $this->submissionWithDisbursement();
        $resource = new DisbursementReceiptResource($submission);
        $response = $resource->toArray(request());

        $this->assertStringContainsString('Tunai', $response['method']);
    }
}