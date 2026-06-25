<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    // ===== HELPER =====

    private function createLog(array $overrides = []): ActivityLog
    {
        return ActivityLog::query()->create(array_merge([
            'actor_type' => 'admin',
            'actor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'actor_name' => 'Admin Test',
            'actor_role' => 'super_admin',
            'module' => 'verification',
            'action' => 'approve',
            'action_label' => 'Menyetujui Pengajuan',
            'target_type' => 'submission',
            'target_id' => '550e8400-e29b-41d4-a716-446655440001',
            'target_name' => 'SBN-ABC123',
            'created_at' => now(),
        ], $overrides));
    }

    // ===== HAPPY PATH (5 test) =====

    public function test_activity_log_creates_with_uuid(): void
    {
        $log = $this->createLog();

        $this->assertNotEmpty($log->id);
        $this->assertEquals(36, strlen($log->id));
    }

    public function test_metadata_is_array(): void
    {
        $log = $this->createLog(['metadata' => ['key' => 'value']]);

        $this->assertIsArray($log->metadata);
        $this->assertEquals('value', $log->metadata['key']);
    }

   public function test_static_log_creates_record(): void
    {
        $log = ActivityLog::log(
            'admin',
            '550e8400-e29b-41d4-a716-446655440000',
            'Admin',
            'super_admin',
            'program', 'create', 'Membuat Program'
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    public function test_scope_by_admin_filters_correctly(): void
    {
        $this->createLog(['actor_id' => '550e8400-e29b-41d4-a716-44665544aaaa']);
        $this->createLog(['actor_id' => '550e8400-e29b-41d4-a716-44665544bbbb']);

        $count = ActivityLog::byAdmin('550e8400-e29b-41d4-a716-44665544aaaa')->count();

        $this->assertEquals(1, $count);
    }

    public function test_scope_by_module_filters_correctly(): void
    {
        $this->createLog(['module' => 'verification']);
        $this->createLog(['module' => 'disbursement']);

        $count = ActivityLog::byModule('verification')->count();

        $this->assertEquals(1, $count);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_scope_today_returns_today_logs(): void
    {
        $this->createLog(['created_at' => now()]);
        $this->createLog(['created_at' => now()->subDays(2)]);

        $count = ActivityLog::today()->count();

        $this->assertEquals(1, $count);
    }

    public function test_scope_recent_orders_by_created_at_desc(): void
    {
        $log1 = $this->createLog(['created_at' => now()->subHour()]);
        $log2 = $this->createLog(['created_at' => now()]);

        $logs = ActivityLog::recent()->get();

        $this->assertEquals($log2->id, $logs->first()->id);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_target_can_be_null(): void
    {
        $log = $this->createLog(['target_type' => null, 'target_id' => null, 'target_name' => null]);

        $this->assertNull($log->target_type);
        $this->assertNull($log->target_id);
    }

    public function test_metadata_can_be_null(): void
    {
        $log = $this->createLog(['metadata' => null]);

        $this->assertNull($log->metadata);
    }

    // ===== DATA TYPE (1 test) =====

    public function test_created_at_is_datetime(): void
    {
        $log = $this->createLog();

        $this->assertInstanceOf(\DateTime::class, $log->created_at);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_scope_by_action_filters_correctly(): void
    {
        $this->createLog(['action' => 'approve']);
        $this->createLog(['action' => 'reject']);

        $count = ActivityLog::byAction('approve')->count();

        $this->assertEquals(1, $count);
    }

    // ===== SECURITY (1 test) =====

    public function test_mass_assignment_does_not_override_id(): void
    {
        $log = $this->createLog();
        $originalId = $log->id;

        $log->fill(['id' => 'fake-uuid']);

        $this->assertEquals($originalId, $log->id);
    }
}