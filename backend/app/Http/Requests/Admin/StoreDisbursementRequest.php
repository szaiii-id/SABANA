<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

protected function prepareForValidation(): void
{
    if ($this->has('notes') && $this->notes !== null) {
        $this->merge([
            'notes' => strip_tags(trim((string) $this->notes)),
        ]);
    }
}

    public function rules(): array
    {
        return [
            'submission_id' => 'required|string|exists:assistance_submissions,id',
            'notes'         => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'submission_id.required' => 'Submission wajib dipilih.',
            'submission_id.exists'   => 'Submission tidak ditemukan.',
            'notes.max'              => 'Catatan maksimal 500 karakter.',
        ];
    }
}