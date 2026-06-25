<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources\Admin;

use Tests\TestCase;
use App\Models\Citizen;
use App\Models\AssistanceSubmission;
use App\Models\AssistanceProgram;
use App\Models\EvaluationLog;
use App\Http\Resources\Admin\EvaluationResource;
use App\Services\Admin\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

final class EvaluationResourceTest extends TestCase
{
    use RefreshDatabase;

    private EvaluationLog $evaluationLog;
    private VerificationService $verificationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Wilayah
        \Illuminate\Support\Facades\DB::table('provinces')->insert(['id' => '63', 'name' => 'Kalsel']);
        \Illuminate\Support\Facades\DB::table('regencies')->insert(['id' => '6301', 'province_id' => '63', 'name' => 'Kabupaten Test']);
        \Illuminate\Support\Facades\DB::table('districts')->insert(['id' => '630101', 'regency_id' => '6301', 'name' => 'Kecamatan Test']);
        \Illuminate\Support\Facades\DB::table('villages')->insert(['id' => '6301010001', 'district_id' => '630101', 'name' => 'Desa Test']);

        // Citizen
        $citizen = new Citizen();
        $citizen->nik = '1234567890123456';
        $citizen->family_card_number = '1234567890123456';
        $citizen->full_name = 'Test Citizen';
        $citizen->whatsapp_number = '6281234567890';
        $citizen->pin = bcrypt('123456');
        $citizen->save();

        // Program
        $program = new AssistanceProgram();
        $program->name = 'BLT Evaluation Test';
        $program->description = 'Program test';
        $program->status = 'active';
        $program->save();

        // Old Submission
        $oldSubmission = new AssistanceSubmission();
        $oldSubmission->citizen_id = $citizen->id;
        $oldSubmission->program_id = $program->id;
        $oldSubmission->registration_number = 'REG-OLD-001';
        $oldSubmission->status = 'evaluation_pending';
        $oldSubmission->regency_id = '6301';
        $oldSubmission->district_id = '630101';
        $oldSubmission->village_id = '6301010001';
        $oldSubmission->submission_data = json_encode([]);
        $oldSubmission->disbursement_method = 'village_cash';
        $oldSubmission->save();

        // New Submission
        $newSubmission = new AssistanceSubmission();
        $newSubmission->citizen_id = $citizen->id;
        $newSubmission->program_id = $program->id;
        $newSubmission->registration_number = 'REG-NEW-001';
        $newSubmission->status = 'validated';
        $newSubmission->smart_score = 75.5;
        $newSubmission->regency_id = '6301';
        $newSubmission->district_id = '630101';
        $newSubmission->village_id = '6301010001';
        $newSubmission->submission_data = json_encode([]);
        $newSubmission->disbursement_method = 'village_cash';
        $newSubmission->save();

        // EvaluationLog
        $this->evaluationLog = new EvaluationLog();
        $this->evaluationLog->submission_id = $oldSubmission->id;
        $this->evaluationLog->new_submission_id = $newSubmission->id;
        $this->evaluationLog->program_id = $program->id;
        $this->evaluationLog->citizen_id = $citizen->id;
        $this->evaluationLog->status = 'updated';
        $this->evaluationLog->decision_notes = null;
        $this->evaluationLog->triggered_by = 'system';
        $this->evaluationLog->triggered_at = now()->subDays(10);
        $this->evaluationLog->save();
        $this->evaluationLog->load(['submission', 'newSubmission', 'citizen', 'program']);

        // Real service (karena final class tidak bisa di-mock)
        $this->verificationService = app(VerificationService::class);
    }

    // ===== HAPPY PATH =====
    public function test_resource_returns_correct_structure(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => (object) ['role' => 'regency_admin']);

        $resource = (new EvaluationResource($this->evaluationLog))->withEvaluationService($this->verificationService);
        $data = $resource->toArray($request);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('status_label', $data);
        $this->assertArrayHasKey('submission', $data);
        $this->assertArrayHasKey('new_submission', $data);
        $this->assertArrayHasKey('citizen', $data);
        $this->assertArrayHasKey('program', $data);
    }

    // ===== SAD PATH — WITHOUT SERVICE INJECTION =====
    public function test_resource_falls_back_when_service_not_injected(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => (object) ['role' => 'regency_admin']);

        $resource = new EvaluationResource($this->evaluationLog);
        $data = $resource->toArray($request);

        $this->assertEquals('Tidak Diketahui', $data['new_submission']['recommendation']['label']);
        $this->assertEquals('gray', $data['new_submission']['recommendation']['color']);
    }

    // ===== BOUNDARY — NULL SMART SCORE =====
    public function test_resource_handles_null_smart_score(): void
    {
        $this->evaluationLog->newSubmission->smart_score = null;
        $this->evaluationLog->newSubmission->save();

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => (object) ['role' => 'regency_admin']);

        $resource = (new EvaluationResource($this->evaluationLog))->withEvaluationService($this->verificationService);
        $data = $resource->toArray($request);

        $this->assertEquals('Belum Dinilai', $data['new_submission']['recommendation']['label']);
    }

    // ===== NULL/EMPTY =====
    public function test_resource_handles_empty_relations(): void
    {
        $this->evaluationLog->decision_notes = null;
        $this->evaluationLog->decided_at = null;
        $this->evaluationLog->save();

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => (object) ['role' => 'regency_admin']);

        $resource = (new EvaluationResource($this->evaluationLog))->withEvaluationService($this->verificationService);
        $data = $resource->toArray($request);

        $this->assertNull($data['decision_notes']);
        $this->assertNull($data['decided_at']);
    }

    // ===== SECURITY — VILLAGE OFFICER TIDAK LIHAT OLD_DATA =====
    public function test_village_officer_cannot_see_old_data(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => (object) ['role' => 'village_officer']);

        $resource = (new EvaluationResource($this->evaluationLog))->withEvaluationService($this->verificationService);
        $data = $resource->toArray($request);

        // MissingValue artinya field tidak muncul di response JSON
        $this->assertInstanceOf(\Illuminate\Http\Resources\MissingValue::class, $data['old_data']);
    }

    // ===== SECURITY — REGENCY ADMIN BISA LIHAT OLD_DATA =====
    public function test_regency_admin_can_see_old_data(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => (object) ['role' => 'regency_admin']);

        $resource = (new EvaluationResource($this->evaluationLog))->withEvaluationService($this->verificationService);
        $data = $resource->toArray($request);

        $this->assertArrayHasKey('old_data', $data);
    }

    // ===== STATE TRANSITION — STATUS LABEL =====
    public function test_status_label_maps_correctly(): void
    {
        $this->evaluationLog->status = 'approved';
        $this->evaluationLog->save();

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => (object) ['role' => 'regency_admin']);

        $resource = (new EvaluationResource($this->evaluationLog))->withEvaluationService($this->verificationService);
        $data = $resource->toArray($request);

        $this->assertEquals('Disetujui', $data['status_label']);
    }
}