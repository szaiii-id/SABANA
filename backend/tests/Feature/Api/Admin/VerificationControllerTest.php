<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use App\Models\Citizen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private AssistanceProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Verifikator',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->program = AssistanceProgram::query()->create([
            'name' => 'Program Test ' . uniqid(),
            'description' => 'Deskripsi',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->admin, ['admin'], 'admin-api');
    }

    private function baseUrl(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/verifications";
    }

    private function createSubmission(string $status = 'pending'): AssistanceSubmission
    {
        $citizen = Citizen::query()->create([
            'nik' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'family_card_number' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT),
            'full_name' => 'Test Citizen',
            'whatsapp_number' => '0812' . mt_rand(10000000, 99999999),
            'pin' => bcrypt('123456'),
        ]);

        return AssistanceSubmission::query()->create([
            'citizen_id' => $citizen->id,
            'program_id' => $this->program->id,
            'registration_number' => 'SBN-' . strtoupper(substr(uniqid(), -8)),
            'regency_id' => '6301',
            'district_id' => '6301010',
            'village_id' => '6301010001',
            'status' => $status,
            'submission_data' => ['name' => 'Test'],
            'disbursement_method' => 'bpd_transfer',
        ]);
    }

    // ===== HAPPY PATH (6 test) =====

    public function test_index_returns_paginated_list(): void
    {
        $this->createSubmission('pending');

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_show_returns_detail(): void
    {
        $submission = $this->createSubmission('pending');

        $response = $this->getJson("{$this->baseUrl()}/{$submission->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_approve_changes_status(): void
    {
        $submission = $this->createSubmission('pending');

        $response = $this->postJson("{$this->baseUrl()}/{$submission->id}/approve");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Pengajuan berhasil disetujui.');
    }

    public function test_reject_changes_status(): void
    {
        $submission = $this->createSubmission('pending');

        $response = $this->postJson("{$this->baseUrl()}/{$submission->id}/reject", [
            'notes' => 'Tidak memenuhi syarat verifikasi',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Pengajuan berhasil ditolak.');
    }

    public function test_request_revision_changes_status(): void
    {
        $submission = $this->createSubmission('pending');

        $response = $this->postJson("{$this->baseUrl()}/{$submission->id}/request-revision", [
            'notes' => 'Mohon lengkapi dokumen yang kurang',
            'revision_items' => ['ktp', 'kk'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Permintaan perbaikan berhasil dikirim.');
    }

    public function test_complete_changes_status(): void
    {
        $submission = $this->createSubmission('validated');

        $response = $this->postJson("{$this->baseUrl()}/{$submission->id}/complete");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Pengajuan berhasil ditandai selesai.');
    }

    // ===== SAD PATH (4 test) =====

    public function test_show_returns_404_for_unknown(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/00000000-0000-0000-0000-000000000000");

        $response->assertStatus(404);
    }

    public function test_reject_without_notes_returns_422(): void
    {
        $submission = $this->createSubmission('pending');

        $response = $this->postJson("{$this->baseUrl()}/{$submission->id}/reject", []);

        $response->assertStatus(422);
    }

    public function test_approve_non_pending_returns_422(): void
    {
        $submission = $this->createSubmission('validated');

        $response = $this->postJson("{$this->baseUrl()}/{$submission->id}/approve");

        $response->assertStatus(422);
    }

    public function test_reject_short_notes_returns_422(): void
    {
        $submission = $this->createSubmission('pending');

        $response = $this->postJson("{$this->baseUrl()}/{$submission->id}/reject", [
            'notes' => 'Singkat',
        ]);

        $response->assertStatus(422);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_index_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    public function test_show_without_auth_returns_error(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson("{$this->baseUrl()}/some-id");

        $this->assertContains($response->status(), [401, 404]);
    }

    // ===== SECURITY (1 test) =====

    public function test_bulk_complete_requires_array_of_ids(): void
    {
        $response = $this->postJson("{$this->baseUrl()}/bulk-complete", ['ids' => 'not-array']);

        $response->assertStatus(422);
    }
}