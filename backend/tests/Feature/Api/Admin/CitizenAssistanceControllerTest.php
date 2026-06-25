<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Citizen;
use App\Models\AssistanceProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;

#[Group('feature')]
#[Group('controller')]
final class CitizenAssistanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Citizen $citizen;
    private AssistanceProgram $program;
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = Admin::create([
            'id'         => Str::uuid()->toString(),
            'nip'        => '199001012020011001',
            'name'       => 'Admin Test',
            'password'   => bcrypt('password'),
            'role'       => 'super_admin',
            'is_active'  => true,
        ]);

        $this->citizen = Citizen::create([
            'id'                       => Str::uuid()->toString(),
            'nik'                      => '6301234567890123',
            'family_card_number'       => '6301234567890123',
            'full_name'                => 'Joko Widodo',
            'whatsapp_number'          => '6281234567890',
            'is_verified'              => true,
            'pin'                      => bcrypt('123456'),
        ]);

        $this->program = AssistanceProgram::create([
            'id'             => Str::uuid()->toString(),
            'name'           => 'Bantuan Beras',
            'slug'           => 'bantuan-beras',
            'description'    => 'Program bantuan beras',
            'status'         => 'active',
            'benefit_amount' => 500000,
        ]);

        $this->prefix = '/api/v1/' . config('sabana.portal_prefix') . '/citizen-assistance';
    }

    private function validData(): array
    {
        return [
            'citizen_id'          => $this->citizen->id,
            'program_id'          => $this->program->id,
            'regency_id'          => '6301',
            'district_id'         => '6301020',
            'village_id'          => '6301020001',
            'disbursement_method' => 'village_cash',
        ];
    }

    // ===== HAPPY PATH =====

    public function test_store_returns_201(): void
    {
        Sanctum::actingAs($this->admin, ['admin'], 'admin-api');

        $response = $this->postJson("{$this->prefix}/submit", $this->validData());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'registration_number', 'status'],
            ]);
    }

    // ===== SAD PATH =====

    public function test_store_returns_422_for_invalid_data(): void
    {
        Sanctum::actingAs($this->admin, ['admin'], 'admin-api');

        $response = $this->postJson("{$this->prefix}/submit", []);
        $response->assertStatus(422);
    }

    public function test_store_returns_422_for_nonexistent_citizen(): void
    {
        Sanctum::actingAs($this->admin, ['admin'], 'admin-api');

        $data = $this->validData();
        $data['citizen_id'] = Str::uuid()->toString();

        $response = $this->postJson("{$this->prefix}/submit", $data);
        $response->assertStatus(422);
    }

    // ===== SECURITY =====

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson("{$this->prefix}/submit", $this->validData());
        $response->assertStatus(401);
    }
}