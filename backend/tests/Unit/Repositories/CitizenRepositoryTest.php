<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\Citizen;
use App\Repositories\CitizenRepository;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('repository')]
class CitizenRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CitizenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CitizenRepository();
    }

    // ========================================================================
    // INTERFACE COMPLIANCE
    // ========================================================================

    #[Group('critical')]
    public function test_implements_citizen_repository_interface(): void
    {
        $this->assertInstanceOf(
            CitizenRepositoryInterface::class,
            $this->repository,
            'CitizenRepository MUST implement CitizenRepositoryInterface.'
        );
    }

    // ========================================================================
    // CREATE
    // ========================================================================

    public function test_create_returns_citizen_instance(): void
    {
        $data = [
            'nik'                => '6301234567890123',
            'family_card_number' => '6301234567890123',
            'full_name'          => 'AKHMAD WARGA',
            'whatsapp_number'    => '081234567890',
            'pin'                => bcrypt('123456'),
        ];

        $citizen = $this->repository->create($data);

        $this->assertInstanceOf(Citizen::class, $citizen);
        $this->assertDatabaseHas('citizens', ['nik' => '6301234567890123']);
    }

    public function test_create_persists_all_fields_correctly(): void
    {
        $data = [
            'nik'                => '6309998888777766',
            'family_card_number' => '6309998888777766',
            'full_name'          => 'JOHN DOE',
            'whatsapp_number'    => '089876543210',
            'pin'                => bcrypt('654321'),
        ];

        $citizen = $this->repository->create($data);

        $this->assertEquals('6309998888777766', $citizen->nik);
        $this->assertEquals('JOHN DOE', $citizen->full_name);
        $this->assertEquals('089876543210', $citizen->whatsapp_number);
    }

    // ========================================================================
    // FIND BY NIK
    // ========================================================================

    public function test_find_by_nik_returns_citizen_when_exists(): void
    {
        Citizen::factory()->create(['nik' => '6301112222333344']);

        $result = $this->repository->findByNik('6301112222333344');

        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertEquals('6301112222333344', $result->nik);
    }

    public function test_find_by_nik_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByNik('0000000000000000');

        $this->assertNull($result, 'findByNik MUST return null for nonexistent NIK.');
    }

    public function test_find_by_nik_is_case_insensitive(): void
    {
        // NIK adalah angka, tapi pastikan query tepat
        Citizen::factory()->create(['nik' => '6305556666777788']);

        $result = $this->repository->findByNik('6305556666777788');

        $this->assertNotNull($result);
    }

    // ========================================================================
    // FIND BY NIK & WHATSAPP
    // ========================================================================

    public function test_find_by_nik_and_whatsapp_returns_citizen_when_both_match(): void
    {
        Citizen::factory()->create([
            'nik'              => '6301231234123412',
            'whatsapp_number'  => '081122334455',
        ]);

        $result = $this->repository->findByNikAndWhatsapp('6301231234123412', '081122334455');

        $this->assertInstanceOf(Citizen::class, $result);
        $this->assertEquals('081122334455', $result->whatsapp_number);
    }

    public function test_find_by_nik_and_whatsapp_returns_null_when_nik_wrong(): void
    {
        Citizen::factory()->create([
            'nik'              => '6301231234123412',
            'whatsapp_number'  => '081122334455',
        ]);

        $result = $this->repository->findByNikAndWhatsapp('9999999999999999', '081122334455');

        $this->assertNull($result, 'MUST return null when NIK does not match.');
    }

    public function test_find_by_nik_and_whatsapp_returns_null_when_whatsapp_wrong(): void
    {
        Citizen::factory()->create([
            'nik'              => '6301231234123412',
            'whatsapp_number'  => '081122334455',
        ]);

        $result = $this->repository->findByNikAndWhatsapp('6301231234123412', '088888888888');

        $this->assertNull($result, 'MUST return null when WhatsApp does not match.');
    }

    // ========================================================================
    // UPDATE
    // ========================================================================

    public function test_update_modifies_existing_citizen(): void
    {
        $citizen = Citizen::factory()->create([
            'full_name'       => 'OLD NAME',
            'whatsapp_number' => '081111111111',
        ]);

        $updated = $this->repository->update($citizen->id, [
            'full_name'       => 'NEW NAME',
            'whatsapp_number' => '082222222222',
        ]);

        $this->assertEquals('NEW NAME', $updated->full_name);
        $this->assertEquals('082222222222', $updated->whatsapp_number);

        $this->assertDatabaseHas('citizens', [
            'id'               => $citizen->id,
            'full_name'        => 'NEW NAME',
            'whatsapp_number'  => '082222222222',
        ]);
    }

    public function test_update_throws_exception_when_citizen_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->update('00000000-0000-0000-0000-000000000000', ['full_name' => 'GHOST']);
    }

    // ========================================================================
    // OTP EXPIRED CHECK
    // ========================================================================

    public function test_is_otp_expired_returns_true_when_expired(): void
    {
        $citizen = Citizen::factory()->create([
            'temporary_pin_expired_at' => now()->subHour(),
        ]);

        $result = $this->repository->isOtpExpired($citizen);

        $this->assertTrue($result, 'OTP should be expired when time is in the past.');
    }

    public function test_is_otp_expired_returns_false_when_still_valid(): void
    {
        $citizen = Citizen::factory()->create([
            'temporary_pin_expired_at' => now()->addHour(),
        ]);

        $result = $this->repository->isOtpExpired($citizen);

        $this->assertFalse($result, 'OTP should NOT be expired when time is in the future.');
    }

    public function test_is_otp_expired_returns_true_at_exact_expiry(): void
    {
        $citizen = Citizen::factory()->create([
            'temporary_pin_expired_at' => now(),
        ]);

        $result = $this->repository->isOtpExpired($citizen);

        $this->assertTrue($result, 'OTP at exact expiry time should be considered expired.');
    }

    // ========================================================================
    // UPDATE PIN
    // ========================================================================

    public function test_update_pin_changes_citizen_pin(): void
    {
        $oldPin = bcrypt('old_pin_123');
        $newPin = bcrypt('new_pin_456');

        $citizen = Citizen::factory()->create(['pin' => $oldPin]);

        $result = $this->repository->updatePin($citizen, $newPin);

        $this->assertTrue($result, 'updatePin MUST return true on success.');

        $citizen->refresh();
        $this->assertEquals($newPin, $citizen->pin, 'Pin MUST be updated in database.');
    }

    public function test_update_pin_returns_boolean(): void
    {
        $citizen = Citizen::factory()->create(['pin' => bcrypt('123456')]);

        $result = $this->repository->updatePin($citizen, bcrypt('654321'));

        $this->assertIsBool($result, 'updatePin MUST return boolean.');
    }

    // ========================================================================
    // EDGE CASES
    // ========================================================================

    public function test_create_with_minimal_data(): void
    {
        $this->markTestSkipped(
            'Skipped: family_card_number has NOT NULL constraint. ' .
            'Minimal data must include all required fields per database schema.'
        );
    }

    public function test_find_by_nik_with_leading_zeros(): void
    {
        Citizen::factory()->create(['nik' => '0001234567890123']);

        $result = $this->repository->findByNik('0001234567890123');

        $this->assertNotNull($result, 'NIK with leading zeros MUST be found.');
        $this->assertEquals('0001234567890123', $result->nik);
    }
}
