<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::query()->create([
            'nip' => str_pad((string) mt_rand(1000000000000000, 9999999999999999), 18, '0', STR_PAD_LEFT),
            'name' => 'Admin Dashboard',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->admin, ['admin'], 'admin-api');
    }

    private function baseUrl(): string
    {
        $prefix = config('sabana.portal_prefix', 'sabana-center-63');
        return "/api/v1/{$prefix}/dashboard";
    }

    // ===== HAPPY PATH (6 test) =====

    public function test_regency_stats_returns_success(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/regency/6301/stats");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_district_distribution_returns_success(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/regency/6301/districts");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_monthly_trend_returns_success(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/regency/6301/trend");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_verification_status_returns_success(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/regency/6301/verification-status");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_district_stats_returns_success(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/district/6301010/stats");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    public function test_village_stats_returns_success(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/village/6301010001/stats");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }

    // ===== SAD PATH (1 test) =====

    public function test_regency_stats_with_invalid_id(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/regency/99/stats");

        // Tetap 200 — data kosong
        $response->assertStatus(200);
    }

    // ===== NULL/EMPTY (1 test) =====

    public function test_dashboard_without_auth_returns_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson("{$this->baseUrl()}/regency/6301/stats");

        $response->assertStatus(401);
    }

    // ===== SECURITY (1 test) =====

    public function test_recent_citizens_returns_success(): void
    {
        $response = $this->getJson("{$this->baseUrl()}/village/6301010001/recent-citizens");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
    }
}