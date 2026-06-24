<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsappRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pesan' => strip_tags(trim($this->pesan ?? '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'pesan' => 'required|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'pesan.required' => 'Pesan laporan wajib diisi.',
            'pesan.min'      => 'Pesan laporan minimal 10 karakter.',
            'pesan.max'      => 'Pesan laporan maksimal 1000 karakter.',
        ];
    }
}