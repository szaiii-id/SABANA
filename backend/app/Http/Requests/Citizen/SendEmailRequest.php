<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama'   => strip_tags(trim($this->nama ?? '')),
            'email'  => trim($this->email ?? ''),
            'subjek' => strip_tags(trim($this->subjek ?? '')),
            'pesan'  => strip_tags(trim($this->pesan ?? '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama'   => 'required|string|max:255',
            'email'  => 'required|email|max:255',
            'subjek' => 'required|string|min:5|max:255',
            'pesan'  => 'required|string|min:10|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'   => 'Nama wajib diisi.',
            'email.required'  => 'Email wajib diisi.',
            'email.email'     => 'Format email tidak valid.',
            'subjek.required' => 'Subjek laporan wajib diisi.',
            'subjek.min'      => 'Subjek minimal 5 karakter.',
            'pesan.required'  => 'Pesan laporan wajib diisi.',
            'pesan.min'       => 'Pesan laporan minimal 10 karakter.',
            'pesan.max'       => 'Pesan laporan maksimal 2000 karakter.',
        ];
    }
}