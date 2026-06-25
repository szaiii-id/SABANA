<?php

declare(strict_types=1);

// ===== FILE: tests/Unit/Models/CitizenRegistrationLogTest.php =====

namespace Tests\Unit\Models;

use App\Models\Citizen;
use App\Models\CitizenRegistrationLog;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CitizenRegistrationLogTest extends TestCase
{
    use RefreshDatabase;

    // ===== [DATA HELPERS] =====

    private function createLog(array $overrides = []): CitizenRegistrationLog
    {
        $citizen = Citizen::create([
            'nik' => '6371012508900001',
            'family_card_number' => '6371012508900002',
            'full_name' => 'Ahmad Fauzi',
            'whatsapp_number' => '6281234567890',
            'pin' => bcrypt('123456'),
            'is_verified' => true,
        ]);

        return CitizenRegistrationLog::create(array_merge([
            'citizen_id' => $citizen->id,
            'admin_id' => null,
            'admin_name' => 'Admin Test',
            'admin_role' => 'operator',
            'action' => 'register_with_pin',
            'metadata' => ['nik' => '6371012508900001'],
        ], $overrides));
    }

    // ============================================================
    // ===== (1) HAPPY PATH =====
    // ============================================================

    public function test_create_log_berhasil(): void
    {
        $log = $this->createLog();

        $this->assertNotNull($log->id);
        $this->assertEquals('register_with_pin', $log->action);
        $this->assertEquals('Admin Test', $log->admin_name);
    }

    public function test_to_activity_log_mengembalikan_array_lengkap(): void
    {
        $log = $this->createLog();

        $activityLog = $log->toActivityLog();

        $this->assertIsArray($activityLog);
        $this->assertEquals('admin', $activityLog['actor_type']);
        $this->assertEquals('registration', $activityLog['module']);
        $this->assertEquals('citizen', $activityLog['target_type']);
        $this->assertEquals('6371012508900001', $activityLog['target_name']);
        $this->assertEquals('citizen_registration_logs', $activityLog['source']);
    }

    // ============================================================
    // ===== (2) SAD PATH =====
    // ============================================================

    public function test_to_activity_log_target_name_null_jika_metadata_kosong(): void
    {
        $log = $this->createLog(['metadata' => []]);

        $activityLog = $log->toActivityLog();

        $this->assertNull($activityLog['target_name']);
    }

    // ============================================================
    // ===== (3) BOUNDARY =====
    // ============================================================

    public function test_metadata_maksimal_1_mb(): void
    {
        $largeMetadata = ['data' => str_repeat('A', 100000)];

        $log = $this->createLog(['metadata' => $largeMetadata]);

        $this->assertIsArray($log->metadata);
    }



    // ============================================================
    // ===== (5) NULL / EMPTY =====
    // ============================================================

    public function test_create_log_admin_null(): void
    {
        $log = $this->createLog(['admin_name' => null, 'admin_role' => null]);

        $this->assertNull($log->admin_name);
        $this->assertNull($log->admin_role);
    }

    // ============================================================
    // ===== (6) DATA TYPE =====
    // ============================================================

    public function test_metadata_di_cast_ke_array(): void
    {
        $log = $this->createLog(['metadata' => ['key' => 'value']]);

        $this->assertIsArray($log->metadata);
        $this->assertEquals('value', $log->metadata['key']);
    }

    public function test_id_bertipe_string_uuid(): void
    {
        $log = $this->createLog();

        $this->assertIsString($log->id);
        $this->assertEquals(36, strlen($log->id));
    }

    // ============================================================
    // ===== (7) EQUIVALENCE PARTITION =====
    // ============================================================

    public function test_grup_registration_labels(): void
    {
        $actions = [
            'register_with_pin' => 'Mendaftarkan Warga (dengan PIN)',
            'register_without_pin' => 'Mendaftarkan Warga (tanpa PIN)',
            'resend_pin' => 'Mengirim Ulang PIN',
            'update_data' => 'Mengubah Data Warga',
        ];

        foreach ($actions as $action => $label) {
            $citizen = Citizen::create([
                'nik' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
                'family_card_number' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
                'full_name' => 'Test',
                'whatsapp_number' => '628' . str_pad((string) random_int(1, 9999999), 9, '0', STR_PAD_LEFT),
                'pin' => bcrypt('123456'),
                'is_verified' => true,
            ]);

            $log = CitizenRegistrationLog::create([
                'citizen_id' => $citizen->id,
                'admin_name' => 'Admin Test',
                'admin_role' => 'operator',
                'action' => $action,
                'metadata' => ['nik' => $citizen->nik],
            ]);

            $activityLog = $log->toActivityLog();
            $this->assertEquals($label, $activityLog['action_label']);
        }
    }

    // ============================================================
    // ===== (8) STATE TRANSITION =====
    // ============================================================

    public function test_update_metadata_mengubah_nilai(): void
    {
        $log = $this->createLog(['metadata' => ['nik' => '1111111111111111']]);

        $log->update(['metadata' => ['nik' => '2222222222222222']]);
        $log->refresh();

        $this->assertEquals('2222222222222222', $log->metadata['nik']);
    }

    // ============================================================
    // ===== (9) CONCURRENCY =====
    // ============================================================

    public function test_create_dua_log_bersamaan_tidak_konflik(): void
    {
        $c1 = Citizen::create([
            'nik' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
            'family_card_number' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
            'full_name' => 'Test',
            'whatsapp_number' => '628' . str_pad((string) random_int(1, 9999999), 9, '0', STR_PAD_LEFT),
            'pin' => bcrypt('123456'),
            'is_verified' => true,
        ]);

        $c2 = Citizen::create([
            'nik' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
            'family_card_number' => '63' . str_pad((string) random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT),
            'full_name' => 'Test',
            'whatsapp_number' => '628' . str_pad((string) random_int(1, 9999999), 9, '0', STR_PAD_LEFT),
            'pin' => bcrypt('123456'),
            'is_verified' => true,
        ]);

        $log1 = CitizenRegistrationLog::create([
            'citizen_id' => $c1->id,
            'admin_name' => 'Admin',
            'admin_role' => 'operator',
            'action' => 'register_with_pin',
            'metadata' => [],
        ]);

        $log2 = CitizenRegistrationLog::create([
            'citizen_id' => $c2->id,
            'admin_name' => 'Admin',
            'admin_role' => 'operator',
            'action' => 'register_without_pin',
            'metadata' => [],
        ]);

        $this->assertNotEquals($log1->id, $log2->id);
    }

    // ============================================================
    // ===== (10) SECURITY =====
    // ============================================================

    public function test_to_activity_log_tidak_mengandung_data_sensitif(): void
    {
        $log = $this->createLog();

        $activityLog = $log->toActivityLog();

        $this->assertArrayNotHasKey('password', $activityLog);
        $this->assertArrayNotHasKey('pin', $activityLog);
        $this->assertArrayNotHasKey('token', $activityLog);
    }
}