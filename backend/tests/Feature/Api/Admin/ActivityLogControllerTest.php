<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->admin, ['admin'], 'admin-api');
    }

    private function baseUrl(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/activity-logs";
    }

    // ===== HAPPY PATH (2 test) =====

    public function test_index_returns_paginated_logs(): void
    {
        ActivityLog::query()->create([
            'actor_type' => 'admin',
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->name,
            'actor_role' => $this->admin->role,
            'module' => 'verification',
            'action' => 'approve',
            'action_label' => 'Menyetujui Pengajuan',
            'created_at' => now(),
        ]);

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_index_with_module_filter(): void
    {
        ActivityLog::query()->create([
            'actor_type' => 'admin',
            'actor_id' => $this->admin->id,
            'actor_name' => $this->admin->name,
            'actor_role' => $this->admin->role,
            'module' => 'disbursement',
            'action' => 'disburse',
            'action_label' => 'Menyalurkan',
            'created_at' => now(),
        ]);

        $response = $this->getJson($this->baseUrl() . '?module=disbursement');

        $response->assertStatus(200);
    }

    // ===== SAD PATH (1 test) =====

    public function test_index_with_invalid_per_page_returns_422(): void
    {
        $response = $this->getJson($this->baseUrl() . '?per_page=5');

        $response->assertStatus(422);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_index_with_per_page_min(): void
    {
        $response = $this->getJson($this->baseUrl() . '?per_page=10');

        $response->assertStatus(200);
    }

    public function test_index_with_per_page_max(): void
    {
        $response = $this->getJson($this->baseUrl() . '?per_page=100');

        $response->assertStatus(200);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_index_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(401);
    }

    // ===== SECURITY (1 test) =====

    public function test_index_returns_empty_when_no_logs(): void
    {
        $response = $this->getJson($this->baseUrl());

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('meta.total'));
    }
}