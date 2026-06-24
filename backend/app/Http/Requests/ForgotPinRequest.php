<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik'             => preg_replace('/[^0-9]/', '', $this->nik ?? ''),
            'whatsapp_number' => preg_replace('/[^0-9]/', '', $this->whatsapp_number ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'nik'             => 'required|string|size:16',
            'whatsapp_number' => 'required|string|min:10|max:15|regex:/^[0-9]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required'             => 'NIK wajib diisi.',
            'nik.size'                 => 'Format NIK harus tepat 16 digit.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.min'      => 'Nomor WhatsApp minimal 10 digit.',
            'whatsapp_number.max'      => 'Nomor WhatsApp maksimal 15 digit.',
            'whatsapp_number.regex'    => 'Nomor WhatsApp hanya boleh berisi angka.',
        ];
    }
}