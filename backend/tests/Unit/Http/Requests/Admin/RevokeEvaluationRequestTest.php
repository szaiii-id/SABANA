<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\RevokeEvaluationRequest;
use Illuminate\Support\Facades\Validator;

final class RevokeEvaluationRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new RevokeEvaluationRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        return $validator->errors()->toArray();
    }

    // ===== HAPPY PATH =====
    public function test_valid_notes_passes_validation(): void
    {
        $errors = $this->validate(['notes' => 'Alasan penolakan yang valid dan cukup panjang.']);

        $this->assertEmpty($errors);
    }

    // ===== SAD PATH =====
    public function test_missing_notes_fails_validation(): void
    {
        $errors = $this->validate([]);

        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== BOUNDARY =====
    public function test_notes_exactly_10_characters_passes(): void
    {
        $errors = $this->validate(['notes' => 'Abcdefghij']); // 10 karakter

        $this->assertEmpty($errors);
    }

    public function test_notes_exactly_500_characters_passes(): void
    {
        $errors = $this->validate(['notes' => str_repeat('a', 500)]);

        $this->assertEmpty($errors);
    }

    public function test_notes_over_500_characters_fails(): void
    {
        $errors = $this->validate(['notes' => str_repeat('a', 501)]);

        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== EDGE CASE =====
    public function test_notes_less_than_10_characters_fails(): void
    {
        $errors = $this->validate(['notes' => 'Pendek']); // 6 karakter

        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== NULL / EMPTY =====
    public function test_empty_notes_fails_validation(): void
    {
        $errors = $this->validate(['notes' => '']);

        $this->assertArrayHasKey('notes', $errors);
    }

    public function test_null_notes_fails_validation(): void
    {
        $errors = $this->validate(['notes' => null]);

        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== DATA TYPE =====
    public function test_integer_notes_fails_validation(): void
    {
        $errors = $this->validate(['notes' => 12345]);

        $this->assertArrayHasKey('notes', $errors);
    }

    public function test_array_notes_fails_validation(): void
    {
        $errors = $this->validate(['notes' => ['test', 'array']]);

        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== EQUIVALENCE PARTITION =====
    public function test_notes_with_whitespace_only_fails(): void
    {
        $errors = $this->validate(['notes' => '          ']); 
        $this->assertArrayHasKey('notes', $errors);
    }

    public function test_notes_trimmed_valid(): void
    {
        $errors = $this->validate(['notes' => '  Alasan valid dengan spasi  ']);

        $this->assertEmpty($errors);
    }

    // ===== SECURITY =====
    public function test_xss_in_notes_passes_string_validation(): void
    {
        $errors = $this->validate(['notes' => '<script>alert("xss")</script> dan teks tambahan']);

        // String validation lolos — sanitasi di frontend/backend
        $this->assertEmpty($errors);
    }

    public function test_sql_injection_in_notes_passes_string_validation(): void
    {
        $errors = $this->validate(['notes' => "DROP TABLE users; -- dan teks tambahan"]);

        $this->assertEmpty($errors);
    }
}