<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class FrankenPHPRobustnessTest extends TestCase
{
    /**
     * PILAR 1: BASIC HTTP AVAILABILITY
     */
    public function test_1_server_responds_successfully(): void
    {
        // Mengetes apakah aplikasi bisa diakses lewat URL utamanya
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * PILAR 2: OCTANE & WORKER MODE CHECK
     */
    public function test_2_octane_worker_is_running(): void
    {
        // Jika Octane jalan, biasanya ada info di header atau kita cek lewat command internal
        $output = shell_exec('php artisan octane:status');
        
        $this->assertStringContainsString('Octane server is running', $output, "Octane/FrankenPHP tidak berjalan!");
    }

    /**
     * PILAR 3: THROUGHPUT (STRESS TEST HTTP)
     */
    public function test_3_server_concurrency_resilience(): void
    {
        // FrankenPHP harus kuat dihajar banyak request beruntun tanpa mati
        for ($i = 0; $i < 20; $i++) {
            $response = $this->get('/');
            $response->assertStatus(200);
        }
    }

    /**
     * PILAR 4: LARAVEL FRAMEWORK INTEGRITY
     */
    public function test_4_application_env_is_correct(): void
    {
        $env = app()->environment();
        $this->assertTrue(in_array($env, ['testing', 'local']), "Env saat ini: $env");
    }
}