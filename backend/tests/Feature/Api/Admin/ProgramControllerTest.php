<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AssistanceProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ProgramControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $this->superAdmin = Admin::query()->create([
            'nip' => str_pad('sup001', 18, '0', STR_PAD_LEFT),
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function baseUrl(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/programs";
    }

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs($this->superAdmin, ['admin'], 'admin-api');
    }

    private function createProgram(array $overrides = []): AssistanceProgram
    {
        return AssistanceProgram::query()->create(array_merge([
            'name' => 'Program Test ' . uniqid(),
            'description' => 'Deskripsi program test',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'quota_total' => 100,
            'benefit_amount' => 500000,
            'status' => 'active',
        ], $overrides));
    }

    private function validStoreData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Program Baru',
            'description' => 'Deskripsi program baru',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'quota_total' => 50,
            'benefit_amount' => 250000,
            'status' => 'draft',
        ], $overrides);
    }

    // ===== HAPPY PATH (9 test) =====

    public function test_index_returns_paginated_programs(): void
    {
        $this->actingAsAdmin();
        $this->createProgram();

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure(['data', 'current_page', 'total', 'last_page', 'per_page']);
    }

    public function test_store_creates_program(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData());

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('message', 'Program berhasil dibuat.');
    }

    public function test_update_modifies_program(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram();

        $response = $this->putJson("{$this->baseUrl()}/{$program->id}", [
            'name' => 'Program Updated',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Program berhasil diperbarui.');
    }

    public function test_destroy_deletes_program(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['status' => 'closed']);

        $response = $this->deleteJson("{$this->baseUrl()}/{$program->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Program berhasil dihapus.');
    }

    public function test_close_program(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['status' => 'active']);

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/close");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Program berhasil ditutup.');
    }

    public function test_reopen_program(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['status' => 'closed']);

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/reopen");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Program berhasil dibuka kembali.');
    }

    public function test_upload_banner_successful(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram();
        $file = UploadedFile::fake()->image('banner.jpg');

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/banner", [
            'banner' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Banner berhasil diunggah.');
    }

    public function test_duplicate_program_successful(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['name' => 'Original Program']);

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/duplicate");

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Program berhasil diduplikasi.');
    }

    public function test_active_programs_returns_list(): void
    {
        $this->actingAsAdmin();
        $this->createProgram(['status' => 'active', 'start_date' => now()->subDay()]);

        $response = $this->getJson("{$this->baseUrl()}/active");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    // ===== SAD PATH (7 test) =====

    public function test_store_without_name_returns_422(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['name' => '']));

        $response->assertStatus(422);
    }

    public function test_store_without_description_returns_422(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['description' => '']));

        $response->assertStatus(422);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $this->actingAsAdmin();

        $response = $this->putJson("{$this->baseUrl()}/00000000-0000-0000-0000-000000000000", [
            'name' => 'Ghost',
        ]);

        $response->assertStatus(404);
    }

    public function test_close_nonexistent_returns_404(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson("{$this->baseUrl()}/00000000-0000-0000-0000-000000000000/close");

        $response->assertStatus(404);
    }

    public function test_reopen_nonexistent_returns_404(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson("{$this->baseUrl()}/00000000-0000-0000-0000-000000000000/reopen");

        $response->assertStatus(404);
    }

    public function test_duplicate_nonexistent_returns_404(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson("{$this->baseUrl()}/00000000-0000-0000-0000-000000000000/duplicate");

        $response->assertStatus(404);
    }

    public function test_index_without_auth_returns_401(): void
    {
        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    // ===== BOUNDARY (4 test) =====

    public function test_store_with_name_exactly_3_chars(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['name' => 'ABC']));

        $response->assertStatus(201);
    }

    public function test_store_with_benefit_amount_zero(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['benefit_amount' => 0]));

        $response->assertStatus(201);
    }

    public function test_store_with_end_date_before_start_date_returns_422(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData([
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(422);
    }

    public function test_store_with_quota_total_one(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['quota_total' => 1]));

        $response->assertStatus(201);
    }

    // ===== EDGE CASE (2 test) =====

    public function test_close_already_closed_program_returns_422(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['status' => 'closed']);

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/close");

        $response->assertStatus(422);
    }

    public function test_reopen_already_active_program_returns_422(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['status' => 'active']);

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/reopen");

        $response->assertStatus(422);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_store_with_empty_body_returns_422(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), []);

        $response->assertStatus(422);
    }

    public function test_active_programs_with_null_citizen_id(): void
    {
        $this->actingAsAdmin();
        $this->createProgram(['status' => 'active', 'start_date' => now()->subDay()]);

        $response = $this->getJson("{$this->baseUrl()}/active?citizen_id=");

        $response->assertStatus(200);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_store_with_string_quota_total_returns_422(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['quota_total' => 'abc']));

        $response->assertStatus(422);
    }

    // ===== EQUIVALENCE PARTITION (2 test) =====

    public function test_store_with_status_draft(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['status' => 'draft']));

        $response->assertStatus(201);
    }

    public function test_store_with_status_active(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData(['status' => 'active']));

        $response->assertStatus(201);
    }

    // ===== STATE TRANSITION (2 test) =====

    public function test_full_lifecycle_draft_to_closed(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['status' => 'draft']);

        // Update ke active
        $this->putJson("{$this->baseUrl()}/{$program->id}", ['status' => 'active']);
        $this->assertEquals('active', $program->fresh()->status);

        // Close
        $this->postJson("{$this->baseUrl()}/{$program->id}/close");
        $this->assertEquals('closed', $program->fresh()->status);
    }

    public function test_full_lifecycle_closed_to_reopen(): void
    {
        $this->actingAsAdmin();
        $program = $this->createProgram(['status' => 'closed']);

        // Reopen
        $this->postJson("{$this->baseUrl()}/{$program->id}/reopen");
        $this->assertEquals('active', $program->fresh()->status);
    }

    // ===== SECURITY (4 test) =====

    public function test_store_sanitizes_name_from_xss(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->baseUrl(), $this->validStoreData([
            'name' => '<script>alert("xss")</script>Program',
        ]));

        $response->assertStatus(201);
        $this->assertStringNotContainsString('<script>', $response->json('data.name'));
    }

    public function test_unauthorized_cannot_store(): void
    {
        $response = $this->postJson($this->baseUrl(), $this->validStoreData());

        $response->assertStatus(401);
    }

    public function test_unauthorized_cannot_close(): void
    {
        $program = $this->createProgram();

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/close");

        $response->assertStatus(401);
    }

    public function test_unauthorized_cannot_duplicate(): void
    {
        $program = $this->createProgram();

        $response = $this->postJson("{$this->baseUrl()}/{$program->id}/duplicate");

        $response->assertStatus(401);
    }
}