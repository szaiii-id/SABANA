<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\RejectSubmissionRequest;
use Illuminate\Support\Facades\Validator;

final class RejectSubmissionRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new RejectSubmissionRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    // ===== [1] HAPPY PATH =====
    public function test_valid_data_passes(): void
    {
        $errors = $this->validate(['notes' => 'Alasan penolakan yang valid dan lengkap.']);
        $this->assertEmpty($errors);
    }

    public function test_notes_with_revision_items_passes(): void
    {
        $errors = $this->validate([
            'notes'          => 'Perbaiki dokumen yang diupload.',
            'revision_items' => ['ktp', 'kk'],
        ]);
        $this->assertEmpty($errors);
    }

    // ===== [2] SAD PATH =====
    public function test_notes_is_required(): void
    {
        $errors = $this->validate([]);
        $this->assertArrayHasKey('notes', $errors);
        $this->assertContains('Alasan wajib diisi.', $errors['notes']);
    }

    public function test_notes_min_10_characters(): void
    {
        $errors = $this->validate(['notes' => 'Pendek']);
        $this->assertArrayHasKey('notes', $errors);
        $this->assertContains('Alasan minimal 10 karakter.', $errors['notes']);
    }

    // ===== [3] BOUNDARY =====
    public function test_notes_exactly_10_characters_passes(): void
    {
        $errors = $this->validate(['notes' => '1234567890']);
        $this->assertArrayNotHasKey('notes', $errors);
    }

    public function test_notes_max_500_characters(): void
    {
        $errors = $this->validate(['notes' => str_repeat('A', 501)]);
        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== [4] EDGE CASE =====
    public function test_notes_strip_tags_removes_html(): void
    {
        $errors = $this->validate(['notes' => '<p>Alasan yang <b>valid</b> dan lengkap.</p>']);
        $this->assertEmpty($errors);
    }

    // ===== [5] NULL / EMPTY =====
    public function test_notes_null_fails(): void
    {
        $errors = $this->validate(['notes' => null]);
        $this->assertArrayHasKey('notes', $errors);
    }

    public function test_notes_empty_string_fails(): void
    {
        $errors = $this->validate(['notes' => '']);
        $this->assertArrayHasKey('notes', $errors);
    }

    // ===== [6] DATA TYPE =====
    public function test_revision_items_must_be_array(): void
    {
        $errors = $this->validate([
            'notes'          => 'Perbaiki dokumen.',
            'revision_items' => 'bukan_array',
        ]);
        $this->assertArrayHasKey('revision_items', $errors);
    }

    public function test_revision_items_each_must_be_string(): void
    {
        $errors = $this->validate([
            'notes'          => 'Perbaiki dokumen.',
            'revision_items' => [123, 456],
        ]);
        $this->assertArrayHasKey('revision_items.0', $errors);
    }
}