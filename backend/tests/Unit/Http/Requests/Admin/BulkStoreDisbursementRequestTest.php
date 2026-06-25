<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\BulkStoreDisbursementRequest;
use Illuminate\Support\Facades\Validator;

final class BulkStoreDisbursementRequestTest extends TestCase
{
    private function validate(array $data): array
    {
        $request = new BulkStoreDisbursementRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        return $validator->errors()->toArray();
    }

    // ===== REQUIRED =====
    public function test_submission_ids_is_required(): void
    {
        $errors = $this->validate([]);
        $this->assertArrayHasKey('submission_ids', $errors);
    }

    // ===== BOUNDARY — MIN =====
    public function test_submission_ids_min_1(): void
    {
        $errors = $this->validate(['submission_ids' => []]);
        $this->assertArrayHasKey('submission_ids', $errors);
    }

    // ===== BOUNDARY — MAX =====
    public function test_submission_ids_max_50_fails(): void
    {
        $ids = array_fill(0, 51, '00000000-0000-0000-0000-000000000000');
        $errors = $this->validate(['submission_ids' => $ids]);
        $this->assertArrayHasKey('submission_ids', $errors);
    }

    // ===== BOUNDARY — MAX PASS =====
    public function test_submission_ids_max_50_passes(): void
    {
        $ids = array_fill(0, 50, '00000000-0000-0000-0000-000000000000');
        $errors = $this->validate(['submission_ids' => $ids]);
        $this->assertArrayNotHasKey('submission_ids', $errors);
    }

    // ===== HAPPY PATH =====
    public function test_submission_ids_min_1_passes(): void
    {
        $errors = $this->validate(['submission_ids' => ['00000000-0000-0000-0000-000000000000']]);
        $this->assertArrayNotHasKey('submission_ids', $errors);
    }
}