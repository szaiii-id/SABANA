<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AssistanceProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssistanceProgramTest extends TestCase
{
    use RefreshDatabase;

    // ===== HELPER =====

    private function createProgram(array $overrides = []): AssistanceProgram
    {
        return AssistanceProgram::query()->create(array_merge([
            'name' => 'Program Test',
            'description' => 'Deskripsi program test',
            'status' => AssistanceProgram::STATUS_DRAFT,
            'is_active' => true,
        ], $overrides));
    }

    // ===== HAPPY PATH (6 test) =====

    public function test_program_creates_with_uuid(): void
    {
        $program = $this->createProgram();

        $this->assertNotEmpty($program->id);
        $this->assertEquals(36, strlen($program->id));
    }

    public function test_program_auto_generates_slug(): void
    {
        $program = $this->createProgram(['name' => 'Bantuan Beras']);

        $this->assertEquals('bantuan-beras', $program->slug);
    }

    public function test_constants_are_correct(): void
    {
        $this->assertEquals('draft', AssistanceProgram::STATUS_DRAFT);
        $this->assertEquals('active', AssistanceProgram::STATUS_ACTIVE);
        $this->assertEquals('closed', AssistanceProgram::STATUS_CLOSED);
        $this->assertEquals('completed', AssistanceProgram::STATUS_COMPLETED);
    }

    public function test_scope_active_returns_only_active(): void
    {
        $this->createProgram(['status' => 'active', 'name' => 'Active 1']);
        $this->createProgram(['status' => 'draft', 'name' => 'Draft 1']);

        $active = AssistanceProgram::query()->active()->count();

        $this->assertEquals(1, $active);
    }

    public function test_scope_by_status_filters_correctly(): void
    {
        $this->createProgram(['status' => 'closed', 'name' => 'Closed']);
        $this->createProgram(['status' => 'draft', 'name' => 'Draft']);

        $closed = AssistanceProgram::query()->byStatus('closed')->count();

        $this->assertEquals(1, $closed);
    }

    public function test_total_anggaran_calculates_correctly(): void
    {
        $program = $this->createProgram([
            'quota_total' => 100,
            'benefit_amount' => 500000,
        ]);

        $this->assertEquals(50000000, $program->total_anggaran);
    }

    // ===== SAD PATH (1 test) =====

    public function test_slug_unique_constraint(): void
    {
        $this->createProgram(['name' => 'Bantuan', 'slug' => 'bantuan']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->createProgram(['name' => 'Bantuan', 'slug' => 'bantuan']);
    }

    // ===== BOUNDARY (2 test) =====

    public function test_name_max_length(): void
    {
        $program = $this->createProgram(['name' => str_repeat('A', 255)]);

        $this->assertEquals(255, strlen($program->name));
    }

    public function test_benefit_amount_decimal(): void
    {
        $program = $this->createProgram(['benefit_amount' => 1234567.89]);

        $this->assertEquals(1234567.89, $program->benefit_amount);
    }

    // ===== EDGE CASE (1 test) =====

    public function test_slug_auto_updates_when_name_changes_without_manual_slug(): void
    {
        $program = $this->createProgram([
            'name' => 'Original Name',
            'slug' => 'custom-slug',
        ]);

        $program->update(['name' => 'New Name']);

        $this->assertEquals('new-name', $program->fresh()->slug);
    }

    // ===== NULL/EMPTY (2 test) =====

    public function test_quota_total_can_be_null(): void
    {
        $program = $this->createProgram(['quota_total' => null]);

        $this->assertNull($program->quota_total);
    }

    public function test_criteria_can_be_null(): void
    {
        $program = $this->createProgram(['criteria' => null]);

        $this->assertNull($program->criteria);
    }

    // ===== DATA TYPE (2 test) =====

    public function test_criteria_is_array(): void
    {
        $program = $this->createProgram(['criteria' => ['usia' => ['min' => 18]]]);

        $this->assertIsArray($program->criteria);
        $this->assertArrayHasKey('usia', $program->criteria);
    }

    public function test_is_active_is_boolean(): void
    {
        $program = $this->createProgram(['is_active' => false]);

        $this->assertFalse($program->is_active);
        $this->assertIsBool($program->is_active);
    }

    // ===== EQUIVALENCE PARTITION (1 test) =====

    public function test_all_statuses_can_be_set(): void
    {
        foreach (AssistanceProgram::ALL_STATUSES as $status) {
            $program = $this->createProgram(['status' => $status, 'name' => "Program {$status}"]);

            $this->assertEquals($status, $program->status);
        }
    }

    // ===== STATE TRANSITION (1 test) =====

    public function test_status_can_change_from_draft_to_active(): void
    {
        $program = $this->createProgram(['status' => 'draft']);

        $program->update(['status' => 'active']);

        $this->assertEquals('active', $program->fresh()->status);
    }

    // ===== SECURITY (2 test) =====

    public function test_mass_assignment_does_not_override_id(): void
    {
        $program = $this->createProgram();
        $originalId = $program->id;

        $program->fill(['id' => 'fake-uuid']);

        $this->assertEquals($originalId, $program->id);
    }

    public function test_soft_deletes_works(): void
    {
        $program = $this->createProgram();
        $program->delete();

        $this->assertSoftDeleted('assistance_programs', ['id' => $program->id]);
    }
}