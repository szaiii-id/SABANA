<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class BulkStoreDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->notes) {
            $this->merge([
                'notes' => strip_tags(trim($this->notes)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'submission_ids'   => 'required|array|min:1|max:50',
            'submission_ids.*' => 'string|exists:assistance_submissions,id',
            'notes'            => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'submission_ids.required' => 'Pilih minimal 1 submission.',
            'submission_ids.min'      => 'Pilih minimal 1 submission.',
            'submission_ids.max'      => 'Maksimal 50 submission per batch.',
            'submission_ids.*.exists' => 'Beberapa submission tidak ditemukan.',
            'notes.max'               => 'Catatan maksimal 500 karakter.',
        ];
    }
}