<?php

namespace Tests\Feature\Controllers\Api\Citizen;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use App\Services\Assistance\AssistanceSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AssistanceControllerTest extends TestCase
{
    use RefreshDatabase; // SANGAT PENTING untuk menguji Rule::unique dan exists

    private AssistanceSubmissionService|MockInterface $submissionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->submissionServiceMock = Mockery::mock(AssistanceSubmissionService::class);
        $this->app->instance(AssistanceSubmissionService::class, $this->submissionServiceMock);
    }

    public function test_store_assistance_returns_201_on_success_with_village_cash()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create(); 
        $program = AssistanceProgram::factory()->create(); 
        $file = UploadedFile::fake()->image('ktp.jpg');
        
        $payload = [
            'program_id' => $program->id,
            'regency_id' => '6301',         // size: 4
            'district_id' => '6301001',     // size: 7
            'village_id' => '6301001001',   // size: 10
            'disbursement_method' => 'village_cash'
        ];

        $submissionMock = (object) [
            'registration_number' => 'REG-2026-001',
            'status' => 'pending'
        ];

        $this->submissionServiceMock
            ->shouldReceive('submit')
            ->once()
            ->andReturn($submissionMock);

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/citizen/assistance/submit', array_merge($payload, ['ktp' => $file]));

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);
    }

    public function test_store_assistance_returns_422_if_bpd_transfer_but_no_bank_account()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create(); 
        $program = AssistanceProgram::factory()->create(); 
        
        $payload = [
            'program_id' => $program->id,
            'regency_id' => '6301',
            'district_id' => '6301001',
            'village_id' => '6301001001',
            'disbursement_method' => 'bpd_transfer'
        ];

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/citizen/assistance/submit', $payload);

        // Service tidak boleh dipanggil karena harusnya dicegat oleh FormRequest!
        $this->submissionServiceMock->shouldNotReceive('submit');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['bank_account_number']);
    }

    public function test_store_assistance_returns_422_if_program_id_is_not_unique_for_pending_status()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create(); 
        $program = AssistanceProgram::factory()->create(); 

        // 1. Arrange: Masukkan data dummy ke database agar warga seolah-olah sudah punya pengajuan 'pending'
        AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status' => 'pending'
        ]);
        
        $payload = [
            'program_id' => $program->id,
            'regency_id' => '6301',
            'district_id' => '6301001',
            'village_id' => '6301001001',
            'disbursement_method' => 'village_cash'
        ];

        // 2. Act: Coba daftar lagi di program yang sama
        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/citizen/assistance/submit', $payload);

        // 3. Assert: Pastikan ditolak dengan status 422 dan pesan custom Anda muncul
        $this->submissionServiceMock->shouldNotReceive('submit');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['program_id'])
                 ->assertJsonFragment([
                     'Anda sudah memiliki pengajuan yang sedang diproses untuk program ini.'
                 ]);
    }

    public function test_store_assistance_allows_registration_if_previous_submission_was_rejected()
    {
        /** @var \App\Models\Citizen|\Illuminate\Contracts\Auth\Authenticatable $citizen */
        $citizen = Citizen::factory()->create(); 
        $program = AssistanceProgram::factory()->create(); 

        // Arrange: Warga punya pengajuan di program yang sama, TAPI statusnya 'rejected' (Boleh daftar lagi)
        AssistanceSubmission::factory()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $program->id,
            'status' => 'rejected'
        ]);
        
        $payload = [
            'program_id' => $program->id,
            'regency_id' => '6301',
            'district_id' => '6301001',
            'village_id' => '6301001001',
            'disbursement_method' => 'village_cash'
        ];

        $submissionMock = (object) ['registration_number' => 'REG-NEW', 'status' => 'pending'];

        $this->submissionServiceMock
            ->shouldReceive('submit')
            ->once()
            ->andReturn($submissionMock);

        $response = $this->actingAs($citizen, 'sanctum')
            ->postJson('/api/v1/citizen/assistance/submit', $payload);

        // Assert: Harus lolos validasi (201) karena pengajuan lama sudah rejected
        $response->assertStatus(201);
    }
}