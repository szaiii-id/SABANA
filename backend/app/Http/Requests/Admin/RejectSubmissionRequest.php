<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->notes ? strip_tags(trim($this->notes)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'notes'            => 'required|string|min:10|max:500',
            'revision_items'   => 'sometimes|array',
            'revision_items.*' => 'string',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Alasan wajib diisi.',
            'notes.min'      => 'Alasan minimal 10 karakter.',
            'notes.max'      => 'Alasan maksimal 500 karakter.',
        ];
    }
}